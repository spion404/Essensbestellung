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
</head>
<body>

<h1>Administration</h1>

<h2>Anmeldung</h2>

<?php if ($error !== null): ?>
    <p>
        <strong><?= $escape($error) ?></strong>
    </p>
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

    <p>
        <label for="password">
            Admin-Passwort
        </label>
        <br>

        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
            autofocus
        >
    </p>

    <p>
        <button type="submit">
            Anmelden
        </button>
    </p>
</form>

<p>
    <a href="/">
        Zur Startseite
    </a>
</p>

</body>
</html>