@php
    $ui = app(\App\Services\UIFrameworkService::class);
    $continueDisabled = empty($database['tested']);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" title="Database">
    <h2 class="{{ $ui->class('heading') }}">Database Configuration</h2>
    <p class="{{ $ui->class('subheading') }}">Choose PostgreSQL or MySQL, then test the connection before continuing.</p>

    <div class="mb-3 p-3 rounded" style="background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.25);font-size:.9rem">
        <strong>Docker defaults:</strong>
        host <code>postgres</code>,
        port <code>5432</code>,
        database <code>cms</code>,
        username <code>cms</code>,
        password <code>cms_secret</code>.
        Do not use <code>127.0.0.1</code> inside the app container.
    </div>

    <div id="db-status"></div>

    <form method="POST" action="{{ route('setup.database.store') }}" id="database-form">
        @csrf

        <x-ui.select
            name="type"
            id="db-type"
            label="Database Type"
            :value="$database['type']"
            :options="$driverOptions"
            required
        />

        <x-ui.input name="host" id="db-host" label="Database Host" :value="$database['host']" required />
        <x-ui.input name="port" id="db-port" label="Database Port" :value="$database['port']" required />
        <x-ui.input name="database" label="Database Name" :value="$database['database']" required />
        <x-ui.input name="username" label="Database Username" :value="$database['username']" required autocomplete="username" />
        <x-ui.input name="password" label="Database Password" type="password" value="{{ old('password', $database['password_plain'] ?? '') }}" autocomplete="new-password" />
        <p class="mb-3 text-sm {{ $ui->class('text_muted') }}">Required for Docker (<code>cms_secret</code>). Leaving this blank causes “no password supplied”.</p>

        <div class="actions between">
            <x-ui.button href="{{ route('setup.requirements') }}" variant="secondary" type="button">← Back</x-ui.button>
            <div class="actions" style="margin:0">
                <x-ui.button type="button" variant="secondary" id="test-connection-btn">Test Connection</x-ui.button>
                @if ($continueDisabled)
                    <x-ui.button type="submit" variant="primary" id="continue-btn" disabled>
                        Continue
                    </x-ui.button>
                @else
                    <x-ui.button type="submit" variant="primary" id="continue-btn">
                        Continue
                    </x-ui.button>
                @endif
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
            const typeSelect = document.getElementById('db-type');
            const hostInput = document.getElementById('db-host');
            const portInput = document.getElementById('db-port');
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const testUrl = @json(route('setup.database.test'));
            const alertSuccess = @json($ui->class('alert_success'));
            const alertDanger = @json($ui->class('alert_danger'));
            const driverMeta = @json($driverMeta);

            const markUntested = () => {
                continueBtn.disabled = true;
                status.innerHTML = '';
            };

            typeSelect.addEventListener('change', () => {
                const meta = driverMeta[typeSelect.value] || {};
                if (meta.default_port) {
                    portInput.value = meta.default_port;
                }
                markUntested();
            });

            ['db-host', 'db-port', 'database', 'username', 'password'].forEach((id) => {
                const el = document.getElementById(id) || form.querySelector('[name="' + id + '"]');
                if (el) {
                    el.addEventListener('input', markUntested);
                    el.addEventListener('change', markUntested);
                }
            });

            testBtn.addEventListener('click', async () => {
                testBtn.disabled = true;
                status.innerHTML = '';

                const data = new FormData(form);

                try {
                    const res = await fetch(testUrl, {
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
                    const alertClass = ok ? alertSuccess : alertDanger;
                    status.innerHTML = '<div class="' + alertClass + ' mb-4">' + (json.message || '') + '</div>';
                    continueBtn.disabled = !ok;
                } catch (e) {
                    status.innerHTML = '<div class="' + alertDanger + ' mb-4">Could not test the connection. Please try again.</div>';
                    continueBtn.disabled = true;
                } finally {
                    testBtn.disabled = false;
                }
            });
        });
        </script>
    </x-slot:scripts>
</x-setup.layout>
