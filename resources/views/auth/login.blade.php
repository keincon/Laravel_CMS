@php
    $ui = app(\App\Services\UIFrameworkService::class);
    $siteName = \App\Models\CmsSetting::getValue('site_name') ?: config('cms.name', config('app.name'));
    $siteDescription = \App\Models\CmsSetting::getValue('site_description') ?: __('auth.sign_in_continue');
    $logoMediaId = \App\Models\CmsSetting::getValue('site_logo_media_id');
    $logoMedia = $logoMediaId ? \App\Models\Media::query()->find($logoMediaId) : null;
    $logoHref = $logoMedia ? '/storage/'.ltrim((string) $logoMedia->path, '/') : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('auth.sign_in_title', ['site' => $siteName]) }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:600,700|source-sans-3:400,500,600&display=swap" rel="stylesheet">
    @foreach ($ui->stylesheetUrls() as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
    <x-theme />
    <x-favicon />
    <style>
        :root, [data-theme="light"] {
            --login-ink: #102a43;
            --login-muted: #486581;
            --login-panel: rgba(255, 255, 255, 0.92);
            --login-line: rgba(16, 42, 67, 0.12);
            --login-accent: var(--color-primary, #2563eb);
            --login-glow: rgba(37, 99, 235, 0.18);
            --login-danger-bg: #fef2f2;
            --login-danger-text: #991b1b;
            --login-danger-border: #fecaca;
            color-scheme: light;
        }
        [data-theme="dark"] {
            --login-ink: #e8eef5;
            --login-muted: #9fb3c8;
            --login-panel: rgba(17, 24, 39, 0.92);
            --login-line: rgba(232, 238, 245, 0.12);
            --login-glow: rgba(56, 189, 248, 0.16);
            --login-danger-bg: #450a0a;
            --login-danger-text: #fecaca;
            --login-danger-border: #7f1d1d;
            color-scheme: dark;
        }
        * { box-sizing: border-box; }
        body.login-body {
            margin: 0;
            min-height: 100vh;
            font-family: "Source Sans 3", "Segoe UI", sans-serif;
            color: var(--login-ink);
            background:
                radial-gradient(900px 480px at 12% -8%, var(--login-glow), transparent 55%),
                radial-gradient(700px 420px at 100% 0%, rgba(14, 165, 233, 0.12), transparent 50%),
                linear-gradient(165deg, var(--color-background, #f4f7fb) 0%, color-mix(in srgb, var(--color-background, #f4f7fb) 70%, #d9e2ec) 100%);
            display: grid;
            grid-template-rows: auto 1fr auto;
        }
        .login-top {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: .65rem;
            padding: 1rem 1.25rem;
        }
        .visually-hidden {
            position: absolute !important;
            width: 1px; height: 1px;
            padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0, 0, 0, 0);
            white-space: nowrap; border: 0;
        }
        .locale-switcher { display: inline-flex; }
        .locale-switcher-select {
            appearance: none;
            background: var(--login-panel);
            color: var(--login-ink);
            border: 1px solid var(--login-line);
            border-radius: 999px;
            padding: .35rem 1.75rem .35rem .7rem;
            font-size: .85rem;
            line-height: 1.3;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23486581' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .55rem center;
        }
        .locale-switcher-select:hover,
        .locale-switcher-select:focus {
            border-color: var(--login-accent);
            outline: none;
        }
        .login-stage {
            display: grid;
            place-items: center;
            padding: 1rem 1.25rem 2.5rem;
        }
        .login-shell { width: min(420px, 100%); }
        .login-brand { text-align: center; margin-bottom: 1.5rem; }
        .login-mark {
            width: 64px; height: 64px; margin: 0 auto .9rem; border-radius: 18px;
            display: grid; place-items: center;
            font-family: Fraunces, Georgia, serif; font-weight: 700; font-size: 1.6rem; color: #fff;
            background: linear-gradient(145deg, var(--login-accent), color-mix(in srgb, var(--login-accent) 55%, #0ea5e9));
            box-shadow: 0 14px 34px var(--login-glow);
        }
        .login-mark-logo {
            width: 64px; height: 64px; margin: 0 auto .9rem; border-radius: 18px;
            object-fit: contain; background: #fff;
            border: 1px solid var(--login-line);
            box-shadow: 0 14px 34px var(--login-glow);
            display: block;
        }
        .login-brand h1 {
            margin: 0; font-family: Fraunces, Georgia, serif;
            font-size: clamp(1.75rem, 4vw, 2.15rem); font-weight: 700;
            letter-spacing: -0.03em; line-height: 1.15;
        }
        .login-brand p { margin: .45rem 0 0; color: var(--login-muted); font-size: .95rem; }
        .login-panel {
            background: var(--login-panel); backdrop-filter: blur(10px);
            border: 1px solid var(--login-line); border-radius: 18px;
            padding: 1.35rem 1.35rem 1.5rem;
            box-shadow: 0 18px 50px rgba(16, 42, 67, 0.08);
        }
        .login-panel h2 {
            margin: 0 0 1rem; font-size: 1.05rem; font-weight: 600; color: var(--login-muted);
        }
        .login-alert {
            display: flex; gap: .65rem; align-items: flex-start;
            margin: 0 0 1rem; padding: .75rem .85rem;
            border-radius: 12px; border: 1px solid var(--login-danger-border);
            background: var(--login-danger-bg); color: var(--login-danger-text);
            font-size: .92rem; line-height: 1.45;
        }
        .login-alert strong { display: block; margin-bottom: .15rem; }
        .login-panel .form-label {
            font-weight: 600; color: var(--login-ink); margin-bottom: .35rem; display: block; font-size: .9rem;
        }
        .login-panel .form-control {
            width: 100%; border-radius: 10px; border: 1px solid var(--login-line);
            padding: .7rem .85rem; background: color-mix(in srgb, var(--color-surface, #fff) 92%, transparent);
            color: var(--login-ink); font: inherit;
        }
        .login-panel .form-control:focus {
            outline: none; border-color: var(--login-accent);
            box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--login-accent) 25%, transparent);
        }
        .login-field { margin-bottom: 1rem; }
        .login-password-wrap { position: relative; }
        .login-password-wrap .form-control { padding-right: 3rem; }
        .login-eye {
            position: absolute; right: .35rem; top: 50%; transform: translateY(-50%);
            border: 0; background: transparent; color: var(--login-muted);
            width: 2.4rem; height: 2.4rem; border-radius: .6rem;
            display: inline-grid; place-items: center; cursor: pointer; font-size: .85rem; font-weight: 700;
        }
        .login-eye:hover, .login-eye:focus-visible {
            color: var(--login-accent); background: color-mix(in srgb, var(--login-accent) 10%, transparent);
            outline: none;
        }
        .login-eye[aria-pressed="true"] { color: var(--login-accent); }
        .login-actions {
            display: flex; align-items: center; justify-content: space-between;
            gap: .75rem; margin-bottom: 1rem; flex-wrap: wrap;
        }
        .login-check {
            display: inline-flex; align-items: center; gap: .45rem;
            color: var(--login-ink); font-size: .92rem; cursor: pointer; user-select: none;
        }
        .login-check input { width: 1rem; height: 1rem; accent-color: var(--login-accent); }
        .login-submit {
            width: 100%; border: 0; border-radius: 12px; padding: .8rem 1rem;
            font-weight: 700; font-size: 1rem; background: var(--login-accent); color: #fff; cursor: pointer;
        }
        .login-submit:hover { filter: brightness(1.05); }
        .login-submit:disabled { opacity: .7; cursor: wait; }
        .login-hint {
            margin: .85rem 0 0; color: var(--login-muted); font-size: .82rem; line-height: 1.45;
        }
        .login-foot {
            text-align: center; padding: 0 1rem 1.25rem; color: var(--login-muted); font-size: .9rem;
        }
        .login-foot a { color: var(--login-accent); text-decoration: none; font-weight: 600; }
        .theme-toggle {
            display: inline-flex; align-items: center; gap: .35rem;
            border: 1px solid var(--login-line); background: var(--login-panel);
            color: var(--login-ink); border-radius: 999px; padding: .35rem .7rem;
            font-size: .85rem; cursor: pointer;
        }
        .theme-toggle[data-theme-active="dark"] .theme-toggle-sun { display: inline; }
        .theme-toggle[data-theme-active="dark"] .theme-toggle-moon { display: none; }
        .theme-toggle[data-theme-active="light"] .theme-toggle-sun { display: none; }
        .theme-toggle[data-theme-active="light"] .theme-toggle-moon { display: inline; }
        @media (max-width: 480px) {
            .login-panel { padding: 1.15rem; border-radius: 14px; }
            .login-brand h1 { font-size: 1.55rem; }
        }
    </style>
</head>
<body class="login-body">
    <div class="login-top">
        <x-locale-switcher />
        <x-theme-toggle />
    </div>

    <main class="login-stage">
        <div class="login-shell">
            <div class="login-brand">
                @if ($logoHref)
                    <img class="login-mark-logo" src="{{ $logoHref }}" alt="{{ $siteName }}">
                @else
                    <div class="login-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($siteName, 0, 1)) }}</div>
                @endif
                <h1>{{ $siteName }}</h1>
                <p>{{ __('auth.sign_in_continue') }}</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="login-panel" autocomplete="on" data-login-form>
                @csrf
                <h2>{{ __('auth.sign_in_account') }}</h2>

                @if (session('error') || $errors->any())
                    <div class="login-alert" role="alert">
                        <div>
                            <strong>{{ __('auth.could_not_sign_in') }}</strong>
                            {{ session('error') ?: $errors->first() }}
                            <div style="margin-top:.35rem;opacity:.9">{{ __('auth.check_credentials') }}</div>
                        </div>
                    </div>
                @endif

                <div class="login-field">
                    <label class="form-label" for="email">{{ __('auth.email') }}</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        class="form-control"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        inputmode="email"
                        placeholder="{{ __('auth.email_placeholder') }}"
                    >
                </div>

                <div class="login-field">
                    <label class="form-label" for="password">{{ __('auth.password') }}</label>
                    <div class="login-password-wrap">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-control"
                            required
                            autocomplete="current-password"
                            placeholder="{{ __('auth.password_placeholder') }}"
                        >
                        <button
                            type="button"
                            class="login-eye"
                            data-password-toggle
                            aria-controls="password"
                            aria-pressed="false"
                            aria-label="{{ __('auth.show_password') }}"
                            title="{{ __('auth.show_password') }}"
                        >{{ __('auth.show') }}</button>
                    </div>
                </div>

                <div class="login-actions">
                    <label class="login-check">
                        <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        <span>{{ __('auth.remember_me') }}</span>
                    </label>
                </div>

                <button class="login-submit" type="submit" data-login-submit>{{ __('auth.log_in') }}</button>
                <p class="login-hint">{{ __('auth.login_hint') }}</p>
            </form>
        </div>
    </main>

    <footer class="login-foot">
        <a href="{{ url('/') }}">{{ __('auth.back_to_website') }}</a>
    </footer>

    <script>
        window.authI18n = {
            show: @json(__('auth.show')),
            hide: @json(__('auth.hide')),
            showPassword: @json(__('auth.show_password')),
            hidePassword: @json(__('auth.hide_password')),
            signingIn: @json(__('auth.signing_in')),
            logIn: @json(__('auth.log_in')),
        };
        (function () {
            var i18n = window.authI18n;
            var toggle = document.querySelector('[data-password-toggle]');
            var input = document.getElementById('password');
            if (toggle && input) {
                toggle.addEventListener('click', function () {
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
                    toggle.setAttribute('aria-label', show ? i18n.hidePassword : i18n.showPassword);
                    toggle.setAttribute('title', show ? i18n.hidePassword : i18n.showPassword);
                    toggle.textContent = show ? i18n.hide : i18n.show;
                    input.focus();
                });
            }

            var form = document.querySelector('[data-login-form]');
            var submit = document.querySelector('[data-login-submit]');
            if (form && submit) {
                form.addEventListener('submit', function () {
                    submit.disabled = true;
                    submit.textContent = i18n.signingIn;
                });
            }
        })();
    </script>
</body>
</html>
