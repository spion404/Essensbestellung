<?php

declare(strict_types=1);

$layoutEscape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$adminPageTitle =
    $adminPageTitle
    ?? 'Administration';

$adminActiveSection =
    $adminActiveSection
    ?? '';

$adminSessionForLayout =
    $adminSession
    ?? new \App\Service\AdminSessionService();

$adminLogoutCsrfToken =
    $adminSessionForLayout->csrfToken();

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
        <?= $layoutEscape($adminPageTitle) ?>
        – Essensbestellung
    </title>

    <link rel="stylesheet" href="/assets/app.css">
    <link rel="stylesheet" href="/assets/admin.css">
</head>

<body>

<header class="topbar">

    <div class="topbar__inner">

        <a
            class="brand"
            href="/admin/orders.php"
        >

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

        <div class="topbar__actions">

            <nav
                class="nav"
                aria-label="Administration"
            >

                <a
                    href="/admin/orders.php"
                    <?= $adminActiveSection === 'orders'
                        ? 'aria-current="page"'
                        : ''
                    ?>
                >
                    Bestellungen
                </a>

                <a
                    href="/admin/groups.php"
                    <?= $adminActiveSection === 'groups'
                        ? 'aria-current="page"'
                        : ''
                    ?>
                >
                    Gruppen
                </a>

                <a
                    href="/admin/budgets.php"
                    <?= $adminActiveSection === 'budgets'
                        ? 'aria-current="page"'
                        : ''
                    ?>
                >
                    Budgets
                </a>

                <a
                    href="/admin/products.php"
                    <?= $adminActiveSection === 'products'
                        ? 'aria-current="page"'
                        : ''
                    ?>
                >
                    Produkte
                </a>

                <a
                    href="/admin/categories.php"
                    <?= $adminActiveSection === 'categories'
                        ? 'aria-current="page"'
                        : ''
                    ?>
                >
                    Kategorien
                </a>

                <a
                    href="/admin/settings.php"
                    <?= $adminActiveSection === 'settings'
                        ? 'aria-current="page"'
                        : ''
                    ?>
                >
                    Einstellungen
                </a>

            </nav>

            <form
                class="inline-form"
                method="post"
                action="/admin/logout.php"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= $layoutEscape(
                        $adminLogoutCsrfToken
                    ) ?>"
                >

                <button
                    class="button--secondary button--small"
                    type="submit"
                >
                    Abmelden
                </button>

            </form>

        </div>

    </div>

</header>

<main class="app-container">
