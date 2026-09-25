<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductSaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
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
            'stock_items' => ['nullable', 'string'],
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
}
