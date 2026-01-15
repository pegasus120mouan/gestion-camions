<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $pinAdmin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $pinAgent = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $pinChauffeur = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        User::updateOrCreate(
            ['login' => 'admin'],
            ['name' => 'admin', 'prenom' => 'Admin', 'contact' => '0700000000', 'matricule' => 'ADM-0001', 'avatar' => null, 'password' => Hash::make('password'), 'code_pin' => Hash::make($pinAdmin), 'role' => 'admin']
        );

        User::updateOrCreate(
            ['login' => 'agent'],
            ['name' => 'agent', 'prenom' => 'Agent', 'contact' => '0700000001', 'matricule' => 'AGT-0001', 'avatar' => null, 'password' => Hash::make('password'), 'code_pin' => Hash::make($pinAgent), 'role' => 'agent']
        );

        User::updateOrCreate(
            ['login' => 'chauffeur'],
            ['name' => 'chauffeur', 'prenom' => 'Chauffeur', 'contact' => '0700000002', 'matricule' => 'DRV-0001', 'avatar' => null, 'password' => Hash::make('password'), 'code_pin' => Hash::make($pinChauffeur), 'role' => 'driver']
        );

        User::factory()->count(10)->create();

        $this->call(PontPesageSeeder::class);
        $this->call(CamionSeeder::class);
    }
}
