<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-factor — {{ config('cms.name') }}</title>
    @php $ui = app(\App\Services\UIFrameworkService::class); @endphp
    @foreach ($ui->stylesheetUrls() as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
    <x-theme />
</head>
<body style="background:var(--color-background);color:var(--color-text);min-height:100vh;display:grid;place-items:center">
    <form method="POST" action="{{ route('login.two-factor.verify') }}" class="p-4 rounded shadow-sm" style="background:var(--color-surface);width:min(400px,92vw)">
        @csrf
        <h1 class="h4 mb-3">Two-factor authentication</h1>
        <p class="muted small mb-3">Enter the 6-digit code from your authenticator app (or a recovery code).</p>
        <div class="mb-3">
            <label class="form-label">Authentication code</label>
            <input name="code" class="form-control" required autofocus autocomplete="one-time-code">
            @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <button class="btn btn-primary w-100" type="submit" style="background:var(--color-primary);border-color:var(--color-primary)">Verify</button>
    </form>
</body>
</html>
