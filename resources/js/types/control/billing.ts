export type PlanStatus = 'draft' | 'active' | 'archived';
export type PlanVisibility = 'public' | 'private';
export type BillingPeriod = 'monthly' | 'quarterly' | 'yearly';

export interface SubscriberCounts {
    monthly: number;
    quarterly: number;
    yearly: number;
    total: number;
}

export interface PlanFeature {
    id: number;
    name: string;
    description: string | null;
}

export interface PricingHistory {
    id: number;
    period: BillingPeriod;
    old_amount: string;
    new_amount: string;
    reason: string | null;
    changed_by: string | null;
    razorpay_old_plan_id: string | null;
    razorpay_new_plan_id: string | null;
    created_at: string;
}

export interface CatalogPlan {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    status: PlanStatus;
    visibility: PlanVisibility;
    sort_order: number;
    price_monthly: string;
    price_quarterly: string;
    price_yearly: string;
    is_active: boolean;
    archived_at: string | null;
    metadata: Record<string, unknown> | null;
    notes: string | null;
    subscriber_counts: SubscriberCounts;
    features: PlanFeature[];
    pricing_histories?: PricingHistory[];
    audit_logs?: CatalogAuditEntry[];
    missing_pricings: BillingPeriod[];
    updated_at: string;
    can_be_deleted?: boolean;
}

export interface CustomerImpact {
    affected_count: number;
    period: BillingPeriod;
    current_price: string;
    new_price: string;
    will_notify: boolean;
    grandfathering_note: string;
}

export interface BillingCatalogStats {
    active_plans: number;
    draft_plans: number;
    archived_plans: number;
    total_subscribers: number;
    monthly_subscribers: number;
    quarterly_subscribers: number;
    yearly_subscribers: number;
}

export interface CatalogAuditEntry {
    id: number;
    action: string;
    entity_type: string;
    entity_id: string;
    before: Record<string, unknown> | null;
    after: Record<string, unknown> | null;
    performed_by: string | null;
    ip_address: string | null;
    created_at: string;
}
