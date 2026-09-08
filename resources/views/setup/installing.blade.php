@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" :title="__('setup.installing.title')">
    <div x-data="installer()" x-cloak>
        <template x-if="!running && !done && !failed">
            <div>
                <h2 class="{{ $ui->class('heading') }}">{{ __('setup.installing.heading') }}</h2>
                <p class="{{ $ui->class('subheading') }}">{{ __('setup.installing.subheading') }}</p>

                <ul class="check-list mb-4">
                    <li><strong>{{ __('setup.installing.summary_site') }}:</strong>&nbsp; {{ $summary['website'] }}</li>
                    <li><strong>{{ __('setup.installing.summary_url') }}:</strong>&nbsp; {{ $summary['url'] }}</li>
                    <li><strong>{{ __('setup.installing.summary_admin') }}:</strong>&nbsp; {{ $summary['admin'] }}</li>
                    <li><strong>{{ __('setup.installing.summary_ui') }}:</strong>&nbsp; {{ ucfirst($summary['framework']) }}</li>
                </ul>

                <div class="actions between">
                    <x-ui.button href="{{ route('setup.appearance') }}" variant="secondary" type="button">{{ __('setup.installing.back') }}</x-ui.button>
                    <x-ui.button type="button" variant="success" @click="start">{{ __('setup.installing.start') }}</x-ui.button>
                </div>
            </div>
        </template>

        <template x-if="running || done">
            <div>
                <h2 class="{{ $ui->class('heading') }}">{{ __('setup.installing.installing') }}</h2>
                <p class="{{ $ui->class('subheading') }}">{{ __('setup.installing.please_wait') }}</p>

                <ul class="install-progress check-list">
                    <template x-for="step in steps" :key="step.key">
                        <li>
                            <span :class="step.status === 'done' ? '{{ $ui->class('check_ok') }}' : '{{ $ui->class('text_muted') }}'" x-text="step.status === 'done' ? '✓' : '…'"></span>
                            <span x-text="step.label"></span>
                        </li>
                    </template>
                </ul>

                <p class="mt-3 {{ $ui->class('text_muted') }}" x-show="running">{{ __('setup.installing.working') }}</p>
            </div>
        </template>

        <template x-if="failed">
            <div>
                <h2 class="{{ $ui->class('heading') }}">{{ __('setup.installing.failed_heading') }}</h2>
                <p class="{{ $ui->class('subheading') }}" x-text="errorMessage"></p>
                <div class="actions between">
                    <x-ui.button href="{{ route('setup.database') }}" variant="secondary" type="button">← {{ __('setup.failed.database') }}</x-ui.button>
                    <x-ui.button href="{{ route('setup.administrator') }}" variant="secondary" type="button">{{ __('setup.failed.administrator') }}</x-ui.button>
                    <x-ui.button type="button" variant="primary" @click="start">{{ __('setup.failed.retry') }}</x-ui.button>
                </div>
            </div>
        </template>
    </div>

    <x-slot:scripts>
        <script>
        window.setupI18n = {
            errorDefault: @json(__('setup.installing.error_default')),
            interruptError: @json(__('setup.installing.interrupt_error')),
            steps: {
                validate: @json(__('setup.installing.progress_validate')),
                database_test: @json(__('setup.installing.progress_database_test')),
                migrate: @json(__('setup.installing.progress_migrate')),
                roles: @json(__('setup.installing.progress_roles')),
                permissions: @json(__('setup.installing.progress_permissions')),
                administrator: @json(__('setup.installing.progress_admin')),
                pages: @json(__('setup.installing.progress_pages')),
                menu: @json(__('setup.installing.progress_menu')),
                categories: @json(__('setup.installing.progress_categories')),
                settings: @json(__('setup.installing.progress_settings')),
                theme: @json(__('setup.installing.progress_theme')),
                finalize: @json(__('setup.installing.progress_done')),
            },
        };
        function installer() {
            const i18n = window.setupI18n;
            const stepDefs = [
                'validate',
                'database_test',
                'migrate',
                'roles',
                'permissions',
                'administrator',
                'settings',
                'theme',
                'finalize',
            ];
            const makeSteps = () => stepDefs.map((key) => ({
                key,
                label: i18n.steps[key] || key,
                status: 'pending',
            }));
            return {
                running: false,
                done: false,
                failed: false,
                errorMessage: i18n.errorDefault,
                steps: makeSteps(),
                async start() {
                    this.running = true;
                    this.failed = false;
                    this.done = false;
                    this.steps = makeSteps();

                    let i = 0;
                    const tick = setInterval(() => {
                        if (i < this.steps.length) {
                            this.steps[i].status = 'done';
                            i++;
                        }
                    }, 450);

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').content;
                        const res = await fetch(@json(route('setup.install')), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({}),
                        });
                        const json = await res.json();
                        clearInterval(tick);

                        if (json.steps) {
                            this.steps = json.steps.map(s => ({
                                key: s.key,
                                label: i18n.steps[s.key] || s.label,
                                status: s.status === 'done' ? 'done' : 'pending',
                            }));
                        } else {
                            this.steps = this.steps.map(s => ({ ...s, status: 'done' }));
                        }

                        if (json.success) {
                            this.done = true;
                            this.running = false;
                            window.location.href = json.redirect || @json(route('setup.complete'));
                        } else {
                            this.failed = true;
                            this.running = false;
                            this.errorMessage = json.message || this.errorMessage;
                        }
                    } catch (e) {
                        clearInterval(tick);
                        this.failed = true;
                        this.running = false;
                        this.errorMessage = i18n.interruptError;
                    }
                }
            }
        }
        </script>
    </x-slot:scripts>
</x-setup.layout>
