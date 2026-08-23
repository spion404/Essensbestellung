<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$formatMoneyCents = static function (int $cents): string {
    return 'CHF ' . number_format(
        $cents / 100,
        2,
        '.',
        "'"
    );
};

$formatMoneyAmount = static function (mixed $amount): string {
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

$orphanTotalCents = 0;

foreach ($orphanItems as $item) {
    $orphanTotalCents +=
        $moneyToCents(
            (string) $item['unit_price']
        )
        * (int) $item['quantity'];
}

$isSubmitted =
    $existingOrder !== null
    && $existingOrder['status'] === 'submitted';

$statusLabel = match (true) {
    $existingOrder === null => 'Noch keine Bestellung',
    $isSubmitted => 'Bestätigt',
    default => 'Entwurf',
};

$statusClass = match (true) {
    $existingOrder === null => 'badge--neutral',
    $isSubmitted => 'badge--success',
    default => 'badge--warning',
};

$adminPageTitle = 'Bestellung bearbeiten – ' . (string) $group['name'];
$adminActiveSection = 'orders';
$adminExtraStylesheets = ['/assets/order.css'];

require dirname(__DIR__)
    . '/partials/layout_start.php';
?>


    <div class="page-header">
        <div class="page-header__copy">
            <p class="eyebrow">Admin-Korrektur</p>

            <h1><?= $escape($group['name']) ?></h1>

            <p class="lead">
                <?= $escape($formatDate($deliveryDate)) ?>
            </p>
        </div>

        <a
            class="button button--secondary"
            href="/admin/orders/day.php?date=<?= $escape(
                rawurlencode($deliveryDate)
            ) ?>"
        >
            Zurück zur Tagesauswertung
        </a>
    </div>

    <div class="stats">
        <div class="stat">
            <span class="stat__label">Gruppe</span>
            <span class="stat__value">
                <?= $escape($group['name']) ?>
            </span>
        </div>

        <div class="stat">
            <span class="stat__label">Status</span>
            <span class="stat__value">
                <span class="badge <?= $statusClass ?>">
                    <?= $escape($statusLabel) ?>
                </span>
            </span>
        </div>

        <div class="stat stat--success">
            <span class="stat__label">Tagesbudget</span>
            <span class="stat__value">
                <?= $escape(
                    $formatMoneyCents(
                        (int) $budgetDay['budget_cents']
                    )
                ) ?>
            </span>
        </div>
    </div>

    <?php if (!$cutoffStatus['is_open']): ?>
        <div class="alert alert--warning">
            <strong>Admin-Override:</strong>
            Der Bestellschluss ist bereits vorbei. Änderungen sind
            hier weiterhin möglich, weil du im geschützten
            Administrationsbereich arbeitest.
        </div>
    <?php endif; ?>

    <?php if ($isSubmitted): ?>
        <div class="alert alert--info">
            <strong>Bereits bestätigt:</strong>
            Gespeicherte Änderungen wirken sofort auf die
            Sammelbestellung dieses Liefertags.
        </div>
    <?php endif; ?>

    <?php if ($saved): ?>
        <div class="alert alert--success">
            <strong>Änderungen gespeichert.</strong>
        </div>
    <?php endif; ?>

    <?php if ($submitted): ?>
        <div class="alert alert--success">
            <strong>
                Die Bestellung wurde gespeichert und bestätigt.
            </strong>
        </div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <div class="alert alert--danger">
            <strong><?= $escape($error) ?></strong>
        </div>
    <?php endif; ?>

    <?php if ($orphanItems !== []): ?>
        <section class="panel orphan-list">
            <div class="panel__header">
                <div>
                    <h2>Nicht mehr im Produktkatalog</h2>
                    <span class="small-muted">
                        Diese Snapshot-Positionen bleiben bei der
                        Korrektur unverändert erhalten.
                    </span>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Artikelnummer</th>
                            <th>Produkt</th>
                            <th>Einheit</th>
                            <th>Packungen</th>
                            <th>Preis</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($orphanItems as $item): ?>
                        <tr>
                            <td>
                                <?= $item['article_number'] === null
                                    || $item['article_number'] === ''
                                        ? '–'
                                        : $escape(
                                            $item['article_number']
                                        ) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= $escape($item['product_name']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= $item['unit'] === null
                                    || $item['unit'] === ''
                                        ? '–'
                                        : $escape($item['unit']) ?>
                            </td>

                            <td class="numeric">
                                <?= (int) $item['quantity'] ?>
                            </td>

                            <td class="numeric">
                                <?= $escape(
                                    $formatMoneyAmount(
                                        $item['unit_price']
                                    )
                                ) ?>
                            </td>

                            <td class="numeric">
                                <?= $escape(
                                    $formatMoneyAmount(
                                        $item['line_total_amount']
                                    )
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <section class="panel order-filter-panel">
        <div class="panel__header">
            <div>
                <h2>Produkte filtern</h2>
                <span class="small-muted">
                    Suche nach Produkt, Artikelnummer, Kategorie oder
                    Bemerkung.
                </span>
            </div>
        </div>

        <div class="order-filter-grid">
            <div
                class="order-filter-field order-filter-field--wide"
            >
                <label for="product-search">Suche</label>

                <input
                    type="search"
                    id="product-search"
                    placeholder="Produkt, Artikelnummer oder Kategorie …"
                    autocomplete="off"
                >
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
        action="/admin/orders/edit.php?group_id=<?= $groupId ?>&amp;date=<?= $escape(
            rawurlencode($deliveryDate)
        ) ?>"
    >
        <input
            type="hidden"
            name="csrf_token"
            value="<?= $escape($adminCsrfToken) ?>"
        >

        <input
            type="hidden"
            name="group_id"
            value="<?= $groupId ?>"
        >

        <input
            type="hidden"
            name="delivery_date"
            value="<?= $escape($deliveryDate) ?>"
        >

        <div class="order-section-heading">
            <h2>Produkte</h2>

            <span class="order-inline-meta">
                Bestehende Positionen behalten ihren
                <strong>Snapshot-Preis</strong>.
            </span>
        </div>

        <div class="product-list">
        <?php foreach ($products as $product): ?>
            <?php
            $productId = (int) $product['id'];

            $existingItem =
                $existingItemsByProductId[$productId]
                ?? null;

            $effectivePrice =
                $existingItem !== null
                    ? (string) $existingItem['unit_price']
                    : (string) $product['price'];

            $effectivePriceCents = $moneyToCents(
                $effectivePrice
            );

            $quantity =
                $quantities[(string) $productId]
                ?? '';

            $searchText = implode(
                ' ',
                [
                    (string) $product['name'],
                    (string) ($product['article_number'] ?? ''),
                    (string) ($product['categories'] ?? ''),
                    (string) ($product['remark'] ?? ''),
                    (string) ($product['unit'] ?? ''),
                ]
            );
            ?>

            <article
                class="product-card"
                data-product-row
                data-search="<?= $escape($searchText) ?>"
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

                    <div class="product-card__tags">
                        <?php if (
                            $product['categories'] !== null
                            && $product['categories'] !== ''
                        ): ?>
                            <span class="product-tag">
                                <?= $escape($product['categories']) ?>
                            </span>
                        <?php endif; ?>

                        <?php if (
                            $product['unit'] !== null
                            && $product['unit'] !== ''
                        ): ?>
                            <span class="product-tag product-tag--muted">
                                <?= $escape($product['unit']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

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
                        Bestellpreis
                    </span>

                    <span class="product-card__price-value">
                        <?= $escape(
                            $formatMoneyAmount($effectivePrice)
                        ) ?>
                    </span>

                    <?php if (
                        $existingItem !== null
                        && (string) $product['price']
                            !== $effectivePrice
                    ): ?>
                        <span class="product-card__old-price">
                            Katalog aktuell:
                            <?= $escape(
                                $formatMoneyAmount(
                                    $product['price']
                                )
                            ) ?>
                        </span>
                    <?php endif; ?>
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
                                data-price-cents="<?= $effectivePriceCents ?>"
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

        <section class="panel panel--soft">

            <div class="panel__header">
                <div>
                    <h2>Abrechnung</h2>

                    <span class="small-muted">
                        Hier kann die Differenz zwischen berechnetem
                        Produktwert und effektivem Rechnungsbetrag
                        eingetragen werden.
                    </span>
                </div>
            </div>

            <div class="form-grid">

                <div class="field">

                    <label for="rounding-amount">
                        Rundung / Kostenkorrektur
                    </label>

                    <input
                        type="text"
                        id="rounding-amount"
                        name="rounding_amount"
                        value="<?= $escape($roundingAmount) ?>"
                        inputmode="decimal"
                        placeholder="0.00"
                    >

                    <span class="small-muted">
                        Positiv = effektive Kosten höher,
                        negativ = effektive Kosten tiefer.
                        Beispiel: +0.20 oder -0.15
                    </span>

                </div>

                <div class="stat">

                    <span class="stat__label">
                        Effektive Kosten
                    </span>

                    <span
                        class="stat__value"
                        id="effective-total"
                    >
                        CHF 0.00
                    </span>

                </div>

            </div>

        </section>

        <div class="order-sticky">
            <div class="order-sticky__inner">
                <div class="order-budget">
                    <div class="order-budget__metric">
                        <span class="order-budget__label">
                            Tagesbudget
                        </span>

                        <span class="order-budget__value">
                            <?= $escape(
                                $formatMoneyCents(
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
                                $formatMoneyCents(
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
                        Änderungen speichern
                    </button>

                    <?php if (!$isSubmitted): ?>
                        <button
                            type="submit"
                            name="action"
                            value="submit"
                        >
                            Speichern & bestätigen
                        </button>
                    <?php endif; ?>
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

            const orphanTotalCents =
                <?= $orphanTotalCents ?>;

            const searchInput =
                document.getElementById('product-search');

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
            
            const roundingInput =
                document.getElementById(
                    'rounding-amount'
                );

            const effectiveTotalElement =
                document.getElementById(
                    'effective-total'
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
                const quantity = Number(input.value);

                if (
                    !Number.isInteger(quantity)
                    || quantity <= 0
                ) {
                    return 0;
                }

                return quantity;
            };

            const getLineTotalCents = (input) => {
                return Number(input.dataset.priceCents)
                    * getQuantity(input);
            };

            const updateLineTotal = (input) => {
                const row = input.closest(
                    '[data-product-row]'
                );

                const lineElement = row.querySelector(
                    '[data-line-total]'
                );

                const quantity = getQuantity(input);

                lineElement.textContent =
                    formatter.format(
                        getLineTotalCents(input) / 100
                    );

                row.classList.toggle(
                    'product-card--selected',
                    quantity > 0
                );
            };

            const updateBudget = () => {
                let totalCents = orphanTotalCents;

                inputs.forEach((input) => {
                    totalCents +=
                        getLineTotalCents(input);
                });

                const remainingCents =
                    budgetCents - totalCents;

                totalElement.textContent =
                    formatter.format(totalCents / 100);
                
                const roundingValue =
                    Number(
                        String(
                            roundingInput.value || '0'
                        ).replace(',', '.')
                    );

                const roundingCents =
                    Number.isFinite(roundingValue)
                        ? Math.round(
                            roundingValue * 100
                        )
                        : 0;

                effectiveTotalElement.textContent =
                    formatter.format(
                        (
                            totalCents
                            + roundingCents
                        ) / 100
                    );

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

                let visibleCount = 0;

                rows.forEach((row) => {
                    const input = row.querySelector(
                        'input[data-price-cents]'
                    );

                    const searchText = normalizeText(
                        row.dataset.search
                    );

                    const matchesSearch =
                        searchTerm === ''
                        || searchText.includes(searchTerm);

                    const matchesSelection =
                        !selectedOnly.checked
                        || getQuantity(input) > 0;

                    const visible =
                        matchesSearch
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

            selectedOnly.addEventListener(
                'change',
                updateFilters
            );

            resetFiltersButton.addEventListener(
                'click',
                () => {
                    searchInput.value = '';
                    selectedOnly.checked = false;

                    updateFilters();
                    searchInput.focus();
                }
            );

            roundingInput.addEventListener(
                'input',
                updateBudget
            );

            updateBudget();
            updateSelectedCount();
            updateFilters();
        })();
    </script>

<?php
require dirname(__DIR__)
    . '/partials/layout_end.php';
