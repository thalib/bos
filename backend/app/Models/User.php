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
            [
                'field' => 'name',
                'label' => 'Name',
                'sortable' => true,
                'clickable' => true,
                'search' => true,
            ],
            [
                'field' => 'username',
                'label' => 'Username',
                'sortable' => true,
                'search' => true,
            ],
            [
                'field' => 'email',
                'label' => 'Email',
                'sortable' => true,
                'search' => true,
            ],
            [
                'field' => 'whatsapp',
                'label' => 'WhatsApp',
                'sortable' => true,
                'search' => true,
            ],
            [
                'field' => 'role',
                'label' => 'Role',
                'sortable' => true,
                'search' => true,
            ],
            [
                'field' => 'active',
                'label' => 'Status',
                'sortable' => true,
                'format' => 'boolean',
                'align' => 'center',
            ],
        ];
    }

    /**
     * Get the API schema for form generation.
     */
    public function getApiSchema(): array
    {
        return [
            [
                'group' => 'Basic Information',
                'fields' => [
                    [
                        'field' => 'active',
                        'label' => 'Status',
                        'type' => 'checkbox',
                        'required' => false,
                        'default' => true,
                    ],
                    [
                        'field' => 'name',
                        'label' => 'Name',
                        'type' => 'string',
                        'placeholder' => 'Enter your full name',
                        'required' => true,
                        'maxLength' => 255,
                    ],
                    [
                        'field' => 'username',
                        'label' => 'Username',
                        'type' => 'string',
                        'placeholder' => 'Enter your username',
                        'required' => true,
                        'maxLength' => 255,
                        'unique' => true,
                    ],
                    [
                        'field' => 'email',
                        'label' => 'Email',
                        'type' => 'string',
                        'placeholder' => 'Enter your email address',
                        'required' => true,
                        'maxLength' => 255,
                        'unique' => true,
                    ],
                ],
            ],
            [
                'group' => 'Contact Information',
                'fields' => [
                    [
                        'field' => 'whatsapp',
                        'label' => 'WhatsApp Number',
                        'type' => 'string',
                        'placeholder' => 'Enter your WhatsApp number',
                        'required' => true,
                        'pattern' => '^[0-9]{10,15}$',
                        'unique' => true,
                    ],
                ],
            ],
            [
                'group' => 'Account Settings',
                'fields' => [
                    [
                        'field' => 'role',
                        'label' => 'Role',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            ['value' => 'admin', 'label' => 'Admin'],
                            ['value' => 'user', 'label' => 'User'],
                        ],
                        'default' => 'user',
                    ],
                    [
                        'field' => 'password',
                        'label' => 'Password',
                        'type' => 'string',
                        'placeholder' => 'Enter your password',
                        'required' => false, // Optional for updates
                        'minLength' => 8,
                    ],
                ],
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
