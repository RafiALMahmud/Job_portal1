<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class ApplicantSeeder extends Seeder
{
    /**
     * Seed applicants with fixed sample data (no Faker/factories).
     */
    public function run(): void
    {
        $aspirants = [
            [
                'name' => 'Amina Rahman',
                'email' => 'amina.rahman@example.com',
                'password' => Hash::make('password123'),
                'user_type' => 'aspirant',
            ],
            [
                'name' => 'Tanvir Hasan',
                'email' => 'tanvir.hasan@example.com',
                'password' => Hash::make('password123'),
                'user_type' => 'aspirant',
            ],
            [
                'name' => 'Nusrat Jahan',
                'email' => 'nusrat.jahan@example.com',
                'password' => Hash::make('password123'),
                'user_type' => 'aspirant',
            ],
        ];

        foreach ($aspirants as $aspirant) {
            $email = mb_strtolower($aspirant['email']);
            $lookupHash = User::emailLookupHash($email);

            User::updateOrCreate(
                ['email_lookup_hash' => $lookupHash],
                [
                    'name' => $aspirant['name'],
                    'email' => $email,
                    'email_lookup_hash' => $lookupHash,
                    'password' => $aspirant['password'],
                    'user_type' => $aspirant['user_type'],
                    'email_verified_at' => Carbon::now(),
                    'is_email_verified' => true,
                ]
            );
        }

        $jobs = Job::query()->orderBy('id')->limit(3)->get();
        if ($jobs->isEmpty()) {
            return;
        }

        $users = User::query()
            ->whereIn('email_lookup_hash', array_map(
                fn (string $email) => User::emailLookupHash($email),
                array_column($aspirants, 'email')
            ))
            ->orderBy('id')
            ->get();

        foreach ($users as $index => $user) {
            $job = $jobs[$index % $jobs->count()];

            Applicant::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'job_id' => $job->id,
                ],
                [
                    'institute' => 'University of Dhaka',
                    'degree' => 'BSc in Computer Science',
                    'cgpa' => '3.70',
                    'passing_year' => '2024',
                    'experience' => '1 year',
                    'status' => 'pending',
                    'applied_date' => Carbon::now(),
                ]
            );
        }
    }
}
