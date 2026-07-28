<?php

namespace App\Http\Requests\Control\Billing;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Http\FormRequest;

class CreatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SubscriptionPlan::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/', 'unique:subscription_plans,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:draft,active'],
            'visibility' => ['required', 'in:public,private'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_quarterly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
