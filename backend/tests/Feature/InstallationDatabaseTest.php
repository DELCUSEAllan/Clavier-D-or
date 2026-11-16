<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Theme;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallationDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_base_peut_etre_initialisee(): void
    {
        $this->seed();

        $this->assertDatabaseCount('themes', 6);

        $this->assertTrue(Question::query()->exists());

        $administrateur = Utilisateur::query()
            ->where('email', 'admin@example.test')
            ->firstOrFail();

        $this->assertTrue($administrateur->est_admin);

        $this->assertDatabaseCount(
            'progression_niveaux',
            6
        );

        $niveauUn = Theme::query()
            ->where('numero_niveau', 1)
            ->firstOrFail();

        $this->assertDatabaseHas(
            'progression_niveaux',
            [
                'utilisateur_id' => $administrateur->id,
                'theme_id' => $niveauUn->id,
                'est_deverrouille' => true,
            ]
        );
    }
}
