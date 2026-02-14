<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">

    {{-- Flash Success --}}
    @if (session()->has('status'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- SMTP Error --}}
    @if ($smtp_failed)
        <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
            <strong>{{ __('SMTP Error') }}:</strong><br>
            {{ $smtp_error }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- LEFT: Overview --}}
        <div class="bg-white rounded-xl shadow border border-gray-200 p-6 space-y-4">

            <h2 class="text-lg font-semibold">
                {{ __('Overview') }}
            </h2>

            <div class="space-y-2 text-sm text-gray-700">

                <div class="flex justify-between">
                    <span class="text-gray-500">APP_NAME</span>
                    <span class="font-medium">{{ $app_name }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">PANEL_VERSION</span>
                    <span class="font-medium">{{ $panel_version }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">APP_ENV</span>
                    <span class="font-medium">{{ $app_env }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">APP_LOCALE</span>
                    <span class="font-medium">{{ $app_locale }}</span>
                </div>

                <hr>

                <div class="flex justify-between">
                    <span class="text-gray-500">PHP</span>
                    <span class="font-medium">{{ phpversion() }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Laravel</span>
                    <span class="font-medium">{{ app()->version() }}</span>
                </div>

            </div>
        </div>

        {{-- RIGHT: Settings --}}
        <div class="bg-white rounded-xl shadow border border-gray-200 p-6 space-y-6">

            <h2 class="text-lg font-semibold">
                {{ __('Settings') }}
            </h2>

            {{-- Language --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    {{ __('Language') }}
                </label>

                <select wire:model="app_locale"
                        class="w-full rounded-lg border-gray-300 text-sm">
                    @foreach($locales as $code => $label)
                        <option value="{{ $code }}">
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- SMTP --}}
            <div class="border-t pt-6 space-y-4">

                <h3 class="text-md font-semibold">
                    SMTP
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <input wire:model="mail_host"
                           type="text"
                           placeholder="MAIL_HOST"
                           class="rounded-lg border-gray-300 text-sm">

                    <input wire:model="mail_port"
                           type="text"
                           placeholder="MAIL_PORT"
                           class="rounded-lg border-gray-300 text-sm">

                    <input wire:model="mail_username"
                           type="text"
                           placeholder="MAIL_USERNAME"
                           class="rounded-lg border-gray-300 text-sm">

                    <input wire:model="mail_password"
                           type="password"
                           placeholder="MAIL_PASSWORD"
                           class="rounded-lg border-gray-300 text-sm">

                    <input wire:model="mail_encryption"
                           type="text"
                           placeholder="MAIL_ENCRYPTION"
                           class="rounded-lg border-gray-300 text-sm">

                    <input wire:model="mail_from_address"
                           type="email"
                           placeholder="MAIL_FROM_ADDRESS"
                           class="rounded-lg border-gray-300 text-sm">

                    <input wire:model="mail_from_name"
                           type="text"
                           placeholder="MAIL_FROM_NAME"
                           class="rounded-lg border-gray-300 text-sm">

                </div>

                {{-- Test Email --}}
                <div class="pt-4">
                    <label class="block text-sm font-medium mb-1">
                        {{ __('Test email recipient (optional)') }}
                    </label>

                    <input wire:model.defer="test_email"
                           type="email"
                           placeholder="test@example.com"
                           class="w-full rounded-lg border-gray-300 text-sm">

                    @error('test_email')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Buttons --}}
                <div class="flex gap-3 pt-4">

                    <button wire:click="save"
                            class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm">
                        {{ __('Save') }}
                    </button>

                    <button wire:click="sendTestEmail"
                            wire:loading.attr="disabled"
                            wire:target="sendTestEmail"
                            class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">

                        <span wire:loading.remove wire:target="sendTestEmail">
                            {{ __('Send test email') }}
                        </span>

                        <span wire:loading wire:target="sendTestEmail">
                            {{ __('Sending...') }}
                        </span>
                    </button>

                </div>

            </div>

        </div>

    </div>
</div>