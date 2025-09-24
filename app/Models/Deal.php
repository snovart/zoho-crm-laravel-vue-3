<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    /** @use HasFactory<\Database\Factories\DealFactory> */
    use HasFactory;

    protected $fillable = ['name','customer_id','source'];

    public function customer()
    { 
        return $this->belongsTo(Customer::class); 
    }

    public function manager()
    {
        return $this->belongsTo(Manager::class, 'manager_id');
    }
}
