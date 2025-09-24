<?php

namespace App\Services\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class ZohoAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $refreshToken;
    private string $accountsBase;

    /** In-process cache to avoid hitting Cache store every call */
    private static ?string $memToken = null;
    private static int $memTokenExpiresAt = 0; // unix timestamp

    /** Cache key for cross-process sharing */
    private string $cacheKey = 'zoho.access_token';

    public function __construct()
    {
        $this->clientId     = (string) config('services.zoho.client_id');
        $this->clientSecret = (string) config('services.zoho.client_secret');
        $this->refreshToken = (string) config('services.zoho.refresh_token');
        $this->accountsBase = rtrim((string) config('services.zoho.accounts_base'), '/');
    }

    /**
     * Get a valid access token. Uses in-process + cache storage until expiry.
     */
    public function getAccessToken(): string
    {
        $now = time();

        // 1) Fast path: in-process token not expired
        if (self::$memToken && $now < (self::$memTokenExpiresAt - 30)) {
            return self::$memToken;
        }

        // 2) Cache storage (shared across workers)
        $cached = Cache::get($this->cacheKey);
        if (is_array($cached) && !empty($cached['token']) && !empty($cached['expires_at'])) {
            if ($now < ((int) $cached['expires_at'] - 30)) {
                self::$memToken = (string) $cached['token'];
                self::$memTokenExpiresAt = (int) $cached['expires_at'];
                return self::$memToken;
            }
        }

        // 3) Refresh via API (only when needed)
        [$token, $expiresIn] = $this->refreshAccessToken();

        // Compute expiry (buffer a bit)
        $buffer = 60; // seconds
        $expiresAt = $now + max(60, (int) $expiresIn) - $buffer;

        // Persist to cache and memory
        Cache::put($this->cacheKey, ['token' => $token, 'expires_at' => $expiresAt], $expiresIn - $buffer);
        self::$memToken = $token;
        self::$memTokenExpiresAt = $expiresAt;

        return $token;
    }

    /**
     * Refresh the access token using refresh_token with simple backoff for rate limits.
     *
     * @return array{0:string,1:int} [access_token, expires_in]
     */
    private function refreshAccessToken(): array
    {
        $url = $this->accountsBase . '/' . ltrim((string) config('zoho.endpoints.token'), '/');

        $maxAttempts = 4;            // small, polite number of retries
        $baseSleepMs = 700;          // initial backoff
        $timeout     = (int) config('zoho.timeout', 20);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $resp = Http::withOptions(['proxy' => null])
                ->asForm()
                ->timeout($timeout)
                ->post($url, [
                    'grant_type'    => 'refresh_token',
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                ]);

            // Successful HTTP status
            if ($resp->ok()) {
                $json = (array) $resp->json();
                $token = $json['access_token'] ?? null;
                if (!$token) {
                    throw new RuntimeException('ZohoAuth: no access_token in response');
                }
                $expiresIn = (int) ($json['expires_in'] ?? 3600);
                return [(string) $token, $expiresIn];
            }

            // Handle rate limit / too many requests style errors
            $body = (string) $resp->body();
            $status = $resp->status();
            $looksRateLimited =
                $status === 429 ||
                stripos($body, 'too many requests') !== false ||
                stripos($body, 'Please try again after some time') !== false;

            if ($looksRateLimited && $attempt < $maxAttempts) {
                // Exponential backoff with jitter
                $sleepMs = (int) ($baseSleepMs * (2 ** ($attempt - 1))) + random_int(0, 250);
                usleep($sleepMs * 1000);
                continue;
            }

            // Other errors -> fail fast with context
            throw new RuntimeException('ZohoAuth: token refresh failed: ' . $body);
        }

        // Should not reach here
        throw new RuntimeException('ZohoAuth: token refresh failed after retries');
    }
}
