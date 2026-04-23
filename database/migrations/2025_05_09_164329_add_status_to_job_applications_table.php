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
        // Targeting 'job_applications' to match your previous migration
        Schema::table('job_applications', function (Blueprint $table) {
            // We add 'status' as a string, default it to 'pending', 
            // and place it after the 'employer_user_id' for a clean schema.
            $table->string('status')->default('pending')->after('employer_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            // This allows you to roll back this specific change
            $table->dropColumn('status');
        });
    }
};