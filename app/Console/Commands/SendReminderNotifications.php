<?php

namespace App\Console\Commands;

use App\Mail\ReminderNotification; // Import Mailable
use App\Models\Reminder;           // Import Reminder model
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;   // Import Log facade
use Illuminate\Support\Facades\Mail; // Import Mail facade
use Carbon\Carbon;                  // Import Carbon for time comparison

class SendReminderNotifications extends Command
{
    /**
     * The name and signature of the console command.
     * This is how you run it manually: php artisan reminders:send
     *
     * @var string
     */
    protected $signature = 'reminders:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for due reminders and send email notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for due reminders...');

        // Get the current time (consider timezone if necessary)
        // Using Carbon ensures consistent time handling
        // Find reminders due within the *next* minute to avoid missing any
        $now = Carbon::now();
        $startTime = $now->copy()->second(0);          // e.g., 10:30:00
        $endTime = $now->copy()->second(0)->addMinute(); // e.g., 10:31:00

        // Query for reminders due in the next minute that haven't been sent
        $reminders = Reminder::where('is_notification_sent', false)
                             ->where('reminder_time', '>=', $startTime)
                             ->where('reminder_time', '<', $endTime)
                             ->with('user') // Eager load user to avoid N+1 queries
                             ->get();

        if ($reminders->isEmpty()) {
            $this->info('No reminders due right now.');
            return 0; // Exit command successfully
        }

        $this->info("Found {$reminders->count()} reminders to notify.");

        foreach ($reminders as $reminder) {
            try {
                // Send to the owner
                Mail::to($reminder->user)->send(new ReminderNotification($reminder));
                $this->line(" - Queued notification for '{$reminder->title}' to user {$reminder->user->email}");

                // Send to guests if any
                if (!empty($reminder->guest_emails)) {
                    // Simple comma-separated parsing, trim whitespace
                    $guestEmails = array_filter(array_map('trim', explode(',', $reminder->guest_emails)));

                    if (!empty($guestEmails)) {
                        // You could send one email CC'ing/BCC'ing guests,
                        // or loop and send individually. Sending individually
                        // might be better if you want separate queue jobs.
                        Mail::to($guestEmails)->send(new ReminderNotification($reminder));
                         $this->line("   - Queued notification for '{$reminder->title}' to guests: " . implode(', ', $guestEmails));
                        // Note: Consider validating guest emails more thoroughly earlier.
                    }
                }

                // Mark as sent *after* successfully queueing
                $reminder->is_notification_sent = true;
                $reminder->save();

            } catch (\Exception $e) {
                // Log any errors during the mailing process for a specific reminder
                Log::error("Failed to queue reminder notification for reminder ID {$reminder->id}: " . $e->getMessage());
                $this->error("   - Failed to queue notification for reminder ID {$reminder->id}. Check logs.");
                // Optionally decide if you want to retry later or skip
            }
        }

        $this->info('Finished sending reminders.');
        return 0; // Exit command successfully
    }
}