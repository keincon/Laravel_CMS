# Architecture

See the canonical specifications:

- [01-Architecture.md](../Specifications/01-Architecture.md)
- [02-Database.md](../Specifications/02-Database.md)
- [System diagrams](../Specifications/index.html)

## Runtime stack

```
Browser
  |
  v
Middleware (install, redirects, security headers)
  |
  v
Controller → FormRequest → Policy
  |
  v
Service (Content / Settings / Search / Media)
  |
  v
PostgreSQL + Redis
```

## Modules

Enabled modules under `modules/{Name}/module.json` are registered at boot via `ModuleManager`.
