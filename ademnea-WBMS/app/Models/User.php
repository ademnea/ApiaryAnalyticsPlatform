<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
        use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'deleted_at'        => 'datetime',
        ];
    }

    /**
     * REQ-F-FAPI-01: every farmer User has exactly one linked Farmer
     * profile record (telephone, address, fcm_token, farm/hive access).
     */
        public function farmer()
    {
        return $this->hasOne(Farmer::class);
    }

    /**
     * Send the password reset notification via a custom mailable.
     *
     * @todo ResetPasswordMail created in Task 7
     */
    public function sendPasswordResetNotification($token): void
    {
        \Illuminate\Support\Facades\Mail::to($this->email)
            ->send(new \App\Mail\ResetPasswordMail($token, $this->email));
    }
}
