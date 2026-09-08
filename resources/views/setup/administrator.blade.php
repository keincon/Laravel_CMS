@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" :title="__('setup.administrator.title')">
    <h2 class="{{ $ui->class('heading') }}">{{ __('setup.administrator.heading') }}</h2>
    <p class="{{ $ui->class('subheading') }}">{{ __('setup.administrator.subheading') }}</p>

    <form method="POST" action="{{ route('setup.administrator.store') }}" x-data="passwordStrength()">
        @csrf

        <x-ui.input name="name" :label="__('setup.administrator.name')" :value="$administrator['name']" required autocomplete="name" />
        <x-ui.input name="username" :label="__('setup.administrator.username')" :value="$administrator['username']" required autocomplete="username" />
        <x-ui.input name="email" :label="__('setup.administrator.email')" type="email" :value="$administrator['email']" required autocomplete="email" />

        <div class="mb-4">
            <label for="password" class="{{ $ui->class('label') }}">{{ __('setup.administrator.password') }} <span class="text-danger">*</span></label>
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
                {!! __('setup.administrator.password_hint') !!}
            </p>
            <p class="mt-1 text-sm {{ $ui->class('check_fail') }}" x-show="password.length > 0 && !meetsPolicy" x-cloak>
                {{ __('setup.administrator.password_policy_fail') }}
            </p>
            @error('password')
                <p class="mt-1 text-sm {{ $ui->class('check_fail') }}">{{ $message }}</p>
            @enderror
        </div>

        <x-ui.input name="password_confirmation" :label="__('setup.administrator.password_confirmation')" type="password" required autocomplete="new-password" />

        <div class="actions between">
            <x-ui.button href="{{ route('setup.website') }}" variant="secondary" type="button">{{ __('setup.administrator.back') }}</x-ui.button>
            <x-ui.button type="submit" variant="primary">{{ __('setup.administrator.continue') }}</x-ui.button>
        </div>
    </form>

    <x-slot:scripts>
        <script>
        window.setupI18n = {
            strengthEnter: @json(__('setup.administrator.strength_enter')),
            strengthTooWeak: @json(__('setup.administrator.strength_too_weak')),
            strengthWeak: @json(__('setup.administrator.strength_weak')),
            strengthFair: @json(__('setup.administrator.strength_fair')),
            strengthGood: @json(__('setup.administrator.strength_good')),
            strengthStrong: @json(__('setup.administrator.strength_strong')),
        };
        function passwordStrength() {
            const i18n = window.setupI18n;
            return {
                password: '',
                percent: 0,
                color: '#e2e8f0',
                label: i18n.strengthEnter,
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
                        { percent: 10, color: '#ef4444', label: i18n.strengthTooWeak, cls: '{{ $ui->class('check_fail') }}' },
                        { percent: 30, color: '#f97316', label: i18n.strengthWeak, cls: '{{ $ui->class('check_fail') }}' },
                        { percent: 55, color: '#eab308', label: i18n.strengthFair, cls: '{{ $ui->class('text_muted') }}' },
                        { percent: 75, color: '#84cc16', label: i18n.strengthGood, cls: '{{ $ui->class('check_ok') }}' },
                        { percent: 100, color: '#10b981', label: i18n.strengthStrong, cls: '{{ $ui->class('check_ok') }}' },
                    ];
                    const level = map[Math.max(0, Math.min(score - 1, map.length - 1))];
                    if (!p) {
                        this.percent = 0;
                        this.label = i18n.strengthEnter;
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
