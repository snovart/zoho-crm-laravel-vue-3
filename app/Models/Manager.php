<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manager extends Model
{
    /** @use HasFactory<\Database\Factories\ManagerFactory> */
    use HasFactory;

    protected $fillable = ['email','deals_count'];

    public function deals()
    {
        return $this->hasMany(Deal::class, 'manager_id');
    }
}
