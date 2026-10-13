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
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->timestamp('break_start_at')->nullable()->after('check_out_at');
            $table->timestamp('break_end_at')->nullable()->after('break_start_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropColumn('break_start_at');
            $table->dropColumn('break_end_at');
        });
    }
};
