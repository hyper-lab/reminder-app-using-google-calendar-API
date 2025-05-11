<x-mail::message>
# Gentle Reminder!

Hi {{ $reminder->user->name }},

This is a reminder for your event: **{{ $reminder->title }}**

**Time:** {{ $reminder->reminder_time->format('l, F jS, Y \a\t g:i A') }} ({{ $reminder->reminder_time->diffForHumans() }})

@if($reminder->description)
**Description:**
{{ $reminder->description }}
@endif

@if($reminder->guest_emails)
**Guests Notified:**
{{ $reminder->guest_emails }}
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>