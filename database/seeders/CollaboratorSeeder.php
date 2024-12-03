<?php

namespace Database\Seeders;

use App\Models\Collaborator;
use Illuminate\Database\Seeder;

class CollaboratorSeeder extends Seeder
{
    public function run()
    {
        Collaborator::factory()->count(50)->create();
    }
}
