<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('otp_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mac_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key_name')->index();
            $table->longText('key_value');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->timestamp('rotated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
        });

        foreach (['users', 'jobs', 'employers', 'applicants', 'notifications'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'encrypted_payload_mac')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->longText('encrypted_payload_mac')->nullable()->after('updated_at');
                });
            }
        }

        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'sender_mac')) {
                $table->longText('sender_mac')->nullable()->after('sender_encrypted_body');
            }
            if (!Schema::hasColumn('messages', 'receiver_mac')) {
                $table->longText('receiver_mac')->nullable()->after('receiver_encrypted_body');
            }
        });

        Schema::table('ecc_keys', function (Blueprint $table) {
            if (!Schema::hasColumn('ecc_keys', 'private_d_encrypted')) {
                $table->longText('private_d_encrypted')->nullable()->after('private_d');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE ecc_keys MODIFY private_d TEXT NULL');
            DB::statement('ALTER TABLE rsa_keys MODIFY private_d TEXT NULL');
            DB::statement('ALTER TABLE rsa_keys MODIFY prime_p TEXT NULL');
            DB::statement('ALTER TABLE rsa_keys MODIFY prime_q TEXT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('login_otps');
        Schema::dropIfExists('mac_keys');
    }
};
