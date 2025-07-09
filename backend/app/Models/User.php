<?php

namespace App\Models;

use App\Attributes\ApiResource;
use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[ApiResource(uri: 'users', apiPrefix: 'api', version: 'v1')]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'whatsapp',
        'active',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    /**
     * Get the columns to display in the index listing.
     */
    public function getIndexColumns(): array
    {
        return [
            'name' => [
                'label' => 'Name',
                'sortable' => true,
                'clickable' => true,
                'search' => true,
            ],
            'email' => [
                'label' => 'Email',
                'search' => true,
            ],
            'whatsapp' => [
                'label' => 'WhatsApp',
                'search' => true,
            ],
            'role' => [
                'label' => 'Role',
                'sortable' => true,
                'search' => true,
            ],
        ];
    }

    /**
     * Get the API schema for form generation.
     */
    public function getApiSchema(): array
    {
        return [
            'active' => [
                'label' => 'Status',
                'type' => 'checkbox',
                'required' => false,
                'default' => true,
            ],
            'name' => [
                'label' => 'Name',
                'placeholder' => 'Enter your full name',
                'required' => true,
                'maxLength' => 255,
            ],
            'username' => [
                'label' => 'Username',
                'placeholder' => 'Enter your username',
                'required' => true,
                'maxLength' => 255,
                'unique' => true,
            ],
            'email' => [
                'label' => 'Email',
                'placeholder' => 'Enter your email address',
                'required' => true,
                'maxLength' => 255,
                'unique' => true,
            ],
            'whatsapp' => [
                'label' => 'WhatsApp Number',
                'placeholder' => 'Enter your WhatsApp number',
                'required' => true,
                'pattern' => '^[0-9]{10,15}$',
                'unique' => true,
            ],
            'role' => [
                'label' => 'Role',
                'required' => true,
                'type' => 'select',
                'options' => [
                    ['value' => 'admin', 'label' => 'Admin'],
                    ['value' => 'user', 'label' => 'User'],
                ],
                'default' => 'user',
            ],
            'password' => [
                'label' => 'Password',
                'placeholder' => 'Enter your password',
                'required' => false, // Optional for updates
                'minLength' => 8,
            ],
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Set active to false if password is empty/null
            if (empty($user->password)) {
                $user->active = false;
            }
        });

        static::updating(function ($user) {
            // Set active to false if password is being set to empty/null
            if (empty($user->password)) {
                $user->active = false;
            }
        });
    }
}
