<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connection_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending')->index();
            $table->timestamps();
            $table->index(['sender_id', 'receiver_id', 'status']);
        });

        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_one_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_two_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('accepted_request_id')->constrained('connection_requests')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_one_id', 'user_two_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connections');
        Schema::dropIfExists('connection_requests');
    }
};
