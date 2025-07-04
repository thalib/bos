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
     *
     * @return array
     */
    public function getIndexColumns(): array
    {
        return [
            'number' => [
                'label' => 'Estimate Number',
                'sortable' => true,
                'clickable' => true,
                'search' => true
            ],
            'date' => [
                'label' => 'Date',
            ],
            'customer_id' => [
                'label' => 'Customer',
            ],
            'status' => [
                'label' => 'Status',
            ],
            'salesperson' => [
                'label' => 'Salesperson',
            ],
            'grand_total' => [
                'label' => 'Total Amount',
                'format' => 'currency',
            ],
            'validity' => [
                'label' => 'Validity (Days)',
            ]
        ];
    }

    /**
     * Get the API schema for form generation.
     *
     * @return array
     */
    public function getApiSchema(): array
    {
        return [
            'type' => [
                'label' => 'Type',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'ESTIMATE', 'label' => 'Estimate'],
                    ['value' => 'QUOTE', 'label' => 'Quote']
                ],
                'default' => 'ESTIMATE'
            ],
            'number' => [
                'label' => 'Estimate Number',
                'placeholder' => 'E-2025-0001',
                'required' => true,
                'maxLength' => 50,
                'unique' => true
            ],
            'date' => [
                'label' => 'Date',
                'type' => 'date',
                'required' => true
            ],
            'validity' => [
                'label' => 'Validity (Days)',
                'type' => 'number',
                'required' => true,
                'min' => 1,
                'max' => 365,
                'default' => 30
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'DRAFT', 'label' => 'Draft'],
                    ['value' => 'SENT', 'label' => 'Sent'],
                    ['value' => 'ACCEPTED', 'label' => 'Accepted'],
                    ['value' => 'REJECTED', 'label' => 'Rejected'],
                    ['value' => 'EXPIRED', 'label' => 'Expired']
                ],
                'default' => 'DRAFT'
            ],
            'active' => [
                'label' => 'Active',
                'type' => 'checkbox',
                'required' => false,
                'default' => true
            ],
            'refrence' => [
                'label' => 'Reference',
                'placeholder' => 'PO-2025-0001',
                'required' => false,
                'maxLength' => 100
            ],
            'customer_id' => [
                'label' => 'Customer ID',
                'placeholder' => 'CUST001',
                'required' => true,
                'maxLength' => 50
            ],
            'salesperson' => [
                'label' => 'Salesperson',
                'placeholder' => 'Enter salesperson name',
                'required' => true,
                'maxLength' => 100
            ],
            'branch_id' => [
                'label' => 'Branch ID',
                'placeholder' => 'CHN001',
                'required' => false,
                'maxLength' => 50
            ],
            'channel' => [
                'label' => 'Channel',
                'type' => 'select',
                'required' => false,
                'options' => [
                    ['value' => 'Online', 'label' => 'Online'],
                    ['value' => 'Offline', 'label' => 'Offline'],
                    ['value' => 'Phone', 'label' => 'Phone'],
                    ['value' => 'Email', 'label' => 'Email']
                ]
            ],
            'tax_inclusive' => [
                'label' => 'Tax Inclusive',
                'type' => 'checkbox',
                'required' => false,
                'default' => false
            ],
            'show_bank_details' => [
                'label' => 'Show Bank Details',
                'type' => 'checkbox',
                'required' => false,
                'default' => true
            ],
            'bank_id' => [
                'label' => 'Bank ID',
                'placeholder' => 'BANK001',
                'maxLength' => 50
            ],
            'show_signature' => [
                'label' => 'Show Signature',
                'type' => 'checkbox',
                'required' => false,
                'default' => true
            ],
            'show_upi_qr' => [
                'label' => 'Show UPI QR',
                'type' => 'checkbox',
                'required' => false,
                'default' => true
            ],
            'customer_billing' => [
                'label' => 'Billing Address',
                'type' => 'object',
                'required' => true,
                'properties' => [
                    'name' => [
                        'label' => 'Company Name',
                        'required' => true,
                        'maxLength' => 255
                    ],
                    'address' => [
                        'label' => 'Address',
                        'required' => true,
                        'maxLength' => 500
                    ],
                    'city' => [
                        'label' => 'City',
                        'required' => true,
                        'maxLength' => 100
                    ],
                    'state' => [
                        'label' => 'State',
                        'required' => true,
                        'maxLength' => 100
                    ],
                    'pincode' => [
                        'label' => 'Pincode',
                        'required' => true,
                        'pattern' => '^[0-9]{6}$'
                    ],
                    'state_code' => [
                        'label' => 'State Code',
                        'required' => true,
                        'pattern' => '^[0-9]{2}$'
                    ],
                    'gstin' => [
                        'label' => 'GSTIN',
                        'required' => false,
                        'pattern' => '^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$'
                    ],
                    'phone' => [
                        'label' => 'Phone',
                        'required' => false,
                        'pattern' => '^[+][0-9-]{10,15}$'
                    ]
                ]
            ],
            'customer_shipping' => [
                'label' => 'Shipping Address',
                'type' => 'object',
                'required' => false,
                'properties' => [
                    'name' => [
                        'label' => 'Company Name',
                        'required' => true,
                        'maxLength' => 255
                    ],
                    'address' => [
                        'label' => 'Address',
                        'required' => true,
                        'maxLength' => 500
                    ],
                    'city' => [
                        'label' => 'City',
                        'required' => true,
                        'maxLength' => 100
                    ],
                    'state' => [
                        'label' => 'State',
                        'required' => true,
                        'maxLength' => 100
                    ],
                    'pincode' => [
                        'label' => 'Pincode',
                        'required' => true,
                        'pattern' => '^[0-9]{6}$'
                    ],
                    'state_code' => [
                        'label' => 'State Code',
                        'required' => true,
                        'pattern' => '^[0-9]{2}$'
                    ],
                    'gstin' => [
                        'label' => 'GSTIN',
                        'required' => false,
                        'pattern' => '^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$'
                    ],
                    'phone' => [
                        'label' => 'Phone',
                        'required' => false,
                        'pattern' => '^[+][0-9-]{10,15}$'
                    ]
                ]
            ],
            'items' => [
                'label' => 'Items',
                'type' => 'array',
                'required' => true,
                'minItems' => 1,
                'properties' => [
                    'name' => [
                        'label' => 'Item Name',
                        'required' => true,
                        'maxLength' => 255
                    ],
                    'description' => [
                        'label' => 'Description',
                        'required' => false,
                        'maxLength' => 1000
                    ],
                    'hsn_sac' => [
                        'label' => 'HSN/SAC Code',
                        'required' => false,
                        'maxLength' => 20
                    ],
                    'quantity' => [
                        'label' => 'Quantity',
                        'type' => 'number',
                        'required' => true,
                        'min' => 0.01
                    ],
                    'unit' => [
                        'label' => 'Unit',
                        'required' => true,
                        'maxLength' => 50
                    ],
                    'cost' => [
                        'label' => 'Cost',
                        'type' => 'number',
                        'required' => true,
                        'min' => 0
                    ],
                    'rate' => [
                        'label' => 'Rate',
                        'type' => 'number',
                        'required' => true,
                        'min' => 0
                    ],
                    'tax_rate' => [
                        'label' => 'Tax Rate (%)',
                        'type' => 'number',
                        'required' => true,
                        'min' => 0,
                        'max' => 100
                    ],
                    'total_amount' => [
                        'label' => 'Total Amount',
                        'type' => 'number',
                        'required' => true,
                        'min' => 0
                    ]
                ]
            ],
            'subtotal' => [
                'label' => 'Subtotal',
                'type' => 'number',
                'required' => true,
                'min' => 0
            ],
            'total_cost' => [
                'label' => 'Total Cost',
                'type' => 'number',
                'required' => true,
                'min' => 0
            ],
            'taxable_amount' => [
                'label' => 'Taxable Amount',
                'type' => 'number',
                'required' => true,
                'min' => 0
            ],
            'total_tax' => [
                'label' => 'Total Tax',
                'type' => 'number',
                'required' => true,
                'min' => 0
            ],
            'shipping_charges' => [
                'label' => 'Shipping Charges',
                'type' => 'number',
                'required' => false,
                'min' => 0,
                'default' => 0
            ],
            'other_charges' => [
                'label' => 'Other Charges',
                'type' => 'number',
                'required' => false,
                'min' => 0,
                'default' => 0
            ],
            'adjustment' => [
                'label' => 'Adjustment',
                'type' => 'number',
                'required' => false,
                'default' => 0
            ],
            'round_off' => [
                'label' => 'Round Off',
                'type' => 'number',
                'required' => false,
                'default' => 0
            ],
            'grand_total' => [
                'label' => 'Grand Total',
                'type' => 'number',
                'required' => true,
                'min' => 0
            ],
            'terms' => [
                'label' => 'Terms & Conditions',
                'type' => 'textarea',
                'required' => false,
                'maxLength' => 2000
            ],
            'notes' => [
                'label' => 'Notes',
                'type' => 'textarea',
                'required' => false,
                'maxLength' => 1000
            ],
            'created_by' => [
                'label' => 'Created By',
                'placeholder' => 'USER001',
                'required' => true,
                'maxLength' => 50
            ],
            'updated_by' => [
                'label' => 'Updated By',
                'placeholder' => 'USER001',
                'required' => true,
                'maxLength' => 50
            ]
        ];
    }

    /**
     * Get the grand total from totals JSON.
     *
     * @return float|null
     */
    public function getGrandTotalAttribute(): ?float
    {
        return $this->grand_total ?? null;
    }

    /**
     * Get the formatted date.
     *
     * @return string
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date ? $this->date->format('Y-m-d') : '';
    }
}
