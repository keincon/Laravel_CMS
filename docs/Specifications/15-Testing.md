# Testing

## PHPUnit (Docker)

```bash
docker compose exec -T app php artisan test
```

Unit: status transitions, hooks, blocks, legacy retirement  
Feature: content CRUD, policies, packs/migration, admin auth, API  

## Playwright browser / e2e

```bash
# Ensure Docker app is up, then seed a deterministic admin:
docker compose exec -T app php artisan laravelpress:e2e-seed

# Discover host port:
docker compose port app 8000

# Install browsers once, then run:
npx playwright install chromium
E2E_BASE_URL=http://127.0.0.1:HOST_PORT npm run test:e2e
```

Coverage:

- Public smoke (`/`, `/blog`, `/api/wp/v2/types`)
- Admin login → create Content with Gutenberg editor → publish → public render

Env overrides: `E2E_BASE_URL`, `E2E_EMAIL`, `E2E_PASSWORD` (defaults `e2e@example.com` / `password`).
