<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rsa_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key_name')->index();
            $table->text('public_n');
            $table->text('public_e');
            $table->text('private_d');
            $table->text('prime_p');
            $table->text('prime_q');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->timestamp('rotated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rsa_keys');
    }
};
