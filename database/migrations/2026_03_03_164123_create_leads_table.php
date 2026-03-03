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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cast')->nullable();
            $table->string('contact_number');
            $table->string('inquiry_for')->nullable();
            $table->string('interested_area')->nullable();
            $table->string('area_latitude')->nullable();
            $table->string('area_longitude')->nullable();
            $table->string('min_budget')->nullable();
            $table->string('max_budget')->nullable();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('lead_status_id')->constrained()->onDelete('cascade');
            $table->foreignId('lead_source_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
