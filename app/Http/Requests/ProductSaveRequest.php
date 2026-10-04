<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorInstance;

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
            'karat' => ['nullable', 'integer', 'in:'.implode(',', \App\Enums\GoldKarat::values())],
            'stock_items' => ['nullable', 'json'],
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

    public function withValidator(ValidatorInstance $validator): void
    {
        $validator->after(function (ValidatorInstance $validator): void {
            $stockItems = json_decode((string) $this->input('stock_items'), true);
            if (! is_array($stockItems)) {
                return;
            }

            $rules = [];
            $attributes = [];
            foreach ($stockItems as $index => $stockItem) {
                if (! is_array($stockItem) || ! isset($stockItem['supplier_id']) || $stockItem['supplier_id'] === '') {
                    continue;
                }

                $rules["{$index}.supplier_id"] = [
                    'bail',
                    'integer',
                    Rule::exists('suppliers', 'id'),
                ];
                $attributes["{$index}.supplier_id"] = __('Supplier');
            }

            if ($rules === []) {
                return;
            }

            $supplierValidation = Validator::make($stockItems, $rules, [], $attributes);
            foreach ($supplierValidation->errors()->messages() as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add("stock_items.{$field}", $message);
                }
            }
        });
    }

}
