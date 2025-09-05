<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'two_factor_secret', 'two_factor_enabled'
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret'
    ];

    protected $casts = [
        'two_factor_enabled' => 'boolean',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function enableTwoFactorAuth()
    {
        $google2fa = new Google2FA();

        if (!$this->two_factor_secret) { 
            $this->two_factor_secret = $google2fa->generateSecretKey();
        }

        $this->two_factor_enabled = true;
        $this->save();
        
        return $this->two_factor_secret;
    }

    public function verifyTwoFactorCode($code)
    {
        if (!$this->two_factor_enabled) {
            return true;
        }

        $google2fa = new Google2FA();
        return $google2fa->verifyKey($this->two_factor_secret, $code, 2);
    }


    public function disableTwoFactorAuth()
    {
        $this->two_factor_secret = null;
        $this->two_factor_enabled = false;
        $this->save();
    }

    public function inventoryItems()
    {
        return $this->hasMany(Inventory::class, 'created_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isManager()
    {
        return $this->role === 'manager' || $this->isAdmin();
    }

    public function isViewer()
    {
        return $this->role === 'viewer' || $this->isManager();
    }
}