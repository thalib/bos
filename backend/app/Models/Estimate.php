<?php

namespace App\Models;

use App\Attributes\ApiResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ApiResource(uri: 'estimates', apiPrefix: 'api', version: 'v1')]
class Estimate extends Model
{
    /** @use HasFactory<\Database\Factories\EstimateFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'number',
        'date',
        'validity',
        'status',
        'active',
        'refrence',
        'customer_id',
        'salesperson',
        'branch_id',
        'channel',
        // Flattened options
        'tax_inclusive',
        'show_bank_details',
        'bank_id',
        'show_signature',
        'show_upi_qr',
        'customer_billing',
        'customer_shipping',
        'items',
        // Flattened totals
        'subtotal',
        'total_cost',
        'taxable_amount',
        'total_tax',
        'shipping_charges',
        'other_charges',
        'adjustment',
        'round_off',
        'grand_total',
        'terms',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'validity' => 'integer',
            'active' => 'boolean',
            'tax_inclusive' => 'boolean',
            'show_bank_details' => 'boolean',
            'show_signature' => 'boolean',
            'show_upi_qr' => 'boolean',
            'customer_billing' => 'array',
            'customer_shipping' => 'array',
            'items' => 'array',
            'subtotal' => 'float',
            'total_cost' => 'float',
            'taxable_amount' => 'float',
            'total_tax' => 'float',
            'shipping_charges' => 'float',
            'other_charges' => 'float',
            'adjustment' => 'float',
            'round_off' => 'float',
            'grand_total' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the columns to display in the index listing.
     */
    public function getIndexColumns(): array
    {
        return [
            [
                'field' => 'number',
                'label' => 'Estimate Number',
                'sortable' => true,
                'clickable' => true,
                'search' => true,
            ],
            [
                'field' => 'date',
                'label' => 'Date',
                'sortable' => true,
                'format' => 'date',
            ],
            [
                'field' => 'customer_id',
                'label' => 'Customer',
                'sortable' => true,
                'search' => true,
            ],
            [
                'field' => 'status',
                'label' => 'Status',
                'sortable' => true,
                'search' => true,
            ],
            [
                'field' => 'salesperson',
                'label' => 'Salesperson',
                'sortable' => true,
                'search' => true,
            ],
            [
                'field' => 'grand_total',
                'label' => 'Total Amount',
                'sortable' => true,
                'format' => 'currency',
                'align' => 'right',
            ],
            [
                'field' => 'validity',
                'label' => 'Validity (Days)',
                'sortable' => true,
                'format' => 'number',
                'align' => 'center',
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
     * Get the API filters for this model.
     */
    public function getApiFilters(): array
    {
        return [
            'status' => [
                'label' => 'Status',
                'values' => ['DRAFT', 'SENT', 'ACCEPTED', 'REJECTED', 'EXPIRED', 'INVOICED'],
            ],
            'channel' => [
                'label' => 'Channel',
                'values' => ['Online', 'Offline'],
            ],
        ];
    }

    /**
     * Get the grand total from totals JSON.
     */
    public function getGrandTotalAttribute(): ?float
    {
        return $this->grand_total ?? null;
    }

    /**
     * Get the formatted date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date ? $this->date->format('Y-m-d') : '';
    }
}
