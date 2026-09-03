# Plugin / Module System

Modules live under `modules/{Name}/` with `module.json` + optional ServiceProvider.

## Admin

`/admin/modules` (requires `manage_settings`) lists discovered modules and toggles `enabled` in `module.json`.

Enabled providers are registered at boot via `ModuleManager::registerEnabled()`.

## Rules

- Never execute uploaded PHP unless an administrator explicitly enables the module
- Modules may register hooks, blocks, routes, and settings
