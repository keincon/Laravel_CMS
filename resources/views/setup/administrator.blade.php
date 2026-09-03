@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" title="Administrator">
    <h2 class="{{ $ui->class('heading') }}">Administrator Account</h2>
    <p class="{{ $ui->class('subheading') }}">Create the first administrator. This account receives the Administrator role.</p>

    <form method="POST" action="{{ route('setup.administrator.store') }}" x-data="passwordStrength()">
        @csrf

        <x-ui.input name="name" label="Administrator Name" :value="$administrator['name']" required autocomplete="name" />
        <x-ui.input name="username" label="Username" :value="$administrator['username']" required autocomplete="username" />
        <x-ui.input name="email" label="Email Address" type="email" :value="$administrator['email']" required autocomplete="email" />

        <div class="mb-4">
            <label for="password" class="{{ $ui->class('label') }}">Password <span class="text-danger">*</span></label>
            <input
                type="password"
                name="password"
                id="password"
                required
                minlength="12"
                autocomplete="new-password"
                class="{{ $ui->class('input') }}"
                x-model="password"
                @input="evaluate"
            >
            <div class="password-meter" aria-hidden="true">
                <i :style="`width:${percent}%;background:${color}`"></i>
            </div>
            <p class="mt-1 text-sm" :class="labelClass" x-text="label"></p>
            <p class="mt-1 text-sm {{ $ui->class('text_muted') }}">
                Minimum 12 characters with uppercase, lowercase, number, and symbol.
                Example: <code>MySite2026!</code>
            </p>
            <p class="mt-1 text-sm {{ $ui->class('check_fail') }}" x-show="password.length > 0 && !meetsPolicy" x-cloak>
                Password does not meet all requirements yet — Install will fail if you continue with a weak password.
            </p>
            @error('password')
                <p class="mt-1 text-sm {{ $ui->class('check_fail') }}">{{ $message }}</p>
            @enderror
        </div>

        <x-ui.input name="password_confirmation" label="Confirm Password" type="password" required autocomplete="new-password" />

        <div class="actions between">
            <x-ui.button href="{{ route('setup.website') }}" variant="secondary" type="button">← Back</x-ui.button>
            <x-ui.button type="submit" variant="primary">Continue</x-ui.button>
        </div>
    </form>

    <x-slot:scripts>
        <script>
        function passwordStrength() {
            return {
                password: '',
                percent: 0,
                color: '#e2e8f0',
                label: 'Enter a password',
                labelClass: '{{ $ui->class('text_muted') }}',
                meetsPolicy: false,
                evaluate() {
                    const p = this.password;
                    let score = 0;
                    if (p.length >= 12) score++;
                    if (p.length >= 16) score++;
                    if (/[a-z]/.test(p) && /[A-Z]/.test(p)) score++;
                    if (/[0-9]/.test(p)) score++;
                    if (/[^A-Za-z0-9]/.test(p)) score++;
                    this.meetsPolicy = p.length >= 12
                        && /[a-z]/.test(p)
                        && /[A-Z]/.test(p)
                        && /[0-9]/.test(p)
                        && /[^A-Za-z0-9]/.test(p);

                    const map = [
                        { percent: 10, color: '#ef4444', label: 'Too weak', cls: '{{ $ui->class('check_fail') }}' },
                        { percent: 30, color: '#f97316', label: 'Weak', cls: '{{ $ui->class('check_fail') }}' },
                        { percent: 55, color: '#eab308', label: 'Fair', cls: '{{ $ui->class('text_muted') }}' },
                        { percent: 75, color: '#84cc16', label: 'Good', cls: '{{ $ui->class('check_ok') }}' },
                        { percent: 100, color: '#10b981', label: 'Strong', cls: '{{ $ui->class('check_ok') }}' },
                    ];
                    const level = map[Math.max(0, Math.min(score - 1, map.length - 1))];
                    if (!p) {
                        this.percent = 0;
                        this.label = 'Enter a password';
                        this.labelClass = '{{ $ui->class('text_muted') }}';
                        return;
                    }
                    this.percent = level.percent;
                    this.color = level.color;
                    this.label = level.label;
                    this.labelClass = level.cls;
                }
            }
        }
        </script>
    </x-slot:scripts>
</x-setup.layout>
