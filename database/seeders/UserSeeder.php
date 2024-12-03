<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'gestor@empresa.com'], // Condição para evitar duplicatas
            [
                'name' => 'Gestor Inicial',
                'email' => 'gestor@empresa.com',//ponha seu email se quiser
                'password' => Hash::make('senha123'), // Certifique-se de usar o Hash
            ]
        );
    }
}