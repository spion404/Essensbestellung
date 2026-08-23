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

    <title>
        Gruppenlogin – <?= $escape($settings['camp_name']) ?>
    </title>

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
                    <?= $escape($settings['camp_name']) ?>
                </span>

                <span class="brand__subtitle">
                    Essensbestellung
                </span>
            </span>
        </a>

        <p class="eyebrow">
            Gruppenbereich
        </p>

        <h1>Anmelden</h1>

        <p class="lead">
            Wähle deine Gruppe aus und melde dich mit dem
            Gruppenpasswort an.
        </p>

        <?php if ($loggedOut): ?>

            <div class="alert alert--success">
                Du wurdest erfolgreich abgemeldet.
            </div>

        <?php endif; ?>

        <?php if ($error !== null): ?>

            <div class="alert alert--danger">
                <strong>
                    <?= $escape($error) ?>
                </strong>
            </div>

        <?php endif; ?>

        <?php if ($groups === []): ?>

            <div class="alert alert--warning">
                Es wurden noch keine Gruppen eingerichtet.
            </div>

        <?php else: ?>

            <form
                method="post"
                action="/group/login.php"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= $escape($csrfToken) ?>"
                >

                <div class="field">

                    <label for="group_id">
                        Gruppe
                    </label>

                    <select
                        id="group_id"
                        name="group_id"
                        required
                        autofocus
                    >

                        <option value="">
                            Bitte auswählen
                        </option>

                        <?php foreach ($groups as $group): ?>

                            <option
                                value="<?= (int) $group['id'] ?>"
                                <?php if (
                                    $selectedGroupId
                                    === (string) $group['id']
                                ): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?= $escape($group['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="field">

                    <label for="password">
                        Passwort
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >

                </div>

                <button type="submit">
                    Anmelden
                </button>

            </form>

        <?php endif; ?>

    </section>

    <p class="auth-card__footer">
        <a href="/">
            Zur Startseite
        </a>
    </p>

</main>

</body>
</html>