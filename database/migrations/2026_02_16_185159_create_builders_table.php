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
        Schema::create('builders', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('name');
            $table->string('company_logo')->nullable();
            $table->string('experience')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('contact_number');
            $table->string('email')->unique();
            $table->string('website')->nullable();
            $table->text('office_address');
            $table->integer('total_project_completed')->default(0);
            $table->integer('ongoing_projects')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('builders');
    }
};
