<?php

namespace App\Http\Requests;

use App\Enums\GoldKarat;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('stock_items');
        if (is_string($raw) && json_validate($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $this->merge(['stock_items' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        $routeItem = $this->route('item');
        if ($routeItem instanceof Product) {
            $productId = $routeItem->id;
        } elseif (is_numeric($routeItem)) {
            $productId = (int) $routeItem;
        } elseif (is_string($routeItem) && $routeItem !== '') {
            $productId = Product::where('slug', $routeItem)->value('id') ?? $this->id;
        } else {
            $productId = $this->id;
        }

        return [
            'name' => ['required', 'string', 'min:5', 'max:128', 'unique:products,name,'.$productId],
            'sku' => ['nullable', 'string', 'min:1', 'max:128', 'unique:products,sku,'.$productId],
            'body' => ['nullable', 'string', 'min:5'],
            'excerpt' => ['required', 'string', 'min:5'],
            'active' => ['nullable', 'boolean'],
            'meta' => ['nullable'],
            'category_id' => ['required', 'exists:categories,id'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'labor_charge_1' => ['nullable', 'numeric', 'min:0'],
            'labor_charge_2' => ['nullable', 'numeric', 'min:0'],
            'labor_charge_3' => ['nullable', 'numeric', 'min:0'],
            'profit' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'min_stock_level' => ['nullable', 'integer', 'min:0'],
            'target_group' => ['nullable', 'string', 'in:men,women,children,unisex'],
            'metal_type' => ['nullable', 'string', 'in:gold,silver'],
            'karat' => ['nullable', 'integer', 'in:'.implode(',', GoldKarat::values())],
            'stock_items' => ['nullable', 'array'],
            'stock_items.*.supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'image.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'canonical' => ['nullable', 'url', 'min:5', 'max:128'],
            'plating_colors' => ['nullable', 'array'],
            'plating_colors.*' => ['string', 'in:'.implode(',', array_keys(Product::platingColorOptions()))],
            'stones' => ['nullable', 'array'],
            'stones.*' => ['string', 'in:'.implode(',', array_keys(Product::stoneOptions()))],
            'accessories' => ['nullable', 'array'],
            'accessories.*' => ['string'],
            'occasions' => ['nullable', 'array'],
            'occasions.*' => ['string', 'in:'.implode(',', array_keys(Product::occasionOptions()))],
        ];
    }

    public function attributes(): array
    {
        return [
            'stock_items.*.supplier_id' => __('Supplier'),
        ];
    }
}
