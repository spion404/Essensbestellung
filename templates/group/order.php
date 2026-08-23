<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$formatMoney = static function (int $cents): string {
    return 'CHF ' . number_format(
        $cents / 100,
        2,
        '.',
        "'"
    );
};

$formatPrice = static function (string $amount): string {
    return 'CHF ' . number_format(
        (float) $amount,
        2,
        '.',
        "'"
    );
};

$moneyToCents = static function (string $amount): int {
    [$wholePart, $decimalPart] = array_pad(
        explode('.', $amount, 2),
        2,
        ''
    );

    $decimalPart = str_pad(
        $decimalPart,
        2,
        '0'
    );

    return ((int) $wholePart * 100)
        + (int) $decimalPart;
};

$formatDate = static function (string $date): string {
    $parsedDate = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $date
    );

    if ($parsedDate === false) {
        return $date;
    }

    $weekdays = [
        1 => 'Montag',
        2 => 'Dienstag',
        3 => 'Mittwoch',
        4 => 'Donnerstag',
        5 => 'Freitag',
        6 => 'Samstag',
        7 => 'Sonntag',
    ];

    return $weekdays[(int) $parsedDate->format('N')]
        . ', '
        . $parsedDate->format('d.m.Y');
};

$categoryNamesById = [];

foreach ($categories as $category) {
    $categoryNamesById[(int) $category['id']] =
        (string) $category['name'];
}

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
        Bestellung – <?= $escape($group['name']) ?>
    </title>

    <link rel="stylesheet" href="/assets/app.css">
    <link rel="stylesheet" href="/assets/order.css">
</head>

<body>

