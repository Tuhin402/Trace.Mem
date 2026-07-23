# CI/CD Pipeline

We employ a robust, automated CI/CD pipeline to ensure that TraceMem remains stable in production.

## Deployment Architecture

```mermaid
graph TD
    %% Environments
    subgraph Local Environment
        A[Developer Commits Code] --> B{Run Local Tests}
        B -->|Pass| C[Push to GitHub main]
        B -->|Fail| D[Fix Code]
        D --> A
    end

    subgraph GitHub Actions
        C --> E[Checkout Source Code]
        E --> F[Setup PHP & Node.js]
        F --> G[Install Dependencies]
        G --> H{Run Automated Test Suite}
        H -->|Pass| I[Trigger Deployment Webhook]
        H -->|Fail| J[Notify Developer]
    end

    subgraph Production Server
        I --> K[git pull origin main]
        K --> L[composer install --no-dev --optimize-autoloader]
        L --> M[npm install && npm run build]
        M --> N[php artisan migrate --force]
        N --> O[php artisan config:cache]
        O --> P[php artisan route:cache]
        P --> Q[php artisan view:cache]
        Q --> R[php artisan queue:restart]
        R --> S[Application Deployed Successfully]
    end
```

## Testing Suite

The CI pipeline automatically runs our comprehensive test suite, executing all Feature and Unit tests before allowing a deployment. For instance, the Workspace isolation tests ensure that:
- Individual accounts get 403s on workspace endpoints.
- API keys are permanently locked to their workspace.
- The `WorkspaceContextService` correctly prevents cross-tenant data leaks.
