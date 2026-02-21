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
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['category', 'type']);
            $table->foreignId('category_id')->nullable()->after('builder_id')->constrained('categories')->onDelete('set null');
            $table->foreignId('property_type_id')->nullable()->after('category_id')->constrained('property_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['property_type_id']);
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'property_type_id']);
            $table->string('category')->nullable()->after('name');
            $table->string('type')->nullable()->after('category');
        });
    }
};
