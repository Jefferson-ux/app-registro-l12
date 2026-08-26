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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name', 150);
            $table->string('business_name', 200)->nullable();

            //
            $table->string('tax_id', 50)->nullable();

            $table->string('email', 150);
            $table->string('phone', 50);

            $table->string('country', 100)->nullable();
            $table->string('timezone', 100)->default("America/Lima");

            $table->string('logo', 255)->nullable();

            $table->enum('status', ['trial', 'active', 'suspended', 'inactive'])->default('trial');

            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
