<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Account extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'Account';
    protected $primaryKey = 'Account_ID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'Account_Name',
        'Account_Username',
        'Account_Email',
        'Account_Password',
        'Account_Phone',
        'Account_Affiliation',
        'Account_Type',
        'Account_Role',
        'Account_Status', // from status

        'valid_id_path',
        'password_set_at',
        'last_login_at',
        'id_info',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'Account_Password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_set_at'   => 'datetime',
        'last_login_at'     => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(EventLog::class);
    }
}
