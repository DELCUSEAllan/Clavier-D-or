<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">

    <title>Bilan du Clavier d'Or</title>

    <style>
        {!! file_get_contents(
            resource_path('css/pdf/bilan.css')
        ) !!}
    </style>
</head>

<body>
<div class="logo">
    <img
        src="{{ public_path('images/logo_quiz.png') }}"
        alt="Logo du Clavier d'Or"
    >
</div>

<h1>Le Clavier d'Or</h1>

<div class="sous-titre">
    Bilan de {{ $utilisateur->pseudo }} —
    généré le {{ $dateGeneration->format('d/m/Y à H:i') }}
</div>

<table class="resume">
    <tr>
        <td>
                <span class="valeur">
                    {{ number_format($scoreGlobal, 0, ',', ' ') }}
                </span>
            Score global
        </td>

        <td>
            <span class="valeur">55 000</span>
            Score maximal
        </td>

        <td>
                <span class="valeur">
                    {{ $statistiques['nombre_parties'] }}
                </span>
            Parties terminées
        </td>

        <td>
                <span class="valeur">
                    {{ $statistiques['victoires'] }}
                </span>
            Boss vaincus
        </td>
    </tr>
</table>

<div class="trophee">
    @if ($tropheePlatine)
        Trophée platine obtenu : tous les niveaux ont été terminés
        avec un score parfait !
    @else
        Trophée platine non obtenu : continue à améliorer tes scores.
    @endif
</div>

<h2>Progression par niveau</h2>

<table class="tableau">
    <thead>
    <tr>
        <th>Niveau</th>
        <th>Thème</th>
        <th>Meilleur score</th>
        <th>Maximum</th>
        <th>État</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($progressions as $progression)
        @php
            $numero = (int) $progression->theme->numero_niveau;
            $scoreMaximal = $numero === 6 ? 20000 : 7000;
        @endphp

        <tr>
            <td class="centre">{{ $numero }}</td>

            <td>{{ $progression->theme->nom }}</td>

            <td class="centre">
                {{ number_format(
                    $progression->meilleur_score,
                    0,
                    ',',
                    ' '
                ) }}
            </td>

            <td class="centre">
                {{ number_format($scoreMaximal, 0, ',', ' ') }}
            </td>

            <td class="centre">
                @if ($progression->meilleur_score >= $scoreMaximal)
                    Parfait
                @elseif ($progression->reussi_le !== null)
                    Réussi
                @elseif ($progression->est_deverrouille)
                    À jouer
                @else
                    Verrouillé
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<h2>Historique des parties</h2>

<table class="tableau">
    <thead>
    <tr>
        <th>Date</th>
        <th>Joker</th>
        <th>Niveau atteint</th>
        <th>Score</th>
        <th>Résultat</th>
    </tr>
    </thead>

    <tbody>
    @forelse ($parties as $partie)
        <tr>
            <td>
                {{ $partie->date_fin?->format('d/m/Y H:i') }}
            </td>

            <td class="centre">{{ $partie->role }}</td>

            <td>
                {{ $partie->niveau_actuel }} —
                {{ $themes->get($partie->niveau_actuel) }}
            </td>

            <td class="centre">
                {{ number_format(
                    $partie->score_total,
                    0,
                    ',',
                    ' '
                ) }}
            </td>

            <td class="centre">
                {{ $partie->statut === 'gagnee'
                    ? 'Victoire'
                    : 'Défaite' }}
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="centre">
                Aucune partie terminée.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="pied">
    Le Clavier d'Or — bilan personnel de progression
</div>
</body>
</html>
