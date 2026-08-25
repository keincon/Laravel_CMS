@props([
    'cmsName' => null,
    'currentStep' => 1,
    'title' => 'Setup',
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — {{ $cmsName ?? config('cms.name') }}</title>
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
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="setup-body" x-data>
    <div class="setup-shell">
        <div class="setup-brand">
            <div class="setup-logo" aria-hidden="true">CMS</div>
            <h1>{{ $cmsName ?? config('cms.name') }}</h1>
            <p>Let's get your website ready.</p>
        </div>

        @php
            $steps = [
                1 => 'Welcome',
                2 => 'Requirements',
                3 => 'Database',
                4 => 'Website',
                5 => 'Administrator',
                6 => 'Appearance',
                7 => 'Install',
                8 => 'Complete',
            ];
            $current = $currentStep ?? 1;
        @endphp

        <nav class="setup-steps" aria-label="Setup progress">
            @foreach ($steps as $num => $label)
                @php
                    $state = $num < $current ? 'done' : ($num === $current ? 'active' : 'pending');
                    $badge = $ui->class('step_'.$state);
                @endphp
                <div class="setup-step">
                    <span class="{{ $badge }}">{{ $num < $current ? '✓' : $num }}</span>
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
