<?php

namespace App\Models;

use App\Attributes\ApiResource;
use App\Traits\HandlesDatabaseDefaults;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ApiResource(uri: 'products', apiPrefix: 'api', version: 'v1')]
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HandlesDatabaseDefaults, HasFactory;

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
     * Get the searchable fields for API search functionality.
     */
    public function getSearchableFields(): array
    {
        return [
            'name',
            'description',
            'short_description',
            'sku',
            'barcode',
            'brand',
        ];
    }

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
        if (! $this->stock_track) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    /**
     * Check if the product is low in stock.
     */
    public function isLowStock(): bool
    {
        if (! $this->stock_track) {
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
     */
    public function getIndexColumns(): array
    {
        return [
            [
                'field' => 'name',
                'label' => 'Product Name',
                'sortable' => true,
                'clickable' => true,
                'search' => true,
            ],
            [
                'field' => 'sku',
                'label' => 'SKU',
                'sortable' => true,
                'search' => true,
            ],
            [
                'field' => 'type',
                'label' => 'Type',
                'sortable' => true,
                'search' => true,
            ],
            [
                'field' => 'cost',
                'label' => 'Cost',
                'sortable' => true,
                'format' => 'currency',
                'align' => 'right',
            ],
            [
                'field' => 'price',
                'label' => 'Price',
                'sortable' => true,
                'format' => 'currency',
                'align' => 'right',
            ],
            [
                'field' => 'mrp',
                'label' => 'MRP',
                'sortable' => true,
                'format' => 'currency',
                'align' => 'right',
            ],
            [
                'field' => 'stock_quantity',
                'label' => 'Stock',
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
                    'field' => 'name',
                    'type' => 'string',
                    'label' => 'Product Name',
                    'placeholder' => 'Enter product name',
                    'required' => true,
                    'maxLength' => 255,
                ],
                [
                    'field' => 'slug',
                    'type' => 'string',
                    'label' => 'URL Slug',
                    'placeholder' => 'auto-generated-from-name',
                    'required' => true,
                    'maxLength' => 255,
                ],
                [
                    'field' => 'type',
                    'type' => 'select',
                    'label' => 'Product Type',
                    'options' => [
                        ['value' => 'simple', 'label' => 'Simple Product'],
                        ['value' => 'variable', 'label' => 'Variable Product'],
                        ['value' => 'grouped', 'label' => 'Grouped Product'],
                        ['value' => 'external', 'label' => 'External Product'],
                    ],
                    'required' => true,
                    'default' => 'simple',
                ],
                [
                    'field' => 'publication_status',
                    'type' => 'select',
                    'label' => 'Publication Status',
                    'options' => [
                        ['value' => 'draft', 'label' => 'Draft'],
                        ['value' => 'published', 'label' => 'Published'],
                        ['value' => 'discontinued', 'label' => 'Discontinued'],
                        ['value' => 'private', 'label' => 'Private'],
                    ],
                    'required' => true,
                    'default' => 'draft',
                ],
                [
                    'field' => 'sku',
                    'type' => 'string',
                    'label' => 'SKU',
                    'placeholder' => 'Enter SKU code',
                    'required' => false,
                    'maxLength' => 100,
                ],
                [
                    'field' => 'barcode',
                    'type' => 'string',
                    'label' => 'Barcode',
                    'placeholder' => 'Enter barcode',
                    'required' => false,
                    'maxLength' => 100,
                ],
                [
                    'field' => 'brand',
                    'type' => 'string',
                    'label' => 'Brand',
                    'placeholder' => 'Enter brand name',
                    'default' => 'ASENSAR',
                    'required' => false,
                ],
                [
                    'field' => 'unit',
                    'type' => 'select',
                    'label' => 'Unit',
                    'options' => [
                        ['value' => 'nos', 'label' => 'Nos'],
                        ['value' => 'piece', 'label' => 'Piece'],
                        ['value' => 'kg', 'label' => 'Kilogram'],
                        ['value' => 'gram', 'label' => 'Gram'],
                        ['value' => 'liter', 'label' => 'Liter'],
                        ['value' => 'meter', 'label' => 'Meter'],
                    ],
                    'required' => false,
                    'default' => 'nos',
                ],
                [
                    'field' => 'categories',
                    'type' => 'multiselect',
                    'label' => 'Categories',
                    'required' => false,
                    'options' => [],
                ],
                [
                    'field' => 'image',
                    'type' => 'file',
                    'label' => 'Featured Image',
                    'required' => false,
                    'accept' => 'image/*',
                ],
                [
                    'field' => 'images',
                    'type' => 'file',
                    'label' => 'Product Images',
                    'required' => false,
                    'multiple' => true,
                    'accept' => 'image/*',
                ],
                [
                    'field' => 'external_url',
                    'type' => 'string',
                    'label' => 'External URL',
                    'placeholder' => 'https://asensar.com',
                    'required' => false,
                    'maxLength' => 500,
                ],
            ],
        ];

        $priceInventory = [
            'group' => 'Price & Inventory',
            'fields' => [
                [
                    'field' => 'cost',
                    'type' => 'decimal',
                    'label' => 'Cost Price',
                    'placeholder' => '0.00',
                    'required' => false,
                    'default' => '0.00',
                    'min' => 0,
                    'prefix' => '₹',
                ],
                [
                    'field' => 'mrp',
                    'type' => 'decimal',
                    'label' => 'MRP',
                    'placeholder' => '0.00',
                    'default' => '0.00',
                    'required' => false,
                    'min' => 0,
                    'prefix' => '₹',
                ],
                [
                    'field' => 'price',
                    'type' => 'decimal',
                    'label' => 'Regular Price',
                    'placeholder' => '0.00',
                    'required' => true,
                    'default' => '0.00',
                    'min' => 0,
                    'prefix' => '₹',
                ],
                [
                    'field' => 'sale_price',
                    'type' => 'decimal',
                    'label' => 'Sale Price',
                    'placeholder' => '0.00',
                    'required' => false,
                    'default' => '0.00',
                    'min' => 0,
                    'prefix' => '₹',
                ],
                [
                    'field' => 'stock_track',
                    'type' => 'checkbox',
                    'label' => 'Track Stock',
                    'required' => false,
                    'default' => false,
                ],
                [
                    'field' => 'stock_quantity',
                    'type' => 'number',
                    'label' => 'Stock Quantity',
                    'placeholder' => '0',
                    'default' => '0',
                    'required' => false,
                    'min' => '0',
                    'step' => '1',
                ],
                [
                    'field' => 'stock_low_threshold',
                    'type' => 'number',
                    'label' => 'Low Stock Threshold',
                    'placeholder' => '5',
                    'required' => false,
                    'default' => '0',
                    'min' => '0',
                    'step' => '1',
                ],
            ],
        ];

        $tax = [
            'group' => 'TAX',
            'fields' => [
                [
                    'field' => 'taxable',
                    'type' => 'checkbox',
                    'label' => 'Taxable',
                    'required' => false,
                    'default' => true,
                ],
                [
                    'field' => 'tax_hsn_code',
                    'type' => 'string',
                    'label' => 'HSN Code',
                    'placeholder' => 'Enter HSN code',
                    'required' => false,
                    'maxLength' => 20,
                ],
                [
                    'field' => 'tax_rate',
                    'type' => 'number',
                    'label' => 'Tax Rate (%)',
                    'placeholder' => '18.00',
                    'default' => '18.00',
                    'required' => false,
                    'min' => '0',
                    'max' => '100',
                    'step' => '0.01',
                    'suffix' => '%',
                ],
                [
                    'field' => 'tax_inclusive',
                    'type' => 'checkbox',
                    'label' => 'Tax Inclusive',
                    'required' => false,
                    'default' => true,
                ],
            ],
        ];

        $shipping = [
            'group' => 'Shipping',
            'fields' => [
                [
                    'field' => 'length',
                    'type' => 'decimal',
                    'label' => 'Length',
                    'placeholder' => '0.00',
                    'default' => '0.00',
                    'required' => false,
                    'min' => '0',
                    'step' => '0.01',
                    'suffix' => 'cm',
                ],
                [
                    'field' => 'width',
                    'type' => 'decimal',
                    'label' => 'Width',
                    'placeholder' => '0.00',
                    'default' => '0.00',
                    'required' => false,
                    'min' => '0',
                    'step' => '0.01',
                    'suffix' => 'cm',
                ],
                [
                    'field' => 'height',
                    'type' => 'decimal',
                    'label' => 'Height',
                    'placeholder' => '0.00',
                    'default' => '0.00',
                    'required' => false,
                    'min' => '0',
                    'step' => '0.01',
                    'suffix' => 'cm',
                ],
                [
                    'field' => 'weight',
                    'type' => 'decimal',
                    'label' => 'Weight',
                    'placeholder' => '0.00',
                    'default' => '0.00',
                    'required' => false,
                    'min' => '0',
                    'step' => '0.01',
                    'suffix' => 'kg',
                ],
                [
                    'field' => 'shipping_weight',
                    'type' => 'decimal',
                    'label' => 'Shipping Weight',
                    'placeholder' => '0.00',
                    'required' => false,
                    'min' => '0',
                    'step' => '0.01',
                    'suffix' => 'kg',
                ],
                [
                    'field' => 'shipping_required',
                    'type' => 'checkbox',
                    'label' => 'Shipping Required',
                    'required' => false,
                    'default' => true,
                ],
                [
                    'field' => 'shipping_taxable',
                    'type' => 'checkbox',
                    'label' => 'Shipping Taxable',
                    'required' => false,
                    'default' => true,
                ],
                [
                    'field' => 'shipping_class_id',
                    'type' => 'number',
                    'label' => 'Shipping Class ID',
                    'placeholder' => '0',
                    'required' => false,
                    'default' => '0',
                    'min' => '0',
                    'step' => '1',
                ],
            ],
        ];

        $other = [
            'group' => 'Other',
            'fields' => [
                [
                    'field' => 'description',
                    'type' => 'textarea',
                    'label' => 'Description',
                    'placeholder' => 'Enter product description',
                    'required' => false,
                    'attributes' => ['rows' => 4],
                ],
                [
                    'field' => 'short_description',
                    'type' => 'textarea',
                    'label' => 'Short Description',
                    'placeholder' => 'Enter brief description',
                    'required' => false,
                    'attributes' => ['rows' => 2],
                ],
                [
                    'field' => 'tags',
                    'type' => 'tags',
                    'label' => 'Tags',
                    'placeholder' => 'Enter tags separated by commas',
                    'required' => false,
                ],
                [
                    'field' => 'attributes',
                    'type' => 'array',
                    'label' => 'Attributes',
                    'required' => false,
                ],
                [
                    'field' => 'variations',
                    'type' => 'array',
                    'label' => 'Variations',
                    'required' => false,
                ],
                [
                    'field' => 'related_ids',
                    'type' => 'array',
                    'label' => 'Related Ids',
                    'required' => false,
                ],
                [
                    'field' => 'upsell_ids',
                    'type' => 'array',
                    'label' => 'Upsell Ids',
                    'required' => false,
                ],
                [
                    'field' => 'cross_sell_ids',
                    'type' => 'array',
                    'label' => 'Cross Sell Ids',
                    'required' => false,
                ],
                [
                    'field' => 'meta_data',
                    'type' => 'array',
                    'label' => 'Meta Data',
                    'required' => false,
                ],
            ],
        ];

        return [
            $general,
            $priceInventory,
            $tax,
            $shipping,
            $other,
        ];
    }

    /**
     * Get hardcoded defaults for known fields (fallback method).
     * These should match the database migration defaults.
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

    /**
     * Apply database defaults to the given data.
     * Also handles slug generation if not provided.
     */
    public function applyDatabaseDefaults(array $data, bool $isUpdate = false): array
    {
        $defaults = $this->getDatabaseDefaults();

        if (! $isUpdate) {
            // For create operations, apply defaults for missing fields
            foreach ($defaults as $field => $value) {
                if (! array_key_exists($field, $data)) {
                    $data[$field] = $value;
                }
            }
        } else {
            // For update operations, only apply defaults for explicitly null values
            foreach ($defaults as $field => $value) {
                if (array_key_exists($field, $data) && $data[$field] === null) {
                    $data[$field] = $value;
                }
            }
        }

        // Generate slug if not provided and name is available
        if ((! isset($data['slug']) || empty($data['slug'])) && isset($data['name'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        return $data;
    }

    /**
     * Generate a unique slug for the product.
     */
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = \Illuminate\Support\Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        // Check if slug already exists
        while (static::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
