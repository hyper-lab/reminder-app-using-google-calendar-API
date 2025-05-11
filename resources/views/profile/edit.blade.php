<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

            {{-- Inside the profile edit form, maybe in its own section --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg mt-6"> {{-- Added mt-6 --}}
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Google Calendar Integration') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Connect your Google Calendar to automatically sync reminders.') }}
                            </p>
                        </header>

                        {{-- Display connection status and buttons --}}
                        <div class="mt-6 space-y-6">
                            @if (Auth::user()->google_access_token) {{-- Check if user has a token --}}
                                <p class="text-sm text-green-600 dark:text-green-400">
                                    <svg class="inline w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Google Calendar is connected.
                                    Expires: {{ Auth::user()->google_token_expires_at ? Auth::user()->google_token_expires_at->diffForHumans() : 'N/A' }}
                                </p>
                                {{-- Add a form for the disconnect button --}}
                                <form method="GET" action="{{ route('google.calendar.disconnect') }}">
                                    @csrf
                                    <x-danger-button type="submit">
                                        {{ __('Disconnect Google Calendar') }}
                                    </x-danger-button>
                                </form>
                            @else
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                   Google Calendar is not connected.
                                </p>
                                <a href="{{ route('google.calendar.connect') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    {{ __('Connect Google Calendar') }}
                                </a>
                            @endif
                        </div>

                        {{-- Display Success/Error Messages from Redirect --}}
                        @if (session('success'))
                            <p class="mt-2 text-sm text-green-600 dark:text-green-400">{{ session('success') }}</p>
                        @endif
                        @if (session('error'))
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ session('error') }}</p>
                        @endif

                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>