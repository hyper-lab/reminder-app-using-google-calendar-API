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
        Schema::table('users', function (Blueprint $table) {
            // Add the google_id column after 'email'
            $table->string('google_id')->nullable()->unique()->after('email');
            // Make password nullable for socialite users who don't set one
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
            // Revert password nullability ONLY IF it wasn't nullable before
            // Check your initial create_users_table migration.
            // If it was $table->string('password'); uncomment the next line.
            // $table->string('password')->nullable(false)->change();
        });
    }
};