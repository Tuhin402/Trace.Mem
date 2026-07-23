# Workspace Architecture

TraceMem uses a multi-tenant, workspace-scoped isolation architecture to guarantee strict boundaries between different instances or customers within your application.

## Hierarchy

```
Tenant (User or Company account)
    └── Subscription (Razorpay — Tenant-level, not Workspace-level)
            └── Workspaces (1 for Individual · 1..N for Company)
                    ├── Members (Roles: Owner, Admin, Developer, Member, Viewer)
                    ├── Invitations (Secure email links for joining)
                    ├── API Keys (scoped per workspace)
                    └── Memories (scoped per workspace)
```

## Isolation Rules (Non-negotiable)

| Rule | Detail |
|------|--------|
| **Billing is Tenant-level** | A subscription belongs to the Tenant, never to a Workspace. |
| **Default workspace is locked** | Cannot be deleted, archived, renamed to empty, or transferred. |
| **Auto test key only** | On workspace creation, exactly **one test key** is auto-created. No live key is created automatically. |
| **Live keys are manual** | The Owner explicitly generates a live key after buying a plan. |
| **Slug is immutable** | `teams.slug` is generated once and never changes. |
| **Owner from membership** | The Owner is derived from `team_members.role = 'owner'` — there is no `owner_user_id` column. |
| **workspace_id is immutable** | Once set on an API key or Memory, it cannot be changed. |
| **Individual accounts** | Always have exactly one workspace (the default). There is no workspace management UI. |
| **Company accounts** | Can have multiple workspaces. Full management UI is available at `/workspaces`. |

## Key Services

- **`WorkspaceContextService`**: Singleton source of truth for resolving the current workspace based on the request context.
- **`WorkspaceService`**: Handles the creation, renaming, and archiving of workspaces; manages members; and auto-creates the initial test API key.
- **`WorkspaceAuditService`**: Maintains an immutable audit trail for all workspace events.
- **`ApiKeyAuthMiddleware`**: Validates the API key and resolves `tenant_id`, `user_id`, `workspace_id`, and `environment` into a highly secure `resolved_scope`.
- **`TeamPolicy`**: Authorizes workspace actions. Individual users receive a 403 Forbidden for all workspace management routes.
