<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Nouveau mot de passe — Le Clavier d’Or</title>
</head>
<body>
<h1>Choisis ton nouveau mot de passe</h1>

@foreach ($errors->all() as $erreur)
    <p role="alert">{{ $erreur }}</p>
@endforeach

<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <p><label>Adresse e-mail
            <input type="email" name="email"
                   value="{{ old('email', request()->query('email')) }}" required>
        </label></p>

    <p><label>Nouveau mot de passe
            <input type="password" name="password" autocomplete="new-password"
                   minlength="8" required>
        </label></p>

    <p><label>Confirme le nouveau mot de passe
            <input type="password" name="password_confirmation"
                   autocomplete="new-password" minlength="8" required>
        </label></p>

    <button type="submit">Réinitialiser mon mot de passe</button>
</form>
</body>
</html>
