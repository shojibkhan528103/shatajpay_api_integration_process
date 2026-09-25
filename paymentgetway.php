<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class paymentgetway extends Model
{
    use HasFactory;

    protected $table = 'paymentgetways';

    protected $fillable = [
        'user_id',
        'getwayname',
        'displayname',
        'logo',
        'getway_id',
        'getway_storeid',
        'getway_appsecret',
        'getway_storepassword',
        'getway_username',
        'getway_mode',
        'getway_status',
    ];

    protected $casts = [
        'getway_status' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
