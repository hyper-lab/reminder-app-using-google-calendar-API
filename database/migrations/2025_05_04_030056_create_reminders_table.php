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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id(); // Primary key (bigint unsigned)
            // Foreign key linking to the users table
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title'); // Reminder title (subject)
            $table->text('description')->nullable(); // Optional longer description
            $table->timestamp('reminder_time'); // Date and time for the reminder
            $table->text('guest_emails')->nullable(); // Optional comma-separated guest emails
            $table->string('google_event_id')->nullable()->unique(); // For Google Calendar sync later
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};