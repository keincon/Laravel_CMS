# Security

CSRF, XSS sanitization, SQL injection prevention via Eloquent, Policies, rate limiting, secure uploads, mass-assignment protection, password hashing, audit logs, security headers.

## Admin authorization

All mutating/admin content routes require Spatie `permission:` middleware (not auth alone):

| Area | Permission |
|------|------------|
| Posts | `manage_posts` |
| Pages | `manage_pages` |
| Categories/Tags | `manage_categories` |
| Media | `manage_media` |
| Menus | `manage_menus` |
| Comments | `manage_comments` |
| Settings | `manage_settings` |
| Appearance/Headers/Footers | `manage_themes` |
| Users | `manage_users` |
| Roles | `manage_roles` |

Dashboard, profile, and personal API tokens remain available to any authenticated user.

Generic Content API uses `ContentPolicy` for create/update/delete/publish.

## Production hardening checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] HTTPS termination at reverse proxy; set `TRUSTED_PROXIES` (default `*` trusts all — tighten to LB CIDRs in multi-tenant hosts)
- [ ] `ForceHttps` middleware redirects plain HTTP → HTTPS when `APP_ENV=production`
- [ ] `SecurityHeaders` middleware enabled on web + API
- [ ] API throttled via `throttle:api` (`CMS_API_RATE_LIMIT`, default 120/min)
- [ ] Comment submissions throttled (`throttle:10,1`)
- [ ] Sanctum tokens for write APIs; never expose secrets in JSON envelopes
- [ ] Media uploads validated with server-side MIME (`finfo`); SVG blocked
- [ ] Redis for cache/queue in Docker Compose production profile
- [ ] Run `php artisan config:cache` and `route:cache` after deploy
- [ ] Rotate `APP_KEY` only with a planned re-encrypt of encrypted columns (2FA secrets)

## Trusted proxies

LaravelPress trusts `X-Forwarded-*` headers so `Request::secure()` reflects the client-facing scheme behind nginx/ALB. Configure:

```env
TRUSTED_PROXIES=*
# or comma-separated proxy IPs / CIDRs
```
