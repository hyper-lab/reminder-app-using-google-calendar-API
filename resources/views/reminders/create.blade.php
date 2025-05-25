
<x-app-layout>
    {{-- Header Slot --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create a New Reminder') }}
        </h2>
    </x-slot>

    <div class="py-12">
        {{-- Centered container with padding --}}
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            {{-- Card Styling: White background, light border, standard shadow --}}
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg border border-gray-200">
                <div class="p-6 sm:p-8 text-gray-900">

                    {{-- Optional: Inner Heading - Standard dark text --}}
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-6">
                        Reminder Details
                    </h3>

                    {{-- Display Validation Errors - Light red theme --}}
                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-red-100 border border-red-400 rounded-md">
                            <div class="font-medium text-red-700">{{ __('Whoops! Something went wrong.') }}</div>
                            <ul class="mt-3 list-disc list-inside text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('reminders.store') }}" class="space-y-6">
                        @csrf

                        {{-- Title Section --}}
                        <div>
                            <x-input-label for="title" :value="__('Reminder Title')" class="font-medium text-gray-700" />
                            <x-text-input id="title" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" type="text" name="title" :value="old('title')" required autofocus placeholder="e.g., Team Meeting, Doctor Appointment" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        {{-- Description Section --}}
                        <div>
                            <x-input-label for="description" :value="__('Description (Optional)')" class="font-medium text-gray-700" />
                            <textarea id="description" name="description" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm placeholder-gray-400" placeholder="Add any relevant details or notes here...">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        {{-- Reminder Time Section --}}
                        <div class="mt-4">
                            <x-input-label for="reminder_time" :value="__('Reminder Date and Time')" />
                            <x-text-input id="reminder_time" class="block mt-1 w-full"
                                          type="datetime-local"
                                          name="reminder_time"
                                          :value="old('reminder_time')"
                                          required
                                          min="{{ $minDateTime ?? '' }}" />
                            <x-input-error :messages="$errors->get('reminder_time')" class="mt-2" />
                        </div>

                        {{-- Notify Me Before Event --}}
                        <div class="mt-4">
                            <x-input-label for="notify_minutes_before" :value="__('Notify Me Before Event')" />
                            <select id="notify_minutes_before" name="notify_minutes_before" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                <option value="0" {{ old('notify_minutes_before', 0) == '0' ? 'selected' : '' }}>At time of event</option>
                                <option value="5" {{ old('notify_minutes_before') == '5' ? 'selected' : '' }}>5 minutes before</option>
                                <option value="10" {{ old('notify_minutes_before') == '10' ? 'selected' : '' }}>10 minutes before</option>
                                <option value="15" {{ old('notify_minutes_before') == '15' ? 'selected' : '' }}>15 minutes before</option>
                                <option value="30" {{ old('notify_minutes_before') == '30' ? 'selected' : '' }}>30 minutes before</option>
                                <option value="60" {{ old('notify_minutes_before') == '60' ? 'selected' : '' }}>1 hour before</option>
                                <option value="180" {{ old('notify_minutes_before') == '180' ? 'selected' : '' }}>3 hours before</option>
                                <option value="300" {{ old('notify_minutes_before') == '300' ? 'selected' : '' }}>5 hours before</option>
                                <option value="600" {{ old('notify_minutes_before') == '600' ? 'selected' : '' }}>10 hours before</option>
                                <option value="720" {{ old('notify_minutes_before') == '720' ? 'selected' : '' }}>12 hours before</option>
                                {{-- You can add more options like 1440 for 24 hours before if needed --}}
                            </select>
                            <x-input-error :messages="$errors->get('notify_minutes_before')" class="mt-2" />
                        </div>

                        {{-- Guest Emails Section --}}
                        <div>
                            <x-input-label for="guest_emails" :value="__('Invite Guests (Optional)')" class="font-medium text-gray-700" />
                            <x-text-input id="guest_emails" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" type="email" name="guest_emails" :value="old('guest_emails')" placeholder="guest1@example.com, guest2@example.com" multiple />
                            <x-input-error :messages="$errors->get('guest_emails')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500">Enter guest emails separated by commas. They will also receive a notification.</p>
                        </div>

                        {{-- Actions / Buttons --}}
                        <div class="flex items-center justify-end pt-6 pb-2 border-t border-gray-200 mt-8">
                            <a href="{{ route('reminders.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150 mr-4">
                                {{ __('Cancel') }}
                            </a>

                            <x-primary-button>
                                {{ __('Save Reminder') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>