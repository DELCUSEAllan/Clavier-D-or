<?php

namespace Tests\Feature;

use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthentificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_connexion_cree_deux_jetons(): void
    {
        $utilisateur = Utilisateur::factory()->create();

        $reponse = $this->postJson('/api/connexion', [
            'email' => $utilisateur->email,
            'mot_de_passe' => 'password',
        ]);

        $reponse
            ->assertOk()
            ->assertJsonPath(
                'utilisateur.id',
                $utilisateur->id
            )
            ->assertJsonStructure([
                'authentification' => [
                    'type',
                    'jeton_acces',
                    'jeton_actualisation',
                    'expiration_acces',
                    'expiration_actualisation',
                ],
            ]);

        $jetons = $utilisateur->tokens()->get();

        $this->assertCount(2, $jetons);

        $this->assertTrue(
            $jetons->contains(
                fn ($jeton): bool =>
                    str_starts_with(
                        $jeton->name,
                        'access:'
                    )
                    && in_array(
                        'access',
                        $jeton->abilities ?? [],
                        true
                    )
                    && $jeton->expires_at !== null
            )
        );

        $this->assertTrue(
            $jetons->contains(fn ($jeton): bool =>str_starts_with(
                        $jeton->name,
                        'refresh:'
                    )
                    && in_array(
                        'refresh',
                        $jeton->abilities ?? [],
                        true
                    )
                    && $jeton->expires_at !== null
            )
        );
    }

    public function test_visiteur_refuse_sur_profil(): void
    {
        $this->getJson('/api/utilisateur')
            ->assertUnauthorized();
    }

    public function test_acces_autorise_sur_profil(): void
    {
        $utilisateur = Utilisateur::factory()->create();

        Sanctum::actingAs(
            $utilisateur,
            ['access']
        );

        $this->getJson('/api/utilisateur')
            ->assertOk()
            ->assertJsonPath(
                'utilisateur.id',
                $utilisateur->id
            );
    }

    public function test_refresh_refuse_sur_profil(): void
    {
        $utilisateur = Utilisateur::factory()->create();

        Sanctum::actingAs(
            $utilisateur,
            ['refresh']
        );

        $this->getJson('/api/utilisateur')
            ->assertForbidden();
    }
}
