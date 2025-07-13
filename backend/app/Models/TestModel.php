<?php

namespace App\Models;

use App\Attributes\ApiResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ApiResource]
class TestModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // This model intentionally does NOT have getApiSchema() method
    // to test the null schema return case
}
