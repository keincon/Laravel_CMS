# Users, Roles, Permissions

## Purpose / 目的

English: Authentication and capability-based authorization (Spatie + Policies).

日本語: Spatie と Policy による認証・権限管理。

## User fields

username, email, password, display_name, first/last name, nickname, bio, locale, timezone, avatar, status  
2FA-ready: `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`

## Roles

Administrator, Editor, Author, Contributor, Subscriber

## Rule

Never rely only on hiding UI buttons — always authorize via Gates/Policies.
