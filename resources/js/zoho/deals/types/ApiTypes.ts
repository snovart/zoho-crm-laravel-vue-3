// API request/response contracts

export interface CreateDealRequest {
  deal: {
    name: string
    source: string
  }
  customer: {
    id?: number        // optional, for existing customer
    first_name?: string
    last_name?: string
    email?: string
  }
}

export interface CreateDealResponse {
  ok: boolean;
  deal_id: number;
  zoho_deal_id?: string | null;
  zoho_raw?: unknown;
}

export interface CustomerListItem {
  id: number;
  first_name: string | null;
  last_name: string | null;
  email: string | null;
}
