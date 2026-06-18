<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PasswordResetToken extends Model
{
    protected $table      = 'password_reset_tokens';
    protected $primaryKey = 'email';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = ['email', 'token', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function isExpired(int $minutes = 60): bool
    {
        return Carbon::parse($this->created_at)->addMinutes($minutes)->isPast();
    }
}
