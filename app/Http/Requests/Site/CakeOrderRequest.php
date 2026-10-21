<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class CakeOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-()]{9,20}$/'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/'],
            'items.*.name' => ['required', 'string', 'max:120'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.size' => ['nullable', Rule::in(array_keys(trans('koba.order.sizes')))],
            'branch' => ['required', Rule::in(array_keys(trans('koba.locations.branches')))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Cakes need a size; a "something else" cake needs the request written out.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $cakes = [...array_keys(trans('koba.cakes')), 'custom'];
            foreach ((array) $this->input('items', []) as $i => $item) {
                $slug = is_array($item) ? ($item['slug'] ?? null) : null;
                if (in_array($slug, $cakes, true) && empty($item['size'])) {
                    $validator->errors()->add("items.$i.size", __('koba.order.size_required', ['item' => $item['name'] ?? $slug]));
                }
                if ($slug === 'custom' && blank($this->input('notes'))) {
                    $validator->errors()->add('notes', __('koba.ui.required', ['field' => trans('koba.order.fields.notes')]));
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => __('koba.ui.required', ['field' => ':Attribute']),
            'required_if' => __('koba.ui.required', ['field' => ':Attribute']),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return trans('koba.order.fields');
    }
}
