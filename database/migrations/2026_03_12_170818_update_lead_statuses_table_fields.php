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
        Schema::table('lead_statuses', function (Blueprint $table) {
            $table->string('color')->nullable()->after('name');
            $table->string('icon')->nullable()->after('color');
            $table->dropColumn('is_final');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_statuses', function (Blueprint $table) {
            $table->boolean('is_final')->default(false)->after('is_initial');
            $table->dropColumn(['color', 'icon']);
        });
    }
};
