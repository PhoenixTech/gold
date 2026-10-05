<?php

namespace App\Http\Requests;

use App\Enums\CampaignStatus;
use App\Enums\MetalType;
use App\Enums\Occasion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CampaignSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * The Vue multi-select components submit a JSON string in a single hidden
     * input, so the set fields have to be decoded before validation runs. This
     * mirrors how `getCategoriesSet()` reads the CATEGORY_SET / TAG_SET settings.
     */
    protected function prepareForValidation(): void
    {
        foreach (['included_products', 'excluded_products', 'occasions', 'metal_scope'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $this->merge([$field => is_array($decoded) ? $decoded : []]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:191', 'alpha_dash'],
            'subtitle' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'badge_text' => ['nullable', 'string', 'max:60'],

            'status' => ['required', 'in:'.implode(',', CampaignStatus::values())],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:12'],

            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            'occasions' => ['nullable', 'array'],
            'occasions.*' => ['string', 'in:'.implode(',', Occasion::values())],

            'metal_scope' => ['nullable', 'array'],
            'metal_scope.*' => ['string', 'in:'.implode(',', MetalType::values())],

            'canonical' => ['nullable', 'string', 'max:2048'],

            'included_products' => ['nullable', 'array'],
            'included_products.*' => ['integer', 'exists:products,id'],
            'excluded_products' => ['nullable', 'array'],
            'excluded_products.*' => ['integer', 'exists:products,id'],

            'image' => ['nullable', 'image', 'max:4096'],
            'mobile_image' => ['nullable', 'image', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('Campaign name'),
            'starts_at' => __('Start date'),
            'ends_at' => __('End date'),
            'occasions' => __('Occasions'),
            'metal_scope' => __('Tabs'),
            'included_products' => __('Included products'),
            'excluded_products' => __('Excluded products'),
        ];
    }

    /**
     * A product can only hold one role per campaign, and the pivot enforces it
     * with a unique index. Catching it here turns a 500 into a field error.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $included = (array) $this->input('included_products', []);
            $excluded = (array) $this->input('excluded_products', []);
            $overlap = array_intersect($included, $excluded);

            if ($overlap !== []) {
                $validator->errors()->add(
                    'excluded_products',
                    __('These products are both included and excluded. A product can only have one role.')
                );
            }
        });
    }
}
