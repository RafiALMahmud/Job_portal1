<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $email = 'rafi.almahmud.007@gmail.com';
        $lookupHash = User::emailLookupHash($email);

        // Create admin user if it doesn't exist
        if (!User::where('email_lookup_hash', $lookupHash)->exists()) {
            User::create([
                'name' => 'Rafi',
                'email' => $email,
                'email_lookup_hash' => $lookupHash,
                'password' => Hash::make('Rafi0008'),
                'user_type' => 'admin'
            ]);
        } else {
            // Update existing user to admin if not already
            $user = User::where('email_lookup_hash', $lookupHash)->first();
            if ($user->user_type !== 'admin') {
                $user->update(['user_type' => 'admin']);
            }
        }
    }
}
