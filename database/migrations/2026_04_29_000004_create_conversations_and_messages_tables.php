<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_MESSAGES_TABLE = 'messages_legacy_20260429';

    public function up(): void
    {
        if (!Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_one_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('user_two_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();
                $table->unique(['user_one_id', 'user_two_id']);
            });
        }

        $this->prepareMessagesTable();

        if (!Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
                $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('sender_ecc_key_id')->constrained('ecc_keys')->onDelete('restrict');
                $table->foreignId('receiver_ecc_key_id')->constrained('ecc_keys')->onDelete('restrict');
                $table->longText('sender_encrypted_body');
                $table->longText('receiver_encrypted_body');
                $table->string('encryption_algorithm')->default('CUSTOM_ECC_ELGAMAL');
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');

        if (Schema::hasTable(self::LEGACY_MESSAGES_TABLE) && !Schema::hasTable('messages')) {
            Schema::rename(self::LEGACY_MESSAGES_TABLE, 'messages');
        }
    }

    private function prepareMessagesTable(): void
    {
        if (!Schema::hasTable('messages') || $this->hasEncryptedMessagesSchema()) {
            return;
        }

        if (DB::table('messages')->count() === 0) {
            Schema::drop('messages');
            return;
        }

        if (Schema::hasTable(self::LEGACY_MESSAGES_TABLE)) {
            throw new RuntimeException(sprintf(
                'Legacy messages table could not be preserved because "%s" already exists.',
                self::LEGACY_MESSAGES_TABLE
            ));
        }

        Schema::rename('messages', self::LEGACY_MESSAGES_TABLE);
    }

    private function hasEncryptedMessagesSchema(): bool
    {
        foreach ([
            'conversation_id',
            'sender_id',
            'receiver_id',
            'sender_ecc_key_id',
            'receiver_ecc_key_id',
            'sender_encrypted_body',
            'receiver_encrypted_body',
            'encryption_algorithm',
            'is_read',
        ] as $column) {
            if (!Schema::hasColumn('messages', $column)) {
                return false;
            }
        }

        return true;
    }
};
