<?php

namespace Database\Seeders;

use App\Models\Theme;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdministrateurSeeder extends Seeder
{
    public function run(): void
    {
        $motDePasse = config(
            'clavierdor.admin.mot_de_passe'
        );

        if (!$motDePasse) {
            throw new RuntimeException(
                'La variable ADMIN_PASSWORD doit être définie.'
            );
        }

        $administrateur = Utilisateur::firstOrNew([
            'email' => config('clavierdor.admin.email'),
        ]);

        $administrateur->pseudo = config('clavierdor.admin.pseudo');

        $administrateur->mot_de_passe = $motDePasse;
        $administrateur->est_admin = true;

        $administrateur->email_verifie_le ??= now();

        $administrateur->save();

        Theme::orderBy('numero_niveau')
            ->each(function (Theme $theme) use (
                $administrateur
            ): void {
                $administrateur
                    ->progressionsNiveaux()
                    ->firstOrCreate(
                        [
                            'theme_id' => $theme->id,
                        ],
                        [
                            'meilleur_score' => 0,
                            'est_deverrouille' => $theme->numero_niveau === 1,
                        ]
                    );
            });
    }
}
