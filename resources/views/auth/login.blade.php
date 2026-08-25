<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — {{ config('cms.name') }}</title>
    @php $ui = app(\App\Services\UIFrameworkService::class); @endphp
    @foreach ($ui->stylesheetUrls() as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
    <x-theme />
</head>
<body style="background:var(--color-background);color:var(--color-text);min-height:100vh;display:grid;place-items:center">
    <form method="POST" action="{{ route('login') }}" class="p-4 rounded shadow-sm" style="background:var(--color-surface);width:min(400px,92vw)">
        @csrf
        <h1 class="h4 mb-3">Sign in</h1>
        @if (session('error'))
            <x-ui.alert type="danger">{{ session('error') }}</x-ui.alert>
        @endif
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label" for="remember">Remember me</label>
        </div>
        <button class="btn btn-primary w-100" type="submit" style="background:var(--color-primary);border-color:var(--color-primary)">Log in</button>
    </form>
</body>
</html>
