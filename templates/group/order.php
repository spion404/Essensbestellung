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

    return $weekdays[
        (int) $parsedDate->format('N')
    ]
        . ', '
        . $parsedDate->format('d.m.Y');
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
        Bestellung – <?= $escape($group['name']) ?>
    </title>

    <style>
        body {
            font-family: sans-serif;
            line-height: 1.45;
            margin: 2rem auto;
            max-width: 1200px;
            padding: 0 1rem;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border-bottom: 1px solid #ccc;
            padding: 0.65rem;
            text-align: left;
            vertical-align: top;
        }

        th {
            white-space: nowrap;
        }

        .filters {
            align-items: end;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin: 1.5rem 0 1rem;
        }

        .filters label {
            display: grid;
            gap: 0.25rem;
        }

        .filters input[type="search"],
        .filters select {
            min-width: 15rem;
            padding: 0.4rem;
        }

        .filters .checkbox-label {
            align-items: center;
            display: flex;
            gap: 0.4rem;
        }

        .filter-status {
            margin: 0.75rem 0;
        }

        .product-name {
            font-weight: 600;
        }

        .product-meta {
            color: #555;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .product-remark {
            margin-top: 0.35rem;
        }

        .quantity-input {
            box-sizing: border-box;
            max-width: 8rem;
            padding: 0.4rem;
            width: 100%;
        }

        .line-total {
            white-space: nowrap;
        }

        .order-summary {
            border-top: 2px solid #333;
            margin-top: 1.5rem;
            padding-top: 1rem;
        }

        .order-summary dl {
            display: grid;
            gap: 0.4rem 1rem;
            grid-template-columns: max-content max-content;
        }

        .order-summary dd {
            margin: 0;
            text-align: right;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .actions button {
            padding: 0.6rem 0.9rem;
        }

        [hidden] {
            display: none !important;
        }

        @media (max-width: 760px) {
            body {
                margin-top: 1rem;
            }

            .filters {
                align-items: stretch;
                display: grid;
            }

            .filters input[type="search"],
            .filters select {
                min-width: 0;
                width: 100%;
            }

            .product-table-wrapper {
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>

<p>
    <a href="/group/">
        Zurück zu den Liefertagen
    </a>
</p>

<h1><?= $escape($settings['camp_name']) ?></h1>

<h2>
    Bestellung:
    <?= $escape($group['name']) ?>
</h2>

<p>
    <strong>
        <?= $escape(
            $formatDate($deliveryDate)
        ) ?>
    </strong>
</p>

<p>
    Abschnitt:
    <?= $escape(
        implode(
            ' + ',
            $budgetDay['labels']
        )
    ) ?>
</p>

<ul>
    <li>
        Ganze Personen:
        <?= (int) $budgetDay['full_participants'] ?>
    </li>

    <li>
        Halbe Personen:
        <?= (int) $budgetDay['half_participants'] ?>
    </li>

    <li>
        Besucher:
        <?= (int) $budgetDay['visitor_participants'] ?>
    </li>

    <li>
        Tagesbudget:
        <strong>
            <?= $escape(
                $formatMoney(
                    (int) $budgetDay['budget_cents']
                )
            ) ?>
        </strong>
    </li>
</ul>

<?php if ($saved): ?>
    <p>
        <strong>
            Der Entwurf wurde gespeichert.
        </strong>
    </p>
<?php endif; ?>

<?php if ($error !== null): ?>
    <p>
        <strong><?= $escape($error) ?></strong>
    </p>
<?php endif; ?>

<?php if ($products === []): ?>

    <p>
        Es sind noch keine Produkte verfügbar.
    </p>

<?php else: ?>

    <h3>Produkte</h3>

    <div class="filters">
        <label for="product-search">
            Suche
            <input
                type="search"
                id="product-search"
                placeholder="Produkt, Artikelnummer, Bemerkung …"
                autocomplete="off"
            >
        </label>

        <label for="category-filter">
            Kategorie
            <select id="category-filter">
                <option value="">
                    Alle Kategorien
                </option>

                <?php foreach ($categories as $category): ?>
                    <option
                        value="<?= (int) $category['id'] ?>"
                    >
                        <?= $escape($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="checkbox-label">
            <input
                type="checkbox"
                id="selected-only"
            >
            Nur ausgewählte Produkte
        </label>

        <button
            type="button"
            id="reset-filters"
        >
            Filter zurücksetzen
        </button>
    </div>

    <p
        class="filter-status"
        aria-live="polite"
    >
        Sichtbar:
        <strong id="visible-product-count">
            <?= count($products) ?>
        </strong>
        von <?= count($products) ?> Produkten
        · Ausgewählt:
        <strong id="selected-product-count">0</strong>
    </p>

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

        <div class="product-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Produkt</th>
                        <th>Details</th>
                        <th>Preis</th>
                        <th>Menge</th>
                        <th>Positionswert</th>
                    </tr>
                </thead>

                <tbody id="product-table-body">

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

                    <tr
                        data-product-row
                        data-search="<?= $escape($searchText) ?>"
                        data-category-ids="<?= $escape(
                            implode(',', $categoryIds)
                        ) ?>"
                    >
                        <td>
                            <div class="product-name">
                                <?= $escape($product['name']) ?>
                            </div>

                            <?php if (
                                $product['article_number'] !== null
                                && $product['article_number'] !== ''
                            ): ?>
                                <div class="product-meta">
                                    Art.-Nr.
                                    <?= $escape(
                                        $product['article_number']
                                    ) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (
                                $product['categories'] !== null
                                && $product['categories'] !== ''
                            ): ?>
                                <div>
                                    <strong>Kategorien:</strong>
                                    <?= $escape(
                                        $product['categories']
                                    ) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (
                                $product['unit'] !== null
                                && $product['unit'] !== ''
                            ): ?>
                                <div class="product-meta">
                                    Einheit:
                                    <?= $escape($product['unit']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (
                                $product['remark'] !== null
                                && $product['remark'] !== ''
                            ): ?>
                                <div class="product-remark">
                                    <?= $escape($product['remark']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (
                                (
                                    $product['categories'] === null
                                    || $product['categories'] === ''
                                )
                                && (
                                    $product['unit'] === null
                                    || $product['unit'] === ''
                                )
                                && (
                                    $product['remark'] === null
                                    || $product['remark'] === ''
                                )
                            ): ?>
                                –
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= $escape(
                                $formatPrice(
                                    (string) $product['price']
                                )
                            ) ?>
                        </td>

                        <td>
                            <input
                                class="quantity-input"
                                type="number"
                                name="quantities[<?= $productId ?>]"
                                value="<?= $escape($quantity) ?>"
                                min="0"
                                step="0.001"
                                inputmode="decimal"
                                autocomplete="off"
                                data-price-cents="<?= $priceCents ?>"
                            >
                        </td>

                        <td
                            class="line-total"
                            data-line-total
                        >
                            CHF 0.00
                        </td>
                    </tr>
                <?php endforeach; ?>

                    <tr id="no-products-row" hidden>
                        <td colspan="5">
                            Keine Produkte entsprechen dem aktuellen Filter.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <section class="order-summary">
            <h3>Budgetkontrolle</h3>

            <dl>
                <dt>Tagesbudget</dt>
                <dd>
                    <?= $escape(
                        $formatMoney(
                            (int) $budgetDay['budget_cents']
                        )
                    ) ?>
                </dd>

                <dt>Warenwert</dt>
                <dd>
                    <strong id="order-total">
                        CHF 0.00
                    </strong>
                </dd>

                <dt>Verbleibendes Tagesbudget</dt>
                <dd>
                    <strong id="remaining-budget">
                        <?= $escape(
                            $formatMoney(
                                (int) $budgetDay['budget_cents']
                            )
                        ) ?>
                    </strong>
                </dd>
            </dl>

            <p id="budget-warning" hidden>
                <strong>
                    Achtung: Das Tagesbudget wird überschritten.
                </strong>
            </p>

            <div class="actions">
                <button
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
        </section>
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

            const warningElement =
                document.getElementById(
                    'budget-warning'
                );

            const rows = Array.from(
                document.querySelectorAll(
                    '[data-product-row]'
                )
            );

            const inputs = rows.map(
                (row) => row.querySelector(
                    'input[data-price-cents]'
                )
            );

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
                const value = Number(
                    String(input.value || '')
                        .replace(',', '.')
                );

                if (
                    !Number.isFinite(value)
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

                return Math.round(
                    priceCents * quantity
                );
            };

            const updateLineTotal = (input) => {
                const row = input.closest(
                    '[data-product-row]'
                );

                const lineTotalElement =
                    row.querySelector(
                        '[data-line-total]'
                    );

                lineTotalElement.textContent =
                    formatter.format(
                        getLineTotalCents(input) / 100
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
                    formatter.format(
                        totalCents / 100
                    );

                remainingElement.textContent =
                    formatter.format(
                        remainingCents / 100
                    );

                warningElement.hidden =
                    remainingCents >= 0;
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
                        || searchText.includes(
                            searchTerm
                        );

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

            inputs.forEach((input) => {
                updateLineTotal(input);

                input.addEventListener(
                    'input',
                    () => {
                        updateLineTotal(input);
                        updateBudget();
                        updateSelectedCount();
                    }
                );

                input.addEventListener(
                    'change',
                    () => {
                        if (selectedOnly.checked) {
                            updateFilters();
                        }
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

</body>
</html>