<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    use HasFactory;

    protected $table = 'verification_codes';

    protected $fillable = [
        'verifiable_type',
        'verifiable_id',
        'code',
        'verified',
        'expires_at'
    ];

    protected $casts = [
        'verified' => 'boolean'
    ];

    public function verifiable()
    {
        return $this->morphTo();
    }

    public function isExpired()
    {
        return $this->expires_at < now();
    }

    public function isVerified()
    {
        return $this->verified;
    }

    public function markAsVerified()
    {
        $this->verified = true;
        $this->save();
    }

    public function markAsExpired()
    {
        $this->expires_at = now();
        $this->save();
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getExpiresAt()
    {
        return $this->expires_at;
    }

}
