@php
    $ui = app(\App\Services\UIFrameworkService::class);
    $siteName = \App\Models\CmsSetting::getValue('site_name') ?: config('cms.name', config('app.name'));
    $siteDescription = \App\Models\CmsSetting::getValue('site_description') ?: 'Sign in to continue';
@endphp
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — {{ $siteName }}</title>
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
            --login-accent: var(--color-primary, #0b6e4f);
            --login-glow: rgba(11, 110, 79, 0.18);
        }
        [data-theme="dark"] {
            --login-ink: #e8eef5;
            --login-muted: #9fb3c8;
            --login-panel: rgba(17, 24, 39, 0.9);
            --login-line: rgba(232, 238, 245, 0.12);
            --login-glow: rgba(56, 189, 248, 0.16);
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
            padding: 1rem 1.25rem;
        }
        .login-stage {
            display: grid;
            place-items: center;
            padding: 1rem 1.25rem 2.5rem;
        }
        .login-shell {
            width: min(420px, 100%);
        }
        .login-brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-mark {
            width: 64px;
            height: 64px;
            margin: 0 auto .9rem;
            border-radius: 18px;
            display: grid;
            place-items: center;
            font-family: Fraunces, Georgia, serif;
            font-weight: 700;
            font-size: 1.6rem;
            color: #fff;
            background: linear-gradient(145deg, var(--login-accent), color-mix(in srgb, var(--login-accent) 55%, #0ea5e9));
            box-shadow: 0 14px 34px var(--login-glow);
        }
        .login-brand h1 {
            margin: 0;
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(1.75rem, 4vw, 2.15rem);
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.15;
        }
        .login-brand p {
            margin: .45rem 0 0;
            color: var(--login-muted);
            font-size: 1rem;
        }
        .login-panel {
            background: var(--login-panel);
            backdrop-filter: blur(10px);
            border: 1px solid var(--login-line);
            border-radius: 18px;
            padding: 1.35rem 1.35rem 1.5rem;
            box-shadow: 0 18px 50px rgba(16, 42, 67, 0.08);
        }
        .login-panel h2 {
            margin: 0 0 1rem;
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--login-muted);
        }
        .login-panel .form-label {
            font-weight: 500;
            color: var(--login-ink);
        }
        .login-panel .form-control {
            border-radius: 10px;
            border-color: var(--login-line);
            padding: .65rem .8rem;
            background: color-mix(in srgb, var(--color-surface, #fff) 92%, transparent);
            color: var(--login-ink);
        }
        .login-panel .form-control:focus {
            border-color: var(--login-accent);
            box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--login-accent) 25%, transparent);
        }
        .login-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: 1rem;
        }
        .login-submit {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: .75rem 1rem;
            font-weight: 600;
            background: var(--login-accent);
            color: #fff;
        }
        .login-submit:hover { filter: brightness(1.05); }
        .login-foot {
            text-align: center;
            padding: 0 1rem 1.25rem;
            color: var(--login-muted);
            font-size: .9rem;
        }
        .login-foot a { color: var(--login-accent); text-decoration: none; }
        .theme-toggle {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border: 1px solid var(--login-line);
            background: var(--login-panel);
            color: var(--login-ink);
            border-radius: 999px;
            padding: .35rem .7rem;
            font-size: .85rem;
            cursor: pointer;
        }
        .theme-toggle[data-theme-active="dark"] .theme-toggle-sun { display: inline; }
        .theme-toggle[data-theme-active="dark"] .theme-toggle-moon { display: none; }
        .theme-toggle[data-theme-active="light"] .theme-toggle-sun { display: none; }
        .theme-toggle[data-theme-active="light"] .theme-toggle-moon { display: inline; }
    </style>
</head>
<body class="login-body">
    <div class="login-top">
        <x-theme-toggle />
    </div>

    <main class="login-stage">
        <div class="login-shell">
            <div class="login-brand">
                <div class="login-mark" aria-hidden="true">{{ strtoupper(substr($siteName, 0, 1)) }}</div>
                <h1>{{ $siteName }}</h1>
                <p>{{ $siteDescription }}</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="login-panel" autocomplete="on">
                @csrf
                <h2>Sign in to your account</h2>

                @if (session('error'))
                    <x-ui.alert type="danger">{{ session('error') }}</x-ui.alert>
                @endif
                @if ($errors->any())
                    <x-ui.alert type="danger">{{ $errors->first() }}</x-ui.alert>
                @endif

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus autocomplete="username">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input id="password" type="password" name="password" class="form-control" required autocomplete="current-password">
                </div>
                <div class="login-actions">
                    <label class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                        <span class="form-check-label">Remember me</span>
                    </label>
                </div>
                <button class="login-submit" type="submit">Log in</button>
            </form>
        </div>
    </main>

    <footer class="login-foot">
        <a href="{{ url('/') }}">← Back to website</a>
    </footer>
</body>
</html>
