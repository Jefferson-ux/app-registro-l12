<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();

            $table->string('tenant_name', 150)->nullable()->after('tenant_id');
            $table->string('tenant_ruc', 20)->nullable()->after('tenant_name');
            $table->unsignedBigInteger('branch_id')->nullable()->after('tenant_ruc');
            $table->string('user_name', 150)->nullable()->after('user_id');

            $table->dropColumn('user_agent');

            $table->index('created_at');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        //
    }
};