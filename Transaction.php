<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'method',
        'amount',
        'currency',
        'status',
        'transaction_id',
        'order_id',
    ];

    protected $casts = [
        'event_data' => 'array',
        'amount' => 'decimal:2',
    ];
    public function participant(): HasOne
    {
        return $this->hasOne(SeminarParticipant::class);
    }
}