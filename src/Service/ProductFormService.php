<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ProductRepository;

final class ProductFormService
{
    public function __construct(
        private readonly ProductRepository $productRepository
    ) {
    }

    public function process(
        array $input,
        array $categories,
        ?int $excludeProductId = null
    ): array {
        $form = [
            'article_number' => trim(
                (string) ($input['article_number'] ?? '')
            ),
            'name' => trim(
                (string) ($input['name'] ?? '')
            ),
            'unit' => trim(
                (string) ($input['unit'] ?? '')
            ),
            'price' => trim(
                (string) ($input['price'] ?? '')
            ),
            'remark' => trim(
                (string) ($input['remark'] ?? '')
            ),
            'category_ids' => [],
        ];

        $errors = [];

        /*
         * Artikelnummer
         *
         * Bei manueller Erfassung optional.
         */
        if (
            mb_strlen(
                $form['article_number']
            ) > 100
        ) {
            $errors['article_number'] =
                'Die Artikelnummer ist zu lang.';
        } elseif (
            $form['article_number'] !== ''
            && $this->productRepository
                ->articleNumberExists(
                    $form['article_number'],
                    $excludeProductId
                )
        ) {
            $errors['article_number'] =
                'Diese Artikelnummer ist bereits vergeben.';
        }

        /*
         * Produktname
         */
        if ($form['name'] === '') {
            $errors['name'] =
                'Bitte einen Produktnamen eingeben.';
        } elseif (
            mb_strlen($form['name']) > 200
        ) {
            $errors['name'] =
                'Der Produktname ist zu lang.';
        }

        /*
         * Einheit
         */
        if (
            mb_strlen($form['unit']) > 50
        ) {
            $errors['unit'] =
                'Die Einheit ist zu lang.';
        }

        /*
         * Preis
         */
        $priceForDatabase =
            $this->normalizePrice(
                $form['price']
            );

        if ($priceForDatabase === null) {
            if ($form['price'] === '') {
                $errors['price'] =
                    'Bitte einen Preis eingeben.';
            } else {
                $errors['price'] =
                    'Bitte einen gültigen Preis eingeben.';
            }
        }

        /*
         * Kategorien
         */
        $submittedCategoryIds =
            $input['category_ids'] ?? [];

        if (
            !is_array(
                $submittedCategoryIds
            )
        ) {
            $errors['categories'] =
                'Die Kategorien konnten nicht verarbeitet werden.';
        } else {
            $allowedCategoryIds = [];

            foreach (
                $categories
                as $category
            ) {
                $allowedCategoryIds[
                    (int) $category['id']
                ] = true;
            }

            foreach (
                $submittedCategoryIds
                as $categoryId
            ) {
                $categoryId =
                    filter_var(
                        $categoryId,
                        FILTER_VALIDATE_INT
                    );

                if (
                    $categoryId === false
                    || $categoryId < 1
                    || !isset(
                        $allowedCategoryIds[
                            $categoryId
                        ]
                    )
                ) {
                    $errors['categories'] =
                        'Mindestens eine ausgewählte Kategorie ist ungültig.';

                    break;
                }

                $form[
                    'category_ids'
                ][] = $categoryId;
            }

            $form['category_ids'] =
                array_values(
                    array_unique(
                        $form[
                            'category_ids'
                        ]
                    )
                );
        }

        return [
            'form' => $form,
            'errors' => $errors,
            'price' => $priceForDatabase,
        ];
    }

    private function normalizePrice(
        string $price
    ): ?string {
        if ($price === '') {
            return null;
        }

        $price = str_replace(
            ',',
            '.',
            $price
        );

        if (
            preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $price
            ) !== 1
        ) {
            return null;
        }

        [
            $wholePart,
            $decimalPart,
        ] = array_pad(
            explode(
                '.',
                $price,
                2
            ),
            2,
            ''
        );

        $wholePart =
            ltrim(
                $wholePart,
                '0'
            );

        if ($wholePart === '') {
            $wholePart = '0';
        }

        /*
         * DECIMAL(10, 2):
         * maximal 8 Stellen vor
         * dem Dezimalpunkt.
         */
        if (
            strlen($wholePart) > 8
        ) {
            return null;
        }

        $decimalPart =
            str_pad(
                $decimalPart,
                2,
                '0'
            );

        return $wholePart
            . '.'
            . $decimalPart;
    }
}