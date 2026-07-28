<?php

namespace App\Http\Requests\Control\Billing;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plan = SubscriptionPlan::query()->find($this->route('id'));

        return $plan !== null && ($this->user()?->can('update', $plan) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'slug' => ['sometimes', 'required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/', Rule::unique('subscription_plans', 'slug')->ignore($this->route('id'))],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'required', 'in:draft,active'],
            'visibility' => ['sometimes', 'required', 'in:public,private'],
            'sort_order' => ['sometimes', 'required', 'integer', 'min:0', 'max:9999'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
