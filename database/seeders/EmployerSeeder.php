<?php

namespace Database\Seeders;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class EmployerSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->employers() as $employerData) {
            $email = mb_strtolower($employerData['user']['email']);
            $lookupHash = User::emailLookupHash($email);

            $user = User::updateOrCreate(
                ['email_lookup_hash' => $lookupHash],
                [
                    'name' => $employerData['user']['name'],
                    'email' => $email,
                    'email_lookup_hash' => $lookupHash,
                    'password' => Hash::make($employerData['user']['password']),
                    'user_type' => 'employer',
                    'is_email_verified' => true,
                    'email_verified_at' => Carbon::now(),
                ]
            );

            Employer::updateOrCreate(
                ['user_id' => $user->id],
                $employerData['profile']
            );
        }
    }

    private function employers(): array
    {
        return [
            [
                'user' => [
                    'name' => 'Nafisa Karim',
                    'email' => 'careers@northstardigital.example.com',
                    'password' => 'password123',
                ],
                'profile' => [
                    'company_name' => 'Northstar Digital',
                    'company_location' => 'Gulshan 2, Dhaka',
                    'company_website' => 'https://northstardigital.example.com',
                    'company_description' => 'A product engineering company building modern SaaS tools for regional and global clients.',
                    'company_size' => '120-200 employees',
                    'industry' => 'Software Development',
                    'founded_year' => '2016',
                ],
            ],
            [
                'user' => [
                    'name' => 'Mahmudul Hasan',
                    'email' => 'hiring@finverse.example.com',
                    'password' => 'password123',
                ],
                'profile' => [
                    'company_name' => 'Finverse Analytics',
                    'company_location' => 'Banani, Dhaka',
                    'company_website' => 'https://finverse.example.com',
                    'company_description' => 'A fintech analytics platform focused on secure payments, lending insights, and risk tooling.',
                    'company_size' => '80-120 employees',
                    'industry' => 'Financial Technology',
                    'founded_year' => '2019',
                ],
            ],
            [
                'user' => [
                    'name' => 'Sabira Tasnim',
                    'email' => 'talent@pixelcraft.example.com',
                    'password' => 'password123',
                ],
                'profile' => [
                    'company_name' => 'Pixelcraft Studio',
                    'company_location' => 'Dhanmondi, Dhaka',
                    'company_website' => 'https://pixelcraft.example.com',
                    'company_description' => 'A design and growth studio partnering with startups on branding, UX, and content systems.',
                    'company_size' => '25-40 employees',
                    'industry' => 'Design and Marketing',
                    'founded_year' => '2020',
                ],
            ],
            [
                'user' => [
                    'name' => 'Rezaul Haque',
                    'email' => 'jobs@healthbridge.example.com',
                    'password' => 'password123',
                ],
                'profile' => [
                    'company_name' => 'HealthBridge',
                    'company_location' => 'Uttara, Dhaka',
                    'company_website' => 'https://healthbridge.example.com',
                    'company_description' => 'A health operations platform improving care coordination for clinics and hospitals.',
                    'company_size' => '60-90 employees',
                    'industry' => 'Healthcare Technology',
                    'founded_year' => '2018',
                ],
            ],
        ];
    }
}
