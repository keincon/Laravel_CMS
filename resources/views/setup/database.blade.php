@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" title="Database">
    <h2 class="{{ $ui->class('heading') }}">Database Configuration</h2>
    <p class="{{ $ui->class('subheading') }}">Connect to PostgreSQL. Test the connection before continuing.</p>

    <div id="db-status"></div>

    <form method="POST" action="{{ route('setup.database.store') }}" id="database-form">
        @csrf

        <x-ui.select
            name="type"
            label="Database Type"
            :value="$database['type']"
            :options="['pgsql' => 'PostgreSQL']"
            required
        />

        <x-ui.input name="host" label="Database Host" :value="$database['host']" required />
        <x-ui.input name="port" label="Database Port" :value="$database['port']" required />
        <x-ui.input name="database" label="Database Name" :value="$database['database']" required />
        <x-ui.input name="username" label="Database Username" :value="$database['username']" required autocomplete="username" />
        <x-ui.input name="password" label="Database Password" type="password" value="" autocomplete="new-password" />

        <div class="actions between">
            <x-ui.button href="{{ route('setup.requirements') }}" variant="secondary" type="button">← Back</x-ui.button>
            <div class="actions" style="margin:0">
                <x-ui.button type="button" variant="secondary" id="test-connection-btn">Test Connection</x-ui.button>
                <x-ui.button type="submit" variant="primary" id="continue-btn" @disabled(! $database['tested'])>
                    Continue
                </x-ui.button>
            </div>
        </div>
    </form>

    <x-slot:scripts>
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('database-form');
            const status = document.getElementById('db-status');
            const continueBtn = document.getElementById('continue-btn');
            const testBtn = document.getElementById('test-connection-btn');
            const token = document.querySelector('meta[name="csrf-token"]').content;

            testBtn.addEventListener('click', async () => {
                testBtn.disabled = true;
                status.innerHTML = '';

                const data = new FormData(form);

                try {
                    const res = await fetch(@json(route('setup.database.test')), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: data,
                    });
                    const json = await res.json();
                    const ok = !!json.success;
                    status.innerHTML = `<div class="${ok ? @json($ui->class('alert_success')) : @json($ui->class('alert_danger'))} mb-4">${json.message}</div>`;
                    continueBtn.disabled = !ok;
                } catch (e) {
                    status.innerHTML = `<div class="{{ $ui->class('alert_danger') }} mb-4">Could not test the connection. Please try again.</div>`;
                    continueBtn.disabled = true;
                } finally {
                    testBtn.disabled = false;
                }
            });
        });
        </script>
    </x-slot:scripts>
</x-setup.layout>
