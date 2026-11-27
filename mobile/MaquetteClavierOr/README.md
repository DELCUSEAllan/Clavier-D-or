# Maquettes Figma — Le Clavier d'Or

Ce mini-plugin génère directement dans Figma les écrans nécessaires au
parcours mobile :

- la page **Connexion** ;
- la page **Inscription** ;
- la page **Accueil** ;
- le **choix du rôle** avant une nouvelle partie ;
- la **question** et son état après validation ;
- le récapitulatif d'un **niveau réussi** ;
- la **fin de partie** ;
- l'**historique** ;
- la **progression** ;
- le **profil** ;
- un **UI Kit** avec la palette et plusieurs composants réutilisables.
- une planche **Écrans et appels API** pour faciliter l'intégration.

Les écrans ne sont pas de simples images : textes, couleurs, cartes, champs,
boutons et icônes sont créés en calques Figma modifiables. Les boutons et le
champ d'exemple du UI Kit sont de vrais composants Figma.

## Installation dans Figma

L'identifiant d'un plugin de développement doit être créé par Figma. La
méthode la plus fiable est donc la suivante :

1. Ouvre l'application **Figma Desktop** et crée ou ouvre un fichier Design.
2. Dans le menu Figma, ouvre **Plugins > Development > New plugin…**.
3. Choisis un plugin qui s'exécute une fois, puis donne-lui le nom
   `Clavier d'Or — Générateur de maquettes`.
4. Choisis un dossier vide. Figma y crée notamment un fichier `manifest.json`
   possédant ton identifiant de plugin.
5. Copie le fichier `code.js` de ce dossier dans le dossier créé par Figma et
   remplace le fichier d'exemple portant le même nom.
6. Dans le `manifest.json` créé par Figma, vérifie ces valeurs sans modifier
   la propriété `id` :

```json
{
  "main": "code.js",
  "editorType": ["figma"],
  "documentAccess": "dynamic-page"
}
```

Le fichier `manifest.template.json` fourni sert uniquement de modèle : ne le
renomme pas en `manifest.json` sans remplacer son identifiant factice.

## Génération des écrans

1. Dans ton fichier Figma, ouvre **Plugins > Development**.
2. Lance **Clavier d'Or — Générateur de maquettes**.
3. Les onze écrans, le UI Kit et la correspondance API apparaissent
   automatiquement sur la page courante.

Tu peux relancer le plugin pour créer une nouvelle copie sans écraser la
précédente.

## Correspondance avec le backend

| Élément de la maquette | Donnée ou route prévue |
| --- | --- |
| Connexion | `POST /api/connexion` ou connexion web par cookie |
| Inscription | `POST /api/inscription` |
| Bonjour + pseudo | utilisateur authentifié |
| Score global | champ `meilleur_score` |
| Partie en cours | `GET /api/parties/actuelle` |
| Reprendre la partie | suite de la partie actuelle |
| Nouvelle partie | `POST /api/parties/demarrer` |
| Historique | `GET /api/historique` |
| Progression | `GET /api/progression` |
| Mon bilan | `GET /api/bilan/pdf` |
| Choix du rôle | `POST /api/parties/demarrer` avec `role` |
| Question | `GET /api/parties/{partie}/question-suivante` |
| Réponse | `POST /api/parties/{partie}/repondre` avec `reponse` |
| Joker | `POST /api/parties/{partie}/joker` |
| Abandon | `PATCH /api/parties/{partie}/abandonner` |
| Niveau suivant | `POST /api/parties/{partie}/continuer` |
| Profil | `GET /api/utilisateur` |
| Déconnexion | `POST /api/deconnexion` |

Les valeurs comme `42 000`, `Niveau 3` et `Question 4 sur 7` sont des données
de démonstration à remplacer par les réponses de l'API lors du développement.
La barre de navigation est présente sur les écrans principaux. Elle est
volontairement masquée pendant le quiz pour limiter les sorties accidentelles.

## Palette utilisée

| Usage | Couleur |
| --- | --- |
| Fond principal | `#0B0B0D` |
| Cartes et champs | `#1C1D22` |
| Texte principal | `#F4F2ED` |
| Action principale | `#176BFF` |
| Accent / récompense | `#E8B84A` |
| Erreur / vies | `#FF3B5C` |
| Succès / progression | `#24C875` |
| Texte secondaire | `#A8ADB7` |
