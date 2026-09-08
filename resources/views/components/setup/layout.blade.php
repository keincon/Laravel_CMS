@props([
    'cmsName' => null,
    'currentStep' => 1,
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('setup.steps.1') }} — {{ $cmsName ?? config('cms.name') }}</title>
    @php
        $ui = app(\App\Services\UIFrameworkService::class);
        $framework = $ui->current();
    @endphp
    @foreach ($ui->stylesheetUrls($framework) as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
    <style>
        :root {
            --setup-bg-1: #0f172a;
            --setup-bg-2: #1e3a5f;
            --setup-accent: #0ea5e9;
        }
        body.setup-body {
            min-height: 100vh;
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background:
                radial-gradient(1200px 600px at 10% -10%, rgba(14,165,233,.25), transparent 60%),
                radial-gradient(900px 500px at 100% 0%, rgba(56,189,248,.18), transparent 55%),
                linear-gradient(160deg, var(--setup-bg-1), var(--setup-bg-2));
            color: #0f172a;
        }
        .setup-shell {
            max-width: 720px;
            margin: 0 auto;
            padding: 2rem 1rem 3rem;
        }
        .setup-logo {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, #38bdf8, #0284c7);
            display: grid;
            place-items: center;
            color: #fff;
            font-weight: 700;
            font-size: 1.25rem;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 30px rgba(14,165,233,.35);
        }
        .setup-brand {
            text-align: center;
            color: #e2e8f0;
            margin-bottom: 1.5rem;
        }
        .setup-brand h1 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 600;
            letter-spacing: -.02em;
        }
        .setup-brand p {
            margin: .35rem 0 0;
            color: #94a3b8;
            font-size: .95rem;
        }
        .visually-hidden {
            position: absolute !important;
            width: 1px; height: 1px;
            padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0, 0, 0, 0);
            white-space: nowrap; border: 0;
        }
        .locale-switcher {
            display: inline-flex;
            justify-content: center;
            margin: .75rem auto 0;
        }
        .locale-switcher-select {
            appearance: none;
            background: rgba(15, 23, 42, 0.55);
            color: #e2e8f0;
            border: 1px solid rgba(148, 163, 184, 0.45);
            border-radius: .5rem;
            padding: .35rem 1.75rem .35rem .7rem;
            font-size: .85rem;
            line-height: 1.3;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%94a3b8' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .55rem center;
        }
        .locale-switcher-select:hover,
        .locale-switcher-select:focus {
            border-color: #38bdf8;
            outline: none;
            color: #fff;
        }
        .locale-switcher-select option {
            background: #0f172a;
            color: #e2e8f0;
        }
        .setup-steps {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .setup-step {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            color: #cbd5e1;
            font-size: .78rem;
        }
        .setup-step span.label { display: none; }
        @media (min-width: 640px) {
            .setup-step span.label { display: inline; }
        }
        .password-meter {
            height: 6px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
            margin-top: .5rem;
        }
        .password-meter > i {
            display: block;
            height: 100%;
            width: 0;
            transition: width .25s ease, background .25s ease;
        }
        .install-progress li {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .45rem 0;
        }
        .check-list { list-style: none; padding: 0; margin: 0; }
        .check-list li {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .55rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .check-list li:last-child { border-bottom: 0; }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            margin-top: 1.5rem;
        }
        .actions.between { justify-content: space-between; }
        .actions.end { justify-content: flex-end; }

        /* Hard-coded button colors so setup never depends on CDN token gaps (e.g. sky-*). */
        .cms-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .7rem 1.35rem;
            border-radius: .65rem;
            font-weight: 600;
            font-size: .95rem;
            line-height: 1.25;
            text-decoration: none !important;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease, box-shadow .15s ease, color .15s ease;
        }
        .cms-btn-primary,
        a.cms-btn-primary,
        button.cms-btn-primary {
            background: #2563eb !important;
            color: #fff !important;
            border-color: #2563eb !important;
            box-shadow: 0 8px 20px rgba(37, 99, 235, .28);
        }
        .cms-btn-primary:hover,
        a.cms-btn-primary:hover {
            background: #1d4ed8 !important;
            border-color: #1d4ed8 !important;
            color: #fff !important;
        }
        .cms-btn-secondary,
        a.cms-btn-secondary,
        button.cms-btn-secondary {
            background: #fff !important;
            color: #334155 !important;
            border-color: #cbd5e1 !important;
        }
        .cms-btn-secondary:hover {
            background: #f8fafc !important;
        }
        .cms-btn-success,
        a.cms-btn-success,
        button.cms-btn-success {
            background: #16a34a !important;
            color: #fff !important;
            border-color: #16a34a !important;
        }
        .cms-btn-success:hover {
            background: #15803d !important;
            color: #fff !important;
        }

        .setup-step .cms-step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 700;
        }
        .setup-step.is-active .cms-step-badge { background: #2563eb; color: #fff; }
        .setup-step.is-done .cms-step-badge { background: #22c55e; color: #fff; }
        .setup-step.is-pending .cms-step-badge { background: #e2e8f0; color: #64748b; }
        .setup-step.is-active { color: #fff; }
        .setup-step.is-done { color: #cbd5e1; }
        .setup-step.is-pending { color: #94a3b8; }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="setup-body" x-data>
    <div class="setup-shell">
        <div class="setup-brand">
            <div class="setup-logo" aria-hidden="true">CMS</div>
            <h1>{{ $cmsName ?? config('cms.name') }}</h1>
            <p>{{ __('setup.tagline') }}</p>
            <x-locale-switcher />
        </div>

        @php
            $steps = [
                1 => __('setup.steps.1'),
                2 => __('setup.steps.2'),
                3 => __('setup.steps.3'),
                4 => __('setup.steps.4'),
                5 => __('setup.steps.5'),
                6 => __('setup.steps.6'),
                7 => __('setup.steps.7'),
                8 => __('setup.steps.8'),
            ];
            $current = $currentStep ?? 1;
        @endphp

        <nav class="setup-steps" aria-label="{{ __('setup.progress') }}">
            @foreach ($steps as $num => $label)
                @php
                    $state = $num < $current ? 'done' : ($num === $current ? 'active' : 'pending');
                @endphp
                <div class="setup-step is-{{ $state }}">
                    <span class="cms-step-badge" aria-hidden="true">{{ $num < $current ? '✓' : $num }}</span>
                    <span class="label">{{ $label }}</span>
                </div>
            @endforeach
        </nav>

        <x-ui.card>
            @if (session('success'))
                <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
            @endif
            @if (session('error'))
                <x-ui.alert type="danger">{{ session('error') }}</x-ui.alert>
            @endif
            @if ($errors->any())
                <x-ui.alert type="danger">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            {{ $slot }}
        </x-ui.card>
    </div>

    @foreach ($ui->scriptUrls($framework) as $src)
        <script src="{{ $src }}" defer></script>
    @endforeach
    {{ $scripts ?? '' }}
</body>
</html>
