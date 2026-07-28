[/] 1. Migrations (3 new migrations)
[/] Create 2026_07_28_000001_extend_subscription_plans_for_catalog
[/] Create 2026_07_28_000002_create_plan_pricing_histories_table
[/] Create 2026_07_28_000003_create_billing_catalog_audit_logs_table

[ ] 2. Models + Enums
[ ] Modify SubscriptionPlan model
[ ] Create PlanPricingHistory model
[ ] Create BillingCatalogAuditLog model
[ ] Create CatalogPlanStatus enum
[ ] Create CatalogPlanVisibility enum

[ ] 3. CommunicationTemplate
[ ] Modify CommunicationTemplate.php to add PricingChangeNotice

[ ] 4. Events
[ ] Create PlanCreated, PlanUpdated, PlanArchived, PlanRestored, PlanPricingUpdated

[ ] 5. Jobs
[ ] Create SyncRazorpayPlanJob
[ ] Create SendPricingChangeNotificationJob
[ ] Create RecalculateCatalogStatsJob

[ ] 6. DTOs
[ ] Create CreatePlanData, UpdatePlanData, UpdatePlanPricingData, CustomerImpactData

[ ] 7. Services
[ ] Create BillingQueryService
[ ] Create CatalogQueryService
[ ] Create CustomerImpactService
[ ] Create CatalogSyncService
[ ] Modify BillingOverviewService

[ ] 8. Actions
[ ] Create CreatePlanAction
[ ] Create UpdatePlanAction
[ ] Create UpdatePlanPricingAction
[ ] Create ArchivePlanAction
[ ] Create RestorePlanAction
[ ] Create DeletePlanAction

[ ] 9. Policies + Requests
[ ] Create CatalogPolicy
[ ] Create CreatePlanRequest, UpdatePlanRequest, UpdatePlanPricingRequest

[ ] 10. Controllers
[ ] Create CatalogController, CatalogPricingController

[ ] 11. Routes
[ ] Update routes/control.php

[ ] 12. Frontend Types
[ ] Create billing.ts and update control/index.ts

[ ] 13. Frontend Components
[ ] Create PlanCard, PlanStatusBadge, PricingGrid, PricingEditSheet, ImpactSummary, ConfirmationDialog, PricingVersionTimeline, MissingPricingAlert, PlanEditModal/Edit, SubscriberBreakdownCard, DangerZone

[ ] 14. Frontend Pages
[ ] Create Index.tsx, Show.tsx

[ ] 15. Overview update
[ ] Update BillingOverviewSection.tsx

[ ] 16. Navigation update
[ ] Update navigation.config.ts

[ ] 17. Seeder
[ ] Create CatalogPlanSeeder

[ ] 18. Verification