<?php

namespace Tests\Feature;

use App\Models\Partie;
use App\Models\Theme;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PartieTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_demarrage_et_partie_unique(): void
    {
        $joueur = $this->creerJoueur();

        $partie = $this->demarrerPartie($joueur);

        $this->assertSame(
            7,
            $partie->questionsPosees()->count()
        );

        $this->postJson('/api/parties/demarrer', [
            'role' => 'front',
            'numero_niveau' => 1,
        ])->assertStatus(409);

        $this->assertDatabaseCount('parties', 1);
    }

    public function test_abandon_permet_nouvelle_partie(): void
    {
        $joueur = $this->creerJoueur();

        $partie = $this->demarrerPartie($joueur);

        $this->patchJson(
            "/api/parties/{$partie->id}/abandonner"
        )->assertOk();

        $partie->refresh();

        $this->assertSame(
            'abandonnee',
            $partie->statut
        );

        $this->assertNotNull($partie->date_fin);

        $this->postJson('/api/parties/demarrer', [
            'role' => 'mobile',
            'numero_niveau' => 1,
        ])->assertCreated();

        $this->assertDatabaseCount('parties', 2);
    }

    public function test_double_reponse_refusee(): void
    {
        $joueur = $this->creerJoueur();

        $partie = $this->demarrerPartie($joueur);

        $questionPosee = $partie
            ->questionsPosees()
            ->with('question')
            ->orderBy('ordre')
            ->firstOrFail();

        $corps = [
            'question_posee_id' => $questionPosee->id,
            'reponse' =>
                $questionPosee->question->reponse_correcte,
        ];

        $this->postJson(
            "/api/parties/{$partie->id}/repondre",
            $corps
        )
            ->assertOk()
            ->assertJsonPath('points_gagnes', 1000)
            ->assertJsonPath('score_total', 1000);

        $this->postJson(
            "/api/parties/{$partie->id}/repondre",
            $corps
        )->assertStatus(422);

        $partie->refresh();
        $questionPosee->refresh();

        $this->assertSame(1000, $partie->score_total);
        $this->assertSame(
            1,
            $partie->questions_correctes_total
        );
        $this->assertTrue($questionPosee->est_correcte);
    }

    public function test_niveau_parfait_met_a_jour_score(): void
    {
        $joueur = $this->creerJoueur();

        $partie = $this->demarrerPartie($joueur);

        $questionsPosees = $partie
            ->questionsPosees()
            ->with('question')
            ->orderBy('ordre')
            ->get();

        foreach ($questionsPosees as $questionPosee) {
            $this->postJson(
                "/api/parties/{$partie->id}/repondre",
                [
                    'question_posee_id' =>
                        $questionPosee->id,
                    'reponse' =>
                        $questionPosee
                            ->question
                            ->reponse_correcte,
                ]
            )->assertOk();
        }

        $partie->refresh();
        $joueur->refresh();

        $this->assertSame(
            'niveau_reussi',
            $partie->statut
        );

        $this->assertSame(7000, $partie->score_total);
        $this->assertSame(7000, $joueur->meilleur_score);

        $themeUn = Theme::query()
            ->where('numero_niveau', 1)
            ->firstOrFail();

        $themeDeux = Theme::query()
            ->where('numero_niveau', 2)
            ->firstOrFail();

        $progression = $joueur
            ->progressionsNiveaux()
            ->where('theme_id', $themeUn->id)
            ->firstOrFail();

        $this->assertSame(
            7000,
            $progression->meilleur_score
        );

        $this->assertNotNull($progression->reussi_le);

        $this->assertDatabaseHas(
            'progression_niveaux',
            [
                'utilisateur_id' => $joueur->id,
                'theme_id' => $themeDeux->id,
                'est_deverrouille' => true,
            ]
        );
    }

    private function creerJoueur(): Utilisateur
    {
        $joueur = Utilisateur::factory()->create();

        Theme::query()
            ->orderBy('numero_niveau')
            ->each(function (Theme $theme) use (
                $joueur
            ): void {
                $joueur
                    ->progressionsNiveaux()
                    ->create([
                        'theme_id' => $theme->id,
                        'meilleur_score' => 0,
                        'est_deverrouille' =>
                            (int)$theme->numero_niveau === 1,
                    ]);
            });

        Sanctum::actingAs($joueur, ['access']);

        return $joueur;
    }

    private function demarrerPartie(Utilisateur $joueur): Partie
    {
        $this->postJson('/api/parties/demarrer', [
            'role' => 'front',
            'numero_niveau' => 1,
        ])
            ->assertCreated()
            ->assertJsonPath('nombre_questions', 7);

        return $joueur
            ->parties()
            ->latest('id')
            ->firstOrFail();
    }
}
