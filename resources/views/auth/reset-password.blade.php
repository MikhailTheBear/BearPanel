<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 justify-center">
                <div class="h-16 w-16 rounded-2xl bg-gray-900 text-white grid place-items-center font-bold text-2xl select-none">
                    BP
                </div>
                <div class="leading-tight text-left">
                    <div class="text-base font-semibold text-gray-900">BearPanel</div>
                    <div class="text-sm text-gray-500">Control panel</div>
                </div>
            </a>
        </x-slot>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="block">
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            </div>

            <div class="mt-4">
                <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button>
                    {{ __('Reset Password') }}
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>