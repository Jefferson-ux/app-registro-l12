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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();

            $table->text('description')->nullable();

            $table->unsignedInteger('max_employees')->nullable();
            $table->unsignedInteger('max_users')->nullable();
            $table->unsignedInteger('max_branches')->nullable();

            $table->decimal('price', 10, 2)->default(0.00);
            // ? campo agregado para verificar moneda ISO
            $table->char('currency', 3)->default('PEN'); // Moneda ISO (PEN = Soles Peruanos)

            $table->enum('billing_period', ['monthly', 'yearly'])
                ->default('monthly');

            $table->boolean('status')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
