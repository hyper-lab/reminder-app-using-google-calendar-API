<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            // Add column after 'google_event_id', default to false (0)
            $table->boolean('is_notification_sent')->default(false)->after('google_event_id');
            $table->index('reminder_time'); // Add index for faster querying
            $table->index('is_notification_sent'); // Add index for faster querying
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropIndex(['reminder_time']); // Drop index if needed
            $table->dropIndex(['is_notification_sent']); // Drop index if needed
            $table->dropColumn('is_notification_sent');
        });
    }
};