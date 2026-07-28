<?php

namespace App\Http\Requests\Control\Billing;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plan = SubscriptionPlan::query()->find($this->input('plan_id'));

        return $plan !== null && ($this->user()?->can('update', $plan) ?? false);
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'period' => ['required', 'in:monthly,quarterly,yearly'],
            'new_amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'notify_subscribers' => ['boolean'],
        ];
    }
}
