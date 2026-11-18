<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_METHODS = ['web', 'mobile', 'kiosk', 'qr', 'manual', 'biometric', 'api'];

    private const METHODS = ['manual', 'qr', 'facial'];

    private const TRANSITION_METHODS = ['web', 'mobile', 'kiosk', 'qr', 'manual', 'biometric', 'api', 'facial'];

    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->enum('method', self::TRANSITION_METHODS)->change();
            });
        }

        DB::table('attendance_records')
            ->whereIn('method', ['web', 'mobile', 'kiosk', 'api'])
            ->update(['method' => 'manual']);

        DB::table('attendance_records')
            ->where('method', 'biometric')
            ->update(['method' => 'facial']);

        if (DB::getDriverName() === 'mysql') {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->enum('method', self::METHODS)->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->enum('method', self::LEGACY_METHODS)->change();
            });
        }

        DB::table('attendance_records')
            ->where('method', 'facial')
            ->update(['method' => 'biometric']);

        DB::table('attendance_records')
            ->where('method', 'manual')
            ->update(['method' => 'web']);

        if (DB::getDriverName() === 'mysql') {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->enum('method', self::LEGACY_METHODS)->change();
            });
        }
    }
};
