<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Exception; // Use base Exception class
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarServiceApi; // Alias for the Calendar service
use Google\Service\Calendar\Event as GoogleCalendarEvent; // Alias for the Event object
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventAttendee;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected GoogleClient $client;
    protected GoogleCalendarServiceApi $service;

    /**
     * Get an authenticated Google API client for the user.
     * Handles token refresh automatically.
     *
     * @param User $user
     * @return GoogleClient|null Returns authenticated client or null on failure
     */
    protected function getClient(User $user): ?GoogleClient
    {
        // Check if user has essential tokens
        if (empty($user->google_access_token) || empty($user->google_refresh_token)) {
            Log::warning("User ID {$user->id} missing Google Calendar tokens.");
            return null; // Cannot proceed without tokens
        }

        try {
            $client = new GoogleClient();
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
            // No need to set redirectUri here, only for auth flow

            // Set the access token obtained previously
            $client->setAccessToken([
                'access_token' => $user->google_access_token,
                'refresh_token' => $user->google_refresh_token,
                'expires_in' => $user->google_token_expires_at->getTimestamp() - time() // Calculate remaining seconds
            ]);

            // Check if the access token has expired
            if ($client->isAccessTokenExpired()) {
                Log::info("Google token expired for User ID {$user->id}. Attempting refresh.");
                // Attempt to refresh the token using the refresh token
                $newAccessToken = $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);

                // Check for errors during refresh
                if (isset($newAccessToken['error'])) {
                    Log::error("Google token refresh failed for User ID {$user->id}: " . ($newAccessToken['error_description'] ?? $newAccessToken['error']));
                    // Clear potentially invalid tokens? Maybe just log and return null.
                    // $user->google_access_token = null;
                    // $user->google_refresh_token = null; // Refresh token might be invalid too
                    // $user->google_token_expires_at = null;
                    // $user->save();
                    return null;
                }

                // Update the client with the new token info
                $client->setAccessToken($newAccessToken);

                // Update the user's record with the new token details
                // Note: Refresh token might not always be returned on refresh, only store if present
                $user->google_access_token = $newAccessToken['access_token'];
                if (isset($newAccessToken['refresh_token'])) {
                   $user->google_refresh_token = $newAccessToken['refresh_token'];
                }
                $user->google_token_expires_at = Carbon::now()->addSeconds($newAccessToken['expires_in'] - 60); // Add buffer
                $user->save();
                Log::info("Google token refreshed successfully for User ID {$user->id}.");
            }

            return $client;

        } catch (\Exception $e) {
             Log::error("Exception while getting Google Client for User ID {$user->id}: " . $e->getMessage());
             return null;
        }
    }


    /**
     * Add a reminder event to the user's primary Google Calendar.
     *
     * @param Reminder $reminder
     * @return string|null Google Event ID on success, null on failure
     */
    public function addEvent(Reminder $reminder): ?string
    {
        $user = $reminder->user; // Get the owner
        $client = $this->getClient($user);

        if (!$client) {
            Log::error("Cannot add GCal event for Reminder ID {$reminder->id}: Failed to get authenticated client for User ID {$user->id}.");
            return null;
        }

        try {
            $this->service = new GoogleCalendarServiceApi($client);
            $calendarId = 'primary'; // Use the user's primary calendar

            $event = new GoogleCalendarEvent([
                'summary' => $reminder->title,
                'description' => $reminder->description,
                'start' => $this->formatDateTimeForGoogle($reminder->reminder_time),
                // Google Calendar typically requires an end time.
                // Let's default to 1 hour duration for simplicity. Adjust as needed.
                'end' => $this->formatDateTimeForGoogle($reminder->reminder_time->copy()->addHour()),
                'attendees' => $this->formatAttendees($reminder->guest_emails),
                // Add reminder notification (e.g., email 10 mins before) - Optional
                'reminders' => [
                    'useDefault' => false, // Use custom reminder, not calendar default
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 10], // Email 10 mins before
                       // ['method' => 'popup', 'minutes' => 15], // Popup 15 mins before (if needed)
                    ],
                ],
                // You can add more details like location, recurrence etc.
            ]);

            $createdEvent = $this->service->events->insert($calendarId, $event);
            Log::info("Created Google Calendar event ID {$createdEvent->getId()} for Reminder ID {$reminder->id}.");
            return $createdEvent->getId(); // Return the unique Google Event ID

        } catch (\Exception $e) {
            Log::error("Failed to create Google Calendar event for Reminder ID {$reminder->id}: " . $e->getMessage());
            // Handle specific errors? e.g., quota limits, invalid data
            return null;
        }
    }

    /**
     * Update an existing event in Google Calendar.
     *
     * @param Reminder $reminder Requires reminder->google_event_id to be set
     * @return string|null Google Event ID on success, null on failure
     */
    public function updateEvent(Reminder $reminder): ?string
    {
        if (empty($reminder->google_event_id)) {
             Log::warning("Cannot update GCal event for Reminder ID {$reminder->id}: Missing google_event_id.");
            return null;
        }

        $user = $reminder->user;
        $client = $this->getClient($user);

        if (!$client) {
             Log::error("Cannot update GCal event for Reminder ID {$reminder->id}: Failed to get client for User ID {$user->id}.");
            return null;
        }

         try {
            $this->service = new GoogleCalendarServiceApi($client);
            $calendarId = 'primary';
            $eventId = $reminder->google_event_id;

            // First, get the existing event (optional, but good practice)
            // $existingEvent = $this->service->events->get($calendarId, $eventId);
            // Note: Getting the event first isn't strictly required for update,
            // but useful if you need to merge properties. For simplicity, we'll overwrite.

             $event = new GoogleCalendarEvent([
                'summary' => $reminder->title,
                'description' => $reminder->description,
                'start' => $this->formatDateTimeForGoogle($reminder->reminder_time),
                'end' => $this->formatDateTimeForGoogle($reminder->reminder_time->copy()->addHour()), // Keep 1hr duration
                'attendees' => $this->formatAttendees($reminder->guest_emails),
                // Keep existing reminders or redefine them
                 'reminders' => [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 10],
                    ],
                ],
            ]);

            $updatedEvent = $this->service->events->update($calendarId, $eventId, $event);
            Log::info("Updated Google Calendar event ID {$updatedEvent->getId()} for Reminder ID {$reminder->id}.");
            return $updatedEvent->getId();

         } catch (\Exception $e) {
             // Handle specific errors like "404 Not Found" if event was deleted from GCal directly
             if ($e->getCode() == 404) {
                 Log::warning("Could not update GCal event for Reminder ID {$reminder->id}: Event ID {$reminder->google_event_id} not found on Google Calendar.");
                 // Clear the local google_event_id?
                 // $reminder->google_event_id = null;
                 // $reminder->save();
             } else {
                Log::error("Failed to update Google Calendar event for Reminder ID {$reminder->id}: " . $e->getMessage());
             }
             return null;
         }
    }

     /**
     * Delete an event from Google Calendar.
     *
     * @param User $user
     * @param string $googleEventId
     * @return bool True on success, false on failure
     */
    public function deleteEvent(User $user, string $googleEventId): bool
    {
         if (empty($googleEventId)) return false; // Nothing to delete

         $client = $this->getClient($user);
         if (!$client) {
             Log::error("Cannot delete GCal event ID {$googleEventId}: Failed to get client for User ID {$user->id}.");
             return false;
         }

         try {
             $this->service = new GoogleCalendarServiceApi($client);
             $calendarId = 'primary';

             $this->service->events->delete($calendarId, $googleEventId);
             Log::info("Deleted Google Calendar event ID {$googleEventId} for User ID {$user->id}.");
             return true;

         } catch (\Exception $e) {
             // Handle 404 Not Found gracefully (event might already be deleted)
             if ($e->getCode() == 404) {
                Log::warning("Attempted to delete GCal event ID {$googleEventId} for User ID {$user->id}, but it was not found (already deleted?).");
                return true; // Consider it successful if it's already gone
             } else {
                Log::error("Failed to delete Google Calendar event ID {$googleEventId} for User ID {$user->id}: " . $e->getMessage());
                return false;
             }
         }
    }

    /**
     * Helper to format Carbon instance into Google Calendar DateTime object.
     *
     * @param Carbon $dateTime
     * @return EventDateTime
     */
    protected function formatDateTimeForGoogle(Carbon $dateTime): EventDateTime
    {
        return new EventDateTime([
            // Use RFC3339 format which includes timezone offset
            'dateTime' => $dateTime->toRfc3339String(),
            // Specify the timezone identifier
            'timeZone' => $dateTime->getTimezone()->getName(), // e.g., 'Asia/Manila', 'UTC'
        ]);
    }

     /**
      * Helper to format comma-separated emails into Google Attendee array.
      *
      * @param string|null $guestEmailString
      * @return array<EventAttendee>
      */
     protected function formatAttendees(?string $guestEmailString): array
     {
        $attendees = [];
        if (!empty($guestEmailString)) {
            // Split by comma, trim whitespace, remove empty entries, validate email format
            $emails = array_filter(array_map('trim', explode(',', $guestEmailString)), function($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            });

            foreach ($emails as $email) {
                $attendees[] = new EventAttendee(['email' => $email]);
            }
        }
        return $attendees;
     }

}