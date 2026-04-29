<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecc_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('public_x');
            $table->text('public_y');
            $table->text('private_d');
            $table->string('curve_name')->default('LAB_CUSTOM_CURVE');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->timestamp('rotated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecc_keys');
    }
};
