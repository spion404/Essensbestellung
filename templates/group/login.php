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
</head>

<body>

<h1><?= $escape($settings['camp_name']) ?></h1>

<h2>Gruppenlogin</h2>

<?php if ($loggedOut): ?>
    <p>
        Du wurdest abgemeldet.
    </p>
<?php endif; ?>

<?php if ($error !== null): ?>
    <p>
        <strong><?= $escape($error) ?></strong>
    </p>
<?php endif; ?>

<?php if ($groups === []): ?>

    <p>
        Es wurden noch keine Gruppen eingerichtet.
    </p>

<?php else: ?>

    <form method="post" action="/group/login.php">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= $escape($csrfToken) ?>"
        >

        <p>
            <label for="group_id">
                Gruppe
            </label>
            <br>

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
        </p>

        <p>
            <label for="password">
                Passwort
            </label>
            <br>

            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >
        </p>

        <p>
            <button type="submit">
                Anmelden
            </button>
        </p>
    </form>

<?php endif; ?>

<p>
    <a href="/">
        Zur Startseite
    </a>
</p>

</body>
</html>