<header class="topbar">
    <div class="topbar__inner">
        <a class="brand" href="/group/">
            <span class="brand__mark">EB</span>

            <span class="brand__text">
                <span class="brand__title">
                    <?= $escape($settings['camp_name']) ?>
                </span>

                <span class="brand__subtitle">
                    Gruppenbestellung
                </span>
            </span>
        </a>

        <div class="topbar__actions">
            <span class="topbar__identity">
                Angemeldet als
                <strong><?= $escape($group['name']) ?></strong>
            </span>

            <form
                class="inline-form"
                method="post"
                action="/group/logout.php"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= $escape($csrfToken) ?>"
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

    <div class="page-header">
        <div class="page-header__copy">
            <p class="eyebrow">Bestellung erfassen</p>

            <h1>
                <?= $escape($formatDate($deliveryDate)) ?>
            </h1>

            <p class="lead">
                <?= $escape(
                    implode(' + ', $budgetDay['labels'])
                ) ?>
            </p>
        </div>

        <a
            class="button button--secondary"
            href="/group/"
        >
            Zurück zu den Liefertagen
        </a>
    </div>

    <div class="stats">
        <div class="stat">
            <span class="stat__label">Ganze Personen</span>
            <span class="stat__value">
                <?= (int) $budgetDay['full_participants'] ?>
            </span>
        </div>

        <div class="stat">
            <span class="stat__label">Halbe Personen</span>
            <span class="stat__value">
                <?= (int) $budgetDay['half_participants'] ?>
            </span>
        </div>

        <div class="stat">
            <span class="stat__label">Besucher</span>
            <span class="stat__value">
                <?= (int) $budgetDay['visitor_participants'] ?>
            </span>
        </div>

        <div class="stat stat--success">
            <span class="stat__label">Tagesbudget</span>
            <span class="stat__value">
                <?= $escape(
                    $formatMoney(
                        (int) $budgetDay['budget_cents']
                    )
                ) ?>
            </span>
        </div>
    </div>

    <div class="stat">

        <span class="stat__label">
            Übertrag / Rundung bisher
        </span>

        <span class="stat__value">
            <?= $escape(
                $formatMoney(
                    (int) $budgetBalance[
                        'carryover_cents'
                    ]
                )
            ) ?>
        </span>

    </div>

    <div class="stat">

        <span class="stat__label">
            Gesamtbudget Lager
        </span>

        <span class="stat__value">
            <?= $escape(
                $formatMoney(
                    (int) $budgetBalance[
                        'total_budget_cents'
                    ]
                )
            ) ?>
        </span>

    </div>

    <?php if ($saved): ?>
        <div class="alert alert--success">
            <strong>Entwurf gespeichert.</strong>
            Deine Mengen wurden übernommen.
        </div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <div class="alert alert--danger">
            <strong><?= $escape($error) ?></strong>
        </div>
    <?php endif; ?>

    <?php if ($products === []): ?>

        <section class="panel">
            <div class="empty-state">
                Es sind noch keine Produkte verfügbar.
            </div>
        </section>

    <?php else: ?>

        <section class="panel order-filter-panel">
            <div class="panel__header">
                <div>
                    <h2>Produkte filtern</h2>
                    <span class="small-muted">
                        Suche nach Name, Artikelnummer, Einheit oder Bemerkung.
                    </span>
                </div>
            </div>

            <div class="order-filter-grid">
                <div class="order-filter-field">
                    <label for="product-search">Suche</label>

                    <input
                        type="search"
                        id="product-search"
                        placeholder="z. B. Tomaten, 10482, glutenfrei …"
                        autocomplete="off"
                    >
                </div>

                <div class="order-filter-field">
                    <label for="category-filter">Kategorie</label>

                    <select id="category-filter">
                        <option value="">Alle Kategorien</option>

                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>">
                                <?= $escape($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label class="order-filter-checkbox">
                    <input
                        type="checkbox"
                        id="selected-only"
                    >
                    Nur ausgewählte
                </label>

                <button
                    class="button--secondary"
                    type="button"
                    id="reset-filters"
                >
                    Filter zurücksetzen
                </button>
            </div>

            <p
                class="order-filter-status"
                aria-live="polite"
            >
                Sichtbar:
                <strong id="visible-product-count">
                    <?= count($products) ?>
                </strong>
                von <?= count($products) ?>
                · Ausgewählt:
                <strong id="selected-product-count">0</strong>
            </p>
        </section>

        <form
            method="post"
            action="/group/order.php?date=<?= $escape(
                rawurlencode($deliveryDate)
            ) ?>"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= $escape($csrfToken) ?>"
            >

            <input
                type="hidden"
                name="delivery_date"
                value="<?= $escape($deliveryDate) ?>"
            >

            <div class="order-section-heading">
                <h2>Produkte</h2>

                <span class="order-inline-meta">
                    Anzahl = bestellte <strong>Packungen</strong>
                </span>
            </div>

            <div class="product-list">

                <?php foreach ($products as $product): ?>
                    <?php
                    $productId = (int) $product['id'];

                    $quantity =
                        $quantities[(string) $productId]
                        ?? '';

                    $priceCents = $moneyToCents(
                        (string) $product['price']
                    );

                    $categoryIds =
                        $productCategoryIds[$productId]
                        ?? [];

                    $categoryNames = [];

                    foreach ($categoryIds as $categoryId) {
                        $categoryId = (int) $categoryId;

                        if (isset($categoryNamesById[$categoryId])) {
                            $categoryNames[] =
                                $categoryNamesById[$categoryId];
                        }
                    }

                    $searchParts = [
                        $product['name'],
                        $product['article_number'],
                        $product['categories'],
                        $product['remark'],
                        $product['unit'],
                    ];

                    $searchText = implode(
                        ' ',
                        array_filter(
                            array_map(
                                static fn (mixed $value): string =>
                                    trim((string) $value),
                                $searchParts
                            ),
                            static fn (string $value): bool =>
                                $value !== ''
                        )
                    );
                    ?>

                    <article
                        class="product-card"
                        data-product-row
                        data-search="<?= $escape($searchText) ?>"
                        data-category-ids="<?= $escape(
                            implode(',', $categoryIds)
                        ) ?>"
                    >
                        <div class="product-card__main">
                            <h3 class="product-card__title">
                                <?= $escape($product['name']) ?>
                            </h3>

                            <?php if (
                                $product['article_number'] !== null
                                && $product['article_number'] !== ''
                            ): ?>
                                <div class="product-card__article">
                                    Art.-Nr.
                                    <?= $escape($product['article_number']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (
                                $categoryNames !== []
                                || (
                                    $product['unit'] !== null
                                    && $product['unit'] !== ''
                                )
                            ): ?>
                                <div class="product-card__tags">
                                    <?php foreach ($categoryNames as $categoryName): ?>
                                        <span class="product-tag">
                                            <?= $escape($categoryName) ?>
                                        </span>
                                    <?php endforeach; ?>

                                    <?php if (
                                        $product['unit'] !== null
                                        && $product['unit'] !== ''
                                    ): ?>
                                        <span class="product-tag product-tag--muted">
                                            <?= $escape($product['unit']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (
                                $product['remark'] !== null
                                && $product['remark'] !== ''
                            ): ?>
                                <p class="product-card__remark">
                                    <?= $escape($product['remark']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="product-card__price">
                            <span class="product-card__price-label">
                                Preis / Packung
                            </span>

                            <span class="product-card__price-value">
                                <?= $escape(
                                    $formatPrice(
                                        (string) $product['price']
                                    )
                                ) ?>
                            </span>
                        </div>

                        <div class="product-card__order">
                            <div>
                                <span class="product-card__quantity-label">
                                    Packungen
                                </span>

                                <div class="quantity-stepper">
                                    <button
                                        class="quantity-stepper__button button--secondary"
                                        type="button"
                                        data-quantity-decrease
                                        aria-label="Eine Packung weniger"
                                    >
                                        −
                                    </button>

                                    <input
                                        class="quantity-stepper__input"
                                        type="number"
                                        name="quantities[<?= $productId ?>]"
                                        value="<?= $escape($quantity) ?>"
                                        min="0"
                                        step="1"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        data-price-cents="<?= $priceCents ?>"
                                        aria-label="Packungen <?= $escape(
                                            $product['name']
                                        ) ?>"
                                    >

                                    <button
                                        class="quantity-stepper__button"
                                        type="button"
                                        data-quantity-increase
                                        aria-label="Eine Packung mehr"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>

                            <div>
                                <span class="product-card__line-label">
                                    Positionswert
                                </span>

                                <span
                                    class="product-line-total"
                                    data-line-total
                                >
                                    CHF 0.00
                                </span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>

                <div
                    class="order-empty-filter"
                    id="no-products-row"
                    hidden
                >
                    Keine Produkte entsprechen dem aktuellen Filter.
                </div>

            </div>

            <div class="order-sticky">
                <div class="order-sticky__inner">
                    <div class="order-budget">
                        <div class="order-budget__metric">
                            <span class="order-budget__label">
                                Tagesbudget
                            </span>

                            <span class="order-budget__value">
                                <?= $escape(
                                    $formatMoney(
                                        (int) $budgetDay['budget_cents']
                                    )
                                ) ?>
                            </span>
                        </div>

                        <div class="order-budget__metric">
                            <span class="order-budget__label">
                                Warenwert
                            </span>

                            <span
                                class="order-budget__value"
                                id="order-total"
                            >
                                CHF 0.00
                            </span>
                        </div>

                        <div
                            class="order-budget__metric"
                            id="remaining-budget-metric"
                        >
                            <span class="order-budget__label">
                                Verbleibend
                            </span>

                            <span
                                class="order-budget__value"
                                id="remaining-budget"
                            >
                                <?= $escape(
                                    $formatMoney(
                                        (int) $budgetDay['budget_cents']
                                    )
                                ) ?>
                            </span>
                        </div>
                    </div>

                    <div class="order-sticky__actions">
                        <button
                            class="button--secondary"
                            type="submit"
                            name="action"
                            value="save"
                        >
                            Entwurf speichern
                        </button>

                        <button
                            type="submit"
                            name="action"
                            value="review"
                        >
                            Bestellung prüfen
                        </button>
                    </div>
                </div>

                <div
                    class="alert alert--warning"
                    id="budget-warning"
                    hidden
                >
                    <strong>Achtung:</strong>
                    Das Tagesbudget wird überschritten.
                </div>
            </div>
        </form>

        <script>
            (() => {
                const budgetCents =
                    <?= (int) $budgetDay['budget_cents'] ?>;

                const searchInput =
                    document.getElementById('product-search');

                const categoryFilter =
                    document.getElementById('category-filter');

                const selectedOnly =
                    document.getElementById('selected-only');

                const resetFiltersButton =
                    document.getElementById('reset-filters');

                const visibleCountElement =
                    document.getElementById(
                        'visible-product-count'
                    );

                const selectedCountElement =
                    document.getElementById(
                        'selected-product-count'
                    );

                const noProductsRow =
                    document.getElementById('no-products-row');

                const totalElement =
                    document.getElementById('order-total');

                const remainingElement =
                    document.getElementById(
                        'remaining-budget'
                    );

                const remainingMetric =
                    document.getElementById(
                        'remaining-budget-metric'
                    );

                const warningElement =
                    document.getElementById(
                        'budget-warning'
                    );

                const rows = Array.from(
                    document.querySelectorAll(
                        '[data-product-row]'
                    )
                );

                const inputs = rows
                    .map((row) => row.querySelector(
                        'input[data-price-cents]'
                    ))
                    .filter(Boolean);

                const formatter = new Intl.NumberFormat(
                    'de-CH',
                    {
                        style: 'currency',
                        currency: 'CHF',
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                );

                const normalizeText = (value) => {
                    return String(value || '')
                        .trim()
                        .toLocaleLowerCase('de-CH');
                };

                const getQuantity = (input) => {
                    const value = Number(input.value);

                    if (
                        !Number.isInteger(value)
                        || value <= 0
                    ) {
                        return 0;
                    }

                    return value;
                };

                const getLineTotalCents = (input) => {
                    const priceCents = Number(
                        input.dataset.priceCents
                    );

                    const quantity = getQuantity(input);

                    if (
                        !Number.isFinite(priceCents)
                        || quantity <= 0
                    ) {
                        return 0;
                    }

                    return priceCents * quantity;
                };

                const updateLineTotal = (input) => {
                    const row = input.closest(
                        '[data-product-row]'
                    );

                    const lineTotalElement =
                        row.querySelector(
                            '[data-line-total]'
                        );

                    const quantity = getQuantity(input);

                    lineTotalElement.textContent =
                        formatter.format(
                            getLineTotalCents(input) / 100
                        );

                    row.classList.toggle(
                        'product-card--selected',
                        quantity > 0
                    );
                };

                const updateBudget = () => {
                    let totalCents = 0;

                    inputs.forEach((input) => {
                        totalCents +=
                            getLineTotalCents(input);
                    });

                    const remainingCents =
                        budgetCents - totalCents;

                    totalElement.textContent =
                        formatter.format(totalCents / 100);

                    remainingElement.textContent =
                        formatter.format(
                            remainingCents / 100
                        );

                    const isNegative =
                        remainingCents < 0;

                    remainingMetric.classList.toggle(
                        'is-negative',
                        isNegative
                    );

                    warningElement.hidden = !isNegative;
                };

                const updateSelectedCount = () => {
                    const selectedCount = inputs.filter(
                        (input) => getQuantity(input) > 0
                    ).length;

                    selectedCountElement.textContent =
                        String(selectedCount);
                };

                const updateFilters = () => {
                    const searchTerm = normalizeText(
                        searchInput.value
                    );

                    const selectedCategory =
                        categoryFilter.value;

                    let visibleCount = 0;

                    rows.forEach((row) => {
                        const input = row.querySelector(
                            'input[data-price-cents]'
                        );

                        const searchText = normalizeText(
                            row.dataset.search
                        );

                        const categoryIds = String(
                            row.dataset.categoryIds || ''
                        )
                            .split(',')
                            .filter(Boolean);

                        const matchesSearch =
                            searchTerm === ''
                            || searchText.includes(searchTerm);

                        const matchesCategory =
                            selectedCategory === ''
                            || categoryIds.includes(
                                selectedCategory
                            );

                        const matchesSelection =
                            !selectedOnly.checked
                            || getQuantity(input) > 0;

                        const visible =
                            matchesSearch
                            && matchesCategory
                            && matchesSelection;

                        row.hidden = !visible;

                        if (visible) {
                            visibleCount += 1;
                        }
                    });

                    visibleCountElement.textContent =
                        String(visibleCount);

                    noProductsRow.hidden =
                        visibleCount !== 0;
                };

                const updateEverything = (input = null) => {
                    if (input !== null) {
                        updateLineTotal(input);
                    }

                    updateBudget();
                    updateSelectedCount();

                    if (selectedOnly.checked) {
                        updateFilters();
                    }
                };

                const setQuantity = (input, quantity) => {
                    const normalized = Math.max(
                        0,
                        Number.isInteger(quantity)
                            ? quantity
                            : 0
                    );

                    input.value =
                        normalized === 0
                            ? ''
                            : String(normalized);

                    updateEverything(input);
                };

                rows.forEach((row) => {
                    const input = row.querySelector(
                        'input[data-price-cents]'
                    );

                    const decreaseButton = row.querySelector(
                        '[data-quantity-decrease]'
                    );

                    const increaseButton = row.querySelector(
                        '[data-quantity-increase]'
                    );

                    updateLineTotal(input);

                    input.addEventListener(
                        'input',
                        () => updateEverything(input)
                    );

                    input.addEventListener(
                        'change',
                        () => {
                            if (selectedOnly.checked) {
                                updateFilters();
                            }
                        }
                    );

                    decreaseButton.addEventListener(
                        'click',
                        () => {
                            setQuantity(
                                input,
                                Math.max(
                                    0,
                                    getQuantity(input) - 1
                                )
                            );
                        }
                    );

                    increaseButton.addEventListener(
                        'click',
                        () => {
                            setQuantity(
                                input,
                                getQuantity(input) + 1
                            );
                        }
                    );
                });

                searchInput.addEventListener(
                    'input',
                    updateFilters
                );

                categoryFilter.addEventListener(
                    'change',
                    updateFilters
                );

                selectedOnly.addEventListener(
                    'change',
                    updateFilters
                );

                resetFiltersButton.addEventListener(
                    'click',
                    () => {
                        searchInput.value = '';
                        categoryFilter.value = '';
                        selectedOnly.checked = false;

                        updateFilters();
                        searchInput.focus();
                    }
                );

                updateBudget();
                updateSelectedCount();
                updateFilters();
            })();
        </script>

    <?php endif; ?>

</main>

</body>
</html>
