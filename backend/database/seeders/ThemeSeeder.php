<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            ['nom' => 'Algorithmes', 'numero_niveau' => 1],
            ['nom' => "Métiers de l'informatique", 'numero_niveau' => 2],
            ['nom' => 'Logique', 'numero_niveau' => 3],
            ['nom' => 'Culture générale', 'numero_niveau' => 4],
            ['nom' => 'Anglais', 'numero_niveau' => 5],
            ['nom' => 'Boss', 'numero_niveau' => 6],
        ];

        foreach ($themes as $theme) {
            Theme::updateOrCreate(
                ['numero_niveau' => $theme['numero_niveau']],
                $theme
            );
        }
    }
}
