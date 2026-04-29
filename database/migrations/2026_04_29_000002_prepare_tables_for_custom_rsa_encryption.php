<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_lookup_hash')) {
                $table->string('email_lookup_hash', 64)->nullable()->unique()->after('email');
            }
        });

        $this->makeText('users', ['name', 'email', 'mobile', 'designation']);
        $this->makeText('jobs', [
            'title',
            'salary',
            'location',
            'description',
            'benefits',
            'responsibility',
            'qualifications',
            'keywords',
            'experience',
            'company_name',
            'company_location',
            'company_website',
        ]);
        $this->makeText('employers', [
            'company_name',
            'company_location',
            'company_website',
            'company_description',
            'company_size',
            'industry',
            'founded_year',
        ]);
        $this->makeText('applicants', ['institute', 'degree', 'cgpa', 'passing_year', 'experience']);
        $this->makeText('notifications', ['message']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email_lookup_hash')) {
                $table->dropUnique(['email_lookup_hash']);
                $table->dropColumn('email_lookup_hash');
            }
        });
    }

    private function makeText(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $driver = DB::getDriverName();

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                continue;
            }

            if ($driver === 'mysql') {
                if ($table === 'users' && $column === 'email') {
                    $this->dropIndexIfExists('users', 'users_email_unique');
                }
                DB::statement("ALTER TABLE {$table} MODIFY {$column} TEXT NULL");
            }
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        try {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$index}");
        } catch (Throwable) {
            // The index may already be absent on a migrated database.
        }
    }
};
