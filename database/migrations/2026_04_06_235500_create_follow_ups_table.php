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
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->foreignId('user_id')->comment('Followed By Staff')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['Current Only', 'Both', 'Next Only']);

            // Current Interaction Details
            $table->string('follow_up_type')->nullable(); // e.g., Call, Meeting, WhatsApp
            $table->dateTime('interaction_date_time')->nullable();
            $table->string('duration')->nullable(); // Call Duration
            $table->string('recording_link')->nullable();
            $table->text('notes')->nullable(); // Interaction Notes

            // Next Follow Up Schedule
            $table->dateTime('next_follow_up_date_time')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
