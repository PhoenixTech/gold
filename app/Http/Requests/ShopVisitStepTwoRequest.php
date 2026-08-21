<?php

namespace App\Http\Requests;

use App\Models\ShopVisit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ShopVisitStepTwoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isVisitor();
    }

    protected function prepareForValidation(): void
    {
        $mall = $this->input('mall');
        if ($mall === '__other__') {
            $mall = trim((string) $this->input('mall_other', ''));
        }

        $this->merge([
            'mall' => $mall,
            'categories' => array_values(array_filter((array) $this->input('categories', []))),
            'work_styles' => array_values(array_filter((array) $this->input('work_styles', []))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', Rule::in(array_keys(ShopVisit::CATEGORIES))],
            'work_styles' => ['nullable', 'array'],
            'work_styles.*' => ['string', Rule::in(array_keys(ShopVisit::WORK_STYLES))],
            'state_id' => ['required', 'exists:states,id'],
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where('state_id', $this->input('state_id')),
            ],
            'mall' => ['required', 'string', 'max:191'],
            'mall_other' => ['nullable', 'string', 'max:191'],
            'address' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'state_id.required' => __('Province is required.'),
            'city_id.required' => __('City is required.'),
            'city_id.exists' => __('Choose a city that belongs to the selected province.'),
            'mall.required' => __('Shopping mall is required.'),
            'address.required' => __('Exact address is required.'),
            'address.min' => __('Exact address is required.'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $categories = $this->input('categories', []);
            $workStyles = $this->input('work_styles', []);
            if ($categories === [] && $workStyles === []) {
                $validator->errors()->add(
                    'categories',
                    __('Select at least one shop category or work style.')
                );
            }
        });
    }
}
