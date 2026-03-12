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
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('user_id')->comment('Site Visit Executive')->constrained('users')->onDelete('cascade');
            $table->string('unit_type')->nullable();
            $table->dateTime('visit_date');
            $table->enum('visited', ['Yes', 'No', 'Cancelled'])->default('No');
            $table->enum('interest_status', ['Thinking', 'Interested', 'Highly Interested', 'Not Interested', 'Close'])->default('Thinking');
            $table->text('notes')->nullable();
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
