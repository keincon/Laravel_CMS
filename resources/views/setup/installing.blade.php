@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" title="Install">
    <div x-data="installer()" x-cloak>
        <template x-if="!running && !done && !failed">
            <div>
                <h2 class="{{ $ui->class('heading') }}">Ready to Install</h2>
                <p class="{{ $ui->class('subheading') }}">Review your choices, then install the CMS.</p>

                <ul class="check-list mb-4">
                    <li><strong>Website:</strong>&nbsp; {{ $summary['website'] }}</li>
                    <li><strong>URL:</strong>&nbsp; {{ $summary['url'] }}</li>
                    <li><strong>Administrator:</strong>&nbsp; {{ $summary['admin'] }}</li>
                    <li><strong>UI Framework:</strong>&nbsp; {{ ucfirst($summary['framework']) }}</li>
                </ul>

                <div class="actions between">
                    <x-ui.button href="{{ route('setup.appearance') }}" variant="secondary" type="button">← Back</x-ui.button>
                    <x-ui.button type="button" variant="success" @click="start">Install CMS</x-ui.button>
                </div>
            </div>
        </template>

        <template x-if="running || done">
            <div>
                <h2 class="{{ $ui->class('heading') }}">Installing CMS...</h2>
                <p class="{{ $ui->class('subheading') }}">Please wait. Do not close this window.</p>

                <ul class="install-progress check-list">
                    <template x-for="step in steps" :key="step.key">
                        <li>
                            <span :class="step.status === 'done' ? '{{ $ui->class('check_ok') }}' : '{{ $ui->class('text_muted') }}'" x-text="step.status === 'done' ? '✓' : '…'"></span>
                            <span x-text="step.label"></span>
                        </li>
                    </template>
                </ul>

                <p class="mt-3 {{ $ui->class('text_muted') }}" x-show="running">Working…</p>
            </div>
        </template>

        <template x-if="failed">
            <div>
                <h2 class="{{ $ui->class('heading') }}">Installation could not be completed.</h2>
                <p class="{{ $ui->class('subheading') }}" x-text="errorMessage"></p>
                <div class="actions between">
                    <x-ui.button href="{{ route('setup.database') }}" variant="secondary" type="button">← Database</x-ui.button>
                    <x-ui.button href="{{ route('setup.administrator') }}" variant="secondary" type="button">Administrator</x-ui.button>
                    <x-ui.button type="button" variant="primary" @click="start">Try Again</x-ui.button>
                </div>
            </div>
        </template>
    </div>

    <x-slot:scripts>
        <script>
        function installer() {
            return {
                running: false,
                done: false,
                failed: false,
                errorMessage: 'Please check your settings and try again.',
                steps: [
                    { key: 'validate', label: 'Validating configuration', status: 'pending' },
                    { key: 'database_test', label: 'Testing database', status: 'pending' },
                    { key: 'migrate', label: 'Creating database', status: 'pending' },
                    { key: 'roles', label: 'Creating roles', status: 'pending' },
                    { key: 'permissions', label: 'Creating permissions', status: 'pending' },
                    { key: 'administrator', label: 'Creating administrator', status: 'pending' },
                    { key: 'settings', label: 'Creating default settings', status: 'pending' },
                    { key: 'theme', label: 'Creating theme', status: 'pending' },
                    { key: 'finalize', label: 'Finalizing installation', status: 'pending' },
                ],
                async start() {
                    this.running = true;
                    this.failed = false;
                    this.done = false;
                    this.steps = this.steps.map(s => ({ ...s, status: 'pending' }));

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
                                label: s.label,
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
                        this.errorMessage = 'The install request was interrupted (often the PHP server restarting after writing .env). Rebuild/restart Docker so it uses the built-in server, then click Try Again. If it still fails, check storage/logs/laravel.log.';
                    }
                }
            }
        }
        </script>
    </x-slot:scripts>
</x-setup.layout>
