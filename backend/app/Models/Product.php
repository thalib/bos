<?php

namespace App\Models;

use App\Attributes\ApiResource;
use App\Traits\HandlesDatabaseDefaults;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[ApiResource(uri: 'products', apiPrefix: 'api', version: 'v1')]
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, HandlesDatabaseDefaults;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'publication_status',
        'active',
        'description',
        'short_description',
        'sku',
        'barcode',
        'brand',
        'cost',
        'mrp',
        'price',
        'sale_price',
        'taxable',
        'tax_hsn_code',
        'tax_rate',
        'tax_inclusive',
        'stock_track',
        'stock_quantity',
        'stock_low_threshold',
        'length',
        'width',
        'height',
        'weight',
        'unit',
        'shipping_weight',
        'shipping_required',
        'shipping_taxable',
        'shipping_class_id',
        'image',
        'images',
        'external_url',
        'categories',
        'tags',
        'attributes',
        'variations',
        'meta_data',
        'related_ids',
        'upsell_ids',
        'cross_sell_ids',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'taxable' => 'boolean',
        'tax_inclusive' => 'boolean',
        'stock_track' => 'boolean',
        'shipping_required' => 'boolean',
        'shipping_taxable' => 'boolean',
        'cost' => 'decimal:2',
        'mrp' => 'decimal:2',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'shipping_weight' => 'decimal:2',
        'images' => 'array',
        'categories' => 'array',
        'tags' => 'array',
        'attributes' => 'array',
        'variations' => 'array',
        'meta_data' => 'array',
        'related_ids' => 'array',
        'upsell_ids' => 'array',
        'cross_sell_ids' => 'array',
    ];

    /**
     * Get the GST information as an array.
     */
    protected function gst(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'hsn_code' => $this->tax_hsn_code,
                'gst_rate' => $this->tax_rate,
                'inclusive' => $this->tax_inclusive,
            ],
        );
    }

    /**
     * Get the stock information as an array.
     */
    protected function stock(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'track' => $this->stock_track,
                'quantity' => $this->stock_quantity,
                'low_threshold' => $this->stock_low_threshold,
            ],
        );
    }

    /**
     * Get the dimensions information as an array.
     */
    protected function dimensions(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'length' => $this->length,
                'width' => $this->width,
                'height' => $this->height,
                'weight' => $this->weight,
                'unit' => $this->unit,
            ],
        );
    }

    /**
     * Get the shipping information as an array.
     */
    protected function shipping(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'weight' => $this->shipping_weight,
                'required' => $this->shipping_required,
                'taxable' => $this->shipping_taxable,
                'class_id' => $this->shipping_class_id,
            ],
        );
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Backward compatibility alias for active scope.
     */
    public function scopeEnabled($query)
    {
        return $this->scopeActive($query);
    }

    /**
     * Scope a query to only include published products.
     */
    public function scopePublished($query)
    {
        return $query->where('publication_status', 'publish');
    }

    /**
     * Scope a query to only include products of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include products with stock tracking.
     */
    public function scopeTracked($query)
    {
        return $query->where('stock_track', true);
    }

    /**
     * Scope a query to only include products that are in stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Scope a query to only include products that are low in stock.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'stock_low_threshold');
    }

    /**
     * Check if the product is in stock.
     */
    public function isInStock(): bool
    {
        if (!$this->stock_track) {
            return true;
        }
        
        return $this->stock_quantity > 0;
    }

    /**
     * Check if the product is low in stock.
     */
    public function isLowStock(): bool
    {
        if (!$this->stock_track) {
            return false;
        }
        
        return $this->stock_quantity <= $this->stock_low_threshold;
    }

    /**
     * Get the effective price (sale price if available, otherwise regular price).
     */
    public function getEffectivePrice(): float
    {
        return $this->sale_price > 0 ? $this->sale_price : $this->price;
    }

    /**
     * Calculate the discount percentage.
     */
    public function getDiscountPercentage(): float
    {
        if ($this->sale_price <= 0 || $this->price <= 0) {
            return 0;
        }
        
        return round((($this->price - $this->sale_price) / $this->price) * 100, 2);
    }

    /**
     * Get the columns to display in the index listing.
     *
     * @return array
     */
    public function getIndexColumns(): array
    {
        return [
            'name' => [
                'label' => 'Product Name',
                'sortable' => true,
                'clickable' => true,
                'search' => true
            ],
            'cost' => [
                'label' => 'Cost',
                'formatter' => 'currency'
            ],
            'price' => [
                'label' => 'Price',
                'formatter' => 'currency'
            ],
            'mrp' => [
                'label' => 'MRP',
                'formatter' => 'currency'
            ],
            'stock_quantity' => [
                'label' => 'Stock',
                'formatter' => 'number'
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
        $general = [
            'group' => 'General Information',
            'fields' => [
                'active' => [
                    'type' => 'checkbox',
                    'label' => 'Active',
                    'required' => false,
                    'default' => true
                ],
                'name' => [
                    'label' => 'Product Name',
                    'placeholder' => 'Enter product name',
                    'required' => true,
                    'maxLength' => 255
                ],
                'slug' => [
                    'label' => 'URL Slug',
                    'placeholder' => 'auto-generated-from-name',
                    'required' => true,
                    'maxLength' => 255
                ],
                'type' => [
                    'type' => 'select',
                    'label' => 'Product Type',
                    'options' => [
                        ['value' => 'simple', 'label' => 'Simple Product'],
                        ['value' => 'variable', 'label' => 'Variable Product'],
                        ['value' => 'grouped', 'label' => 'Grouped Product'],
                        ['value' => 'external', 'label' => 'External Product']
                    ],
                    'required' => true,
                    'default' => 'simple'
                ],
                'publication_status' => [
                    'type' => 'select',
                    'label' => 'Publication Status',
                    'options' => [
                        ['value' => 'draft', 'label' => 'Draft'],
                        ['value' => 'published', 'label' => 'Published'],
                        ['value' => 'discontinued', 'label' => 'Discontinued'],
                        ['value' => 'private', 'label' => 'Private']
                    ],
                    'required' => true,
                    'default' => 'draft'
                ],
                'sku' => [
                    'label' => 'SKU',
                    'placeholder' => 'Enter SKU code',
                    'required' => false,
                    'maxLength' => 100
                ],
                'barcode' => [
                    'label' => 'Barcode',
                    'placeholder' => 'Enter barcode',
                    'required' => false,
                    'maxLength' => 100
                ],
                'brand' => [
                    'label' => 'Brand',
                    'placeholder' => 'Enter brand name',
                    'default' => 'ASENSAR',
                    'required' => false
                ],
                'unit' => [
                    'type' => 'select',
                    'label' => 'Unit',
                    'options' => [
                        ['value' => 'nos', 'label' => 'Nos'],
                        ['value' => 'piece', 'label' => 'Piece'],
                        ['value' => 'kg', 'label' => 'Kilogram'],
                        ['value' => 'gram', 'label' => 'Gram'],
                        ['value' => 'liter', 'label' => 'Liter'],
                        ['value' => 'meter', 'label' => 'Meter']
                    ],
                    'required' => false,
                    'default' => 'nos'
                ],
                'categories' => [
                    'type' => 'multiselect',
                    'label' => 'Categories',
                    'required' => false,
                    'options' => []
                ],
                'image' => [
                    'type' => 'file',
                    'label' => 'Featured Image',
                    'required' => false,
                    'accept' => 'image/*'
                ],
                'images' => [
                    'type' => 'file',
                    'label' => 'Product Images',
                    'required' => false,
                    'multiple' => true,
                    'accept' => 'image/*'
                ],
                'external_url' => [
                    'label' => 'External URL',
                    'placeholder' => 'https://asensar.com',
                    'required' => false,
                    'maxLength' => 500
                ]
            ]
        ];

        $priceInventory = [
            'group' => 'Price & Inventory',
            'fields' => [
                'cost' => [
                    'label' => 'Cost Price',
                    'placeholder' => '0.00',
                    'required' => false,
                    'default' => '0.00',
                    'min' => '0',
                    'step' => '0.01',
                    'prefix' => '₹'
                ],
                'mrp' => [
                    'label' => 'MRP',
                    'placeholder' => '0.00',
                    'default' => '0.00',
                    'required' => false,
                    'min' => '0',
                    'step' => '0.01',
                    'prefix' => '₹'
                ],
                'price' => [
                    'label' => 'Regular Price',
                    'placeholder' => '0.00',
                    'required' => true,
                    'default' => '0.00',
                    'min' => '0',
                    'step' => '0.01',
                    'prefix' => '₹'
                ],
                'sale_price' => [
                    'label' => 'Sale Price',
                    'placeholder' => '0.00',
                    'required' => false,
                    'default' => '0.00',
                    'min' => '0',
                    'step' => '0.01',
                    'prefix' => '₹'
                ],
                'stock_track' => [
                    'type' => 'checkbox',
                    'label' => 'Track Stock',
                    'required' => false,
                    'default' => false
                ],
                'stock_quantity' => [
                    'type' => 'number',
                    'label' => 'Stock Quantity',
                    'placeholder' => '0',
                    'default' => '0',
                    'required' => false,
                    'min' => '0',
                    'step' => '1'
                ],
                'stock_low_threshold' => [
                    'type' => 'number',
                    'label' => 'Low Stock Threshold',
                    'placeholder' => '5',
                    'required' => false,
                    'default' => '0',
                    'min' => '0',
                    'step' => '1'
                ]
            ]
        ];

        $tax = [
            'group' => 'TAX',
            'fields' => [
                'taxable' => [
                    'type' => 'checkbox',
                    'label' => 'Taxable',
                    'required' => false,
                    'default' => true
                ],
                'tax_hsn_code' => [
                    'label' => 'HSN Code',
                    'placeholder' => 'Enter HSN code',
                    'required' => false,
                    'maxLength' => 20
                ],
                'tax_rate' => [
                    'type' => 'number',
                    'label' => 'Tax Rate (%)',
                    'placeholder' => '18.00',
                    'default' => '18.00',
                    'required' => false,
                    'min' => '0',
                    'max' => '100',
                    'step' => '0.01',
                    'suffix' => '%'
                ],
                'tax_inclusive' => [
                    'type' => 'checkbox',
                    'label' => 'Tax Inclusive',
                    'required' => false,
                    'default' => true
                ]
            ]
        ];

        $shipping = [
            'group' => 'Shipping',
            'fields' => [
                'length' => [
                    'label' => 'Length',
                    'placeholder' => '0.00',
                    'default' => '0.00',
                    'required' => false,
                    'min' => '0',
                    'step' => '0.01',
                    'suffix' => 'cm'
                ],
                'width' => [
                    'label' => 'Width',
                    'placeholder' => '0.00',
                    'default' => '0.00',
                    'required' => false,
                    'min' => '0',
                    'step' => '0.01',
                    'suffix' => 'cm'
                ],
                'height' => [
                    'label' => 'Height',
                    'placeholder' => '0.00',
                    'default' => '0.00',
                    'required' => false,
                    'min' => '0',
                    'step' => '0.01',
                    'suffix' => 'cm'
                ],
                'weight' => [
                    'label' => 'Weight',
                    'placeholder' => '0.00',
                    'default' => '0.00',
                    'required' => false,
                    'min' => '0',
                    'step' => '0.01',
                    'suffix' => 'kg'
                ],
                'shipping_weight' => [
                    'label' => 'Shipping Weight',
                    'placeholder' => '0.00',
                    'required' => false,
                    'min' => '0',
                    'step' => '0.01',
                    'suffix' => 'kg'
                ],
                'shipping_required' => [
                    'type' => 'checkbox',
                    'label' => 'Shipping Required',
                    'required' => false,
                    'default' => true
                ],
                'shipping_taxable' => [
                    'type' => 'checkbox',
                    'label' => 'Shipping Taxable',
                    'required' => false,
                    'default' => true
                ],
                'shipping_class_id' => [
                    'type' => 'number',
                    'label' => 'Shipping Class ID',
                    'placeholder' => '0',
                    'required' => false,
                    'default' => '0',
                    'min' => '0',
                    'step' => '1'
                ]
            ]
        ];

        $other = [
            'group' => 'Other',
            'fields' => [
                'description' => [
                    'type' => 'textarea',
                    'label' => 'Description',
                    'placeholder' => 'Enter product description',
                    'required' => false,
                    'attributes' => ['rows' => 4]
                ],
                'short_description' => [
                    'type' => 'textarea',
                    'label' => 'Short Description',
                    'placeholder' => 'Enter brief description',
                    'required' => false,
                    'attributes' => ['rows' => 2]
                ],
                'tags' => [
                    'type' => 'tags',
                    'label' => 'Tags',
                    'placeholder' => 'Enter tags separated by commas',
                    'required' => false
                ],
                'attributes' => [
                    'type' => 'array',
                    'label' => 'Attributes',
                    'required' => false
                ],
                'variations' => [
                    'type' => 'array',
                    'label' => 'Variations',
                    'required' => false
                ],
                'related_ids' => [
                    'type' => 'array',
                    'label' => 'Related Ids',
                    'required' => false
                ],
                'upsell_ids' => [
                    'type' => 'array',
                    'label' => 'Upsell Ids',
                    'required' => false
                ],
                'cross_sell_ids' => [
                    'type' => 'array',
                    'label' => 'Cross Sell Ids',
                    'required' => false
                ],
                'meta_data' => [
                    'type' => 'array',
                    'label' => 'Meta Data',
                    'required' => false
                ]
            ]
        ];

        return [
            $general,
            $priceInventory,
            $tax,
            $shipping,
            $other
        ];
    }

    /**
     * Get hardcoded defaults for known fields (fallback method).
     * These should match the database migration defaults.
     *
     * @return array
     */
    public function getHardcodedDefaults(): array
    {
        return [
            'type' => 'simple',
            'publication_status' => 'draft',
            'active' => true,
            'brand' => 'ASENSAR',
            'cost' => 0.00,
            'mrp' => 0.00,
            'price' => 0.00,
            'sale_price' => 0.00,
            'taxable' => true,
            'tax_rate' => 18.00,
            'tax_inclusive' => true,
            'stock_track' => false,
            'stock_quantity' => 0,
            'stock_low_threshold' => 0,
            'shipping_required' => true,
            'shipping_taxable' => true,
        ];
    }
}
