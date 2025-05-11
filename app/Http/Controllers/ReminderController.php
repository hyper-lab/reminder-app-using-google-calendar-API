<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Services\GoogleCalendarService; // Import the service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse; // For returning JSON data
use Illuminate\Support\Facades\Log; // Import Log

class ReminderController extends Controller
{
    // Inject the service through the constructor
    protected GoogleCalendarService $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Display a listing of the user's reminders.
     */
    public function index(): View
    {
        $reminders = Auth::user()->reminders()
                                 ->orderBy('reminder_time', 'asc')
                                 ->paginate(10);

        return view('reminders.index', compact('reminders'));
    }

    /**
     * Show the form for creating a new reminder.
     */
    public function create(): View
    {
        return view('reminders.create');
    }

    /**
     * Store a newly created reminder in storage and sync to Google Calendar.
     */
    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            // Modify this line: Add '|after:now'
            'reminder_time' => 'required|date|after:now',
            'guest_emails' => 'nullable|string',
        ]);

        $user = Auth::user();
        $validatedData['user_id'] = $user->id;

        $reminder = Reminder::create($validatedData);

        if ($user->google_refresh_token) {
            try {
                $googleEventId = $this->googleCalendarService->addEvent($reminder);

                if ($googleEventId) {
                    $reminder->google_event_id = $googleEventId;
                    $reminder->save();
                } else {
                    Log::warning("Failed to get Google Event ID for Reminder ID {$reminder->id}.");
                }
            } catch (\Exception $e) {
                Log::error("Error syncing Reminder ID {$reminder->id} to Google Calendar: " . $e->getMessage());
            }
        }

        return redirect()->route('reminders.index')
                         ->with('success', 'Reminder created successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reminder $reminder): View
    {
        if ($reminder->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('reminders.edit', compact('reminder'));
    }

    /**
     * Update the specified resource in storage and sync to Google Calendar.
     */
    public function update(Request $request, Reminder $reminder): RedirectResponse
    {
        if ($reminder->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            // Modify this line: Add '|after:now'
            'reminder_time' => 'required|date|after:now',
            'guest_emails' => 'nullable|string',
        ]);

        $reminder->update($validatedData);

        $user = Auth::user();
        if ($user->google_refresh_token) {
            try {
                if ($reminder->google_event_id) {
                    $this->googleCalendarService->updateEvent($reminder);
                } else {
                    $googleEventId = $this->googleCalendarService->addEvent($reminder);
                    if ($googleEventId) {
                        $reminder->google_event_id = $googleEventId;
                        $reminder->save();
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error syncing updated Reminder ID {$reminder->id} to Google Calendar: " . $e->getMessage());
            }
        }

        return redirect()->route('reminders.index')
                         ->with('success', 'Reminder updated successfully!');
    }

    /**
     * Remove the specified resource from storage and sync deletion to Google Calendar.
     */
    public function destroy(Reminder $reminder): RedirectResponse
    {
        if ($reminder->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        $googleEventIdToDelete = $reminder->google_event_id;

        if ($user->google_refresh_token && $googleEventIdToDelete) {
            try {
                $this->googleCalendarService->deleteEvent($user, $googleEventIdToDelete);
            } catch (\Exception $e) {
                Log::error("Error deleting Google Calendar event ID {$googleEventIdToDelete}: " . $e->getMessage());
            }
        }

        $reminder->delete();

        return redirect()->route('reminders.index')
                         ->with('success', 'Reminder deleted successfully!');
    }

    /**
     * Display the calendar view page.
     */
    public function calendar(): View
    {
        // Just return the view, JavaScript will fetch events
        return view('reminders.calendar');
    }

    /**
     * Fetch reminder data formatted for FullCalendar.
     */
    public function getEvents(Request $request): JsonResponse
    {
        $reminders = Auth::user()->reminders()->get();

        // Format reminders into FullCalendar event objects
        $events = $reminders->map(function (Reminder $reminder) {
            return [
                'id' => $reminder->id,
                'title' => $reminder->title,
                'start' => $reminder->reminder_time->toIso8601String(),
                'end' => $reminder->reminder_time->copy()->addHour()->toIso8601String(),
                'url' => route('reminders.edit', $reminder),
                'description' => $reminder->description,
            ];
        });

        return response()->json($events);
    }
}