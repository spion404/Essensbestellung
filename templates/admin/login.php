<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin-Anmeldung</title>

    <link rel="stylesheet" href="/assets/app.css">
</head>

<body class="auth-page">

<main class="auth-shell">

    <section class="auth-card">

        <a class="brand" href="/">

            <span class="brand__mark">
                EB
            </span>

            <span class="brand__text">

                <span class="brand__title">
                    Essensbestellung
                </span>

                <span class="brand__subtitle">
                    Administration
                </span>

            </span>

        </a>

        <p class="eyebrow">
            Geschützter Bereich
        </p>

        <h1>Administration</h1>

        <p class="lead">
            Melde dich mit dem Administrationspasswort an.
        </p>

        <?php if ($error !== null): ?>

            <div class="alert alert--danger">

                <strong>
                    <?= $escape($error) ?>
                </strong>

            </div>

        <?php endif; ?>

        <form
            method="post"
            action="/admin/login.php"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= $escape($csrfToken) ?>"
            >

            <div class="field">

                <label for="password">
                    Admin-Passwort
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    autofocus
                >

            </div>

            <button type="submit">
                Anmelden
            </button>

        </form>

    </section>

    <p class="auth-card__footer">

        <a href="/">
            Zur Startseite
        </a>

    </p>

</main>

</body>
</html>