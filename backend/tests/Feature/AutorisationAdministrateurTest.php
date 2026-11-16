<?php

namespace Tests\Feature;

use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AutorisationAdministrateurTest extends TestCase
{
    use RefreshDatabase;

    public function test_utilisateur_refuse(): void
    {
        $utilisateur = Utilisateur::factory()->create([
            'est_admin' => false,
        ]);

        Sanctum::actingAs(
            $utilisateur,
            ['access']
        );

        $this->getJson('/api/admin/utilisateurs')
            ->assertForbidden();
    }

    public function test_admin_autorise(): void
    {
        $administrateur =
            Utilisateur::factory()->create([
                'est_admin' => true,
            ]);

        Utilisateur::factory()->count(2)->create();

        Sanctum::actingAs(
            $administrateur,
            ['access']
        );

        $this->getJson('/api/admin/utilisateurs')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_refresh_refuse(): void
    {
        $administrateur =
            Utilisateur::factory()->create([
                'est_admin' => true,
            ]);

        Sanctum::actingAs(
            $administrateur,
            ['refresh']
        );

        $this->getJson('/api/admin/utilisateurs')
            ->assertForbidden();
    }
}
