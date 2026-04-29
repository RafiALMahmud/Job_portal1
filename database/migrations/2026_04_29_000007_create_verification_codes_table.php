<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('verification_codes')) {
            Schema::create('verification_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->string('email_lookup_hash')->nullable()->index();
                $table->string('purpose')->index();
                $table->string('code_hash');
                $table->timestamp('expires_at')->index();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('resend_available_at')->nullable();
                $table->timestamp('consumed_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'is_email_verified')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_email_verified')->default(false)->after('email_verified_at');
            });

            DB::table('users')->update([
                'is_email_verified' => true,
                'email_verified_at' => DB::raw('COALESCE(email_verified_at, CURRENT_TIMESTAMP)'),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_email_verified')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_email_verified');
            });
        }

        Schema::dropIfExists('verification_codes');
    }
};
