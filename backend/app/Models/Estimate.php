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
                'values' => ['DRAFT', 'SENT', 'ACCEPTED', 'REJECTED', 'EXPIRED', 'INVOICED'],
            ],
            'channel' => [
                'values' => ['Online', 'Offline'],
            ],
        ];
    }

    /**
     * Get the API schema for form generation.
     */
    public function getApiSchema(): array
    {
        $general = [
            'group' => 'General Information',
            'fields' => [
                [
                    'field' => 'active',
                    'type' => 'checkbox',
                    'label' => 'Active',
                    'required' => false,
                    'default' => true,
                ],
                [
                    'field' => 'type',
                    'type' => 'select',
                    'label' => 'Estimate Type',
                    'options' => [
                        ['value' => 'ESTIMATE', 'label' => 'Estimate'],
                        ['value' => 'QUOTATION', 'label' => 'Quotation'],
                        ['value' => 'PROPOSAL', 'label' => 'Proposal'],
                    ],
                    'required' => true,
                    'default' => 'ESTIMATE',
                ],
                [
                    'field' => 'number',
                    'type' => 'string',
                    'label' => 'Estimate Number',
                    'placeholder' => 'EST-001',
                    'required' => true,
                    'maxLength' => 50,
                ],
                [
                    'field' => 'date',
                    'type' => 'date',
                    'label' => 'Estimate Date',
                    'required' => true,
                ],
                [
                    'field' => 'validity',
                    'type' => 'number',
                    'label' => 'Validity (Days)',
                    'placeholder' => '30',
                    'required' => false,
                    'default' => 30,
                    'min' => 1,
                    'max' => 365,
                ],
                [
                    'field' => 'status',
                    'type' => 'select',
                    'label' => 'Status',
                    'options' => [
                        ['value' => 'DRAFT', 'label' => 'Draft'],
                        ['value' => 'SENT', 'label' => 'Sent'],
                        ['value' => 'ACCEPTED', 'label' => 'Accepted'],
                        ['value' => 'REJECTED', 'label' => 'Rejected'],
                        ['value' => 'EXPIRED', 'label' => 'Expired'],
                        ['value' => 'INVOICED', 'label' => 'Invoiced'],
                    ],
                    'required' => true,
                    'default' => 'DRAFT',
                ],
                [
                    'field' => 'refrence',
                    'type' => 'string',
                    'label' => 'Reference',
                    'placeholder' => 'Enter reference number',
                    'required' => false,
                    'maxLength' => 100,
                ],
                [
                    'field' => 'channel',
                    'type' => 'select',
                    'label' => 'Channel',
                    'options' => [
                        ['value' => 'Online', 'label' => 'Online'],
                        ['value' => 'Offline', 'label' => 'Offline'],
                    ],
                    'required' => false,
                    'default' => 'Online',
                ],
            ],
        ];

        $customer = [
            'group' => 'Customer Information',
            'fields' => [
                [
                    'field' => 'customer_id',
                    'type' => 'number',
                    'label' => 'Customer ID',
                    'placeholder' => 'Select customer',
                    'required' => true,
                    'min' => 1,
                ],
                [
                    'field' => 'salesperson',
                    'type' => 'string',
                    'label' => 'Sales Person',
                    'placeholder' => 'Enter salesperson name',
                    'required' => false,
                    'maxLength' => 100,
                ],
                [
                    'field' => 'branch_id',
                    'type' => 'number',
                    'label' => 'Branch ID',
                    'placeholder' => 'Select branch',
                    'required' => false,
                    'min' => 1,
                ],
                [
                    'field' => 'customer_billing',
                    'type' => 'object',
                    'label' => 'Billing Address',
                    'required' => false,
                    'properties' => [
                        'name' => ['type' => 'string', 'required' => true],
                        'address' => ['type' => 'string', 'required' => true],
                        'city' => ['type' => 'string', 'required' => true],
                        'state' => ['type' => 'string', 'required' => true],
                        'pincode' => ['type' => 'string', 'required' => true],
                        'phone' => ['type' => 'string', 'required' => false],
                        'email' => ['type' => 'string', 'required' => false],
                    ],
                ],
                [
                    'field' => 'customer_shipping',
                    'type' => 'object',
                    'label' => 'Shipping Address',
                    'required' => false,
                    'properties' => [
                        'name' => ['type' => 'string', 'required' => true],
                        'address' => ['type' => 'string', 'required' => true],
                        'city' => ['type' => 'string', 'required' => true],
                        'state' => ['type' => 'string', 'required' => true],
                        'pincode' => ['type' => 'string', 'required' => true],
                        'phone' => ['type' => 'string', 'required' => false],
                    ],
                ],
            ],
        ];

        $items = [
            'group' => 'Items',
            'fields' => [
                [
                    'field' => 'items',
                    'type' => 'array',
                    'label' => 'Estimate Items',
                    'required' => true,
                    'minItems' => 1,
                    'properties' => [
                        'product_id' => ['type' => 'number', 'required' => true],
                        'name' => ['type' => 'string', 'required' => true],
                        'description' => ['type' => 'string', 'required' => false],
                        'quantity' => ['type' => 'number', 'required' => true, 'min' => 1],
                        'unit_price' => ['type' => 'decimal', 'required' => true, 'min' => 0],
                        'discount' => ['type' => 'decimal', 'required' => false, 'min' => 0],
                        'tax_rate' => ['type' => 'decimal', 'required' => false, 'min' => 0, 'max' => 100],
                        'total' => ['type' => 'decimal', 'required' => true, 'min' => 0],
                    ],
                ],
            ],
        ];

        $totals = [
            'group' => 'Totals',
            'fields' => [
                [
                    'field' => 'subtotal',
                    'type' => 'decimal',
                    'label' => 'Subtotal',
                    'required' => false,
                    'default' => 0.00,
                    'min' => 0,
                ],
                [
                    'field' => 'total_cost',
                    'type' => 'decimal',
                    'label' => 'Total Cost',
                    'required' => false,
                    'default' => 0.00,
                    'min' => 0,
                ],
                [
                    'field' => 'taxable_amount',
                    'type' => 'decimal',
                    'label' => 'Taxable Amount',
                    'required' => false,
                    'default' => 0.00,
                    'min' => 0,
                ],
                [
                    'field' => 'total_tax',
                    'type' => 'decimal',
                    'label' => 'Total Tax',
                    'required' => false,
                    'default' => 0.00,
                    'min' => 0,
                ],
                [
                    'field' => 'shipping_charges',
                    'type' => 'decimal',
                    'label' => 'Shipping Charges',
                    'required' => false,
                    'default' => 0.00,
                    'min' => 0,
                ],
                [
                    'field' => 'other_charges',
                    'type' => 'decimal',
                    'label' => 'Other Charges',
                    'required' => false,
                    'default' => 0.00,
                    'min' => 0,
                ],
                [
                    'field' => 'adjustment',
                    'type' => 'decimal',
                    'label' => 'Adjustment',
                    'required' => false,
                    'default' => 0.00,
                ],
                [
                    'field' => 'round_off',
                    'type' => 'decimal',
                    'label' => 'Round Off',
                    'required' => false,
                    'default' => 0.00,
                ],
                [
                    'field' => 'grand_total',
                    'type' => 'decimal',
                    'label' => 'Grand Total',
                    'required' => true,
                    'default' => 0.00,
                    'min' => 0,
                ],
            ],
        ];

        $options = [
            'group' => 'Options',
            'fields' => [
                [
                    'field' => 'tax_inclusive',
                    'type' => 'checkbox',
                    'label' => 'Tax Inclusive',
                    'required' => false,
                    'default' => true,
                ],
                [
                    'field' => 'show_bank_details',
                    'type' => 'checkbox',
                    'label' => 'Show Bank Details',
                    'required' => false,
                    'default' => false,
                ],
                [
                    'field' => 'bank_id',
                    'type' => 'number',
                    'label' => 'Bank ID',
                    'placeholder' => 'Select bank',
                    'required' => false,
                    'min' => 1,
                ],
                [
                    'field' => 'show_signature',
                    'type' => 'checkbox',
                    'label' => 'Show Signature',
                    'required' => false,
                    'default' => false,
                ],
                [
                    'field' => 'show_upi_qr',
                    'type' => 'checkbox',
                    'label' => 'Show UPI QR Code',
                    'required' => false,
                    'default' => false,
                ],
            ],
        ];

        $notes = [
            'group' => 'Terms & Notes',
            'fields' => [
                [
                    'field' => 'terms',
                    'type' => 'textarea',
                    'label' => 'Terms & Conditions',
                    'placeholder' => 'Enter terms and conditions',
                    'required' => false,
                ],
                [
                    'field' => 'notes',
                    'type' => 'textarea',
                    'label' => 'Notes',
                    'placeholder' => 'Enter additional notes',
                    'required' => false,
                ],
            ],
        ];

        return [
            $general,
            $customer,
            $items,
            $totals,
            $options,
            $notes,
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
