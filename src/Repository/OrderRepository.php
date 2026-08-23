<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use RuntimeException;
use Throwable;

final class OrderRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                o.id,
                o.group_id,
                g.name AS group_name,
                o.delivery_date,
                o.status,
                o.submitted_at,
                o.created_at,
                o.updated_at,
                COUNT(oi.id) AS item_count,
                COALESCE(
                    ROUND(
                        SUM(
                            oi.unit_price * oi.quantity
                        ),
                        2
                    ),
                    0.00
                ) AS total_amount
            FROM orders o
            INNER JOIN groups g
                ON g.id = o.group_id
            LEFT JOIN order_items oi
                ON oi.order_id = o.id
            GROUP BY
                o.id,
                o.group_id,
                g.name,
                o.delivery_date,
                o.status,
                o.submitted_at,
                o.created_at,
                o.updated_at
            ORDER BY
                o.delivery_date ASC,
                g.name ASC'
        );

        return $statement->fetchAll();
    }

    public function findByDeliveryDate(
        string $deliveryDate
    ): array {
        $statement = $this->pdo->prepare(
            'SELECT
                o.id,
                o.group_id,
                g.name AS group_name,
                o.delivery_date,
                o.status,
                o.submitted_at,
                o.created_at,
                o.updated_at,
                COUNT(oi.id) AS item_count,
                COALESCE(
                    ROUND(
                        SUM(
                            oi.unit_price * oi.quantity
                        ),
                        2
                    ),
                    0.00
                ) AS total_amount
            FROM orders o
            INNER JOIN groups g
                ON g.id = o.group_id
            LEFT JOIN order_items oi
                ON oi.order_id = o.id
            WHERE o.delivery_date = :delivery_date
            GROUP BY
                o.id,
                o.group_id,
                g.name,
                o.delivery_date,
                o.status,
                o.submitted_at,
                o.created_at,
                o.updated_at
            ORDER BY g.name ASC'
        );

        $statement->execute([
            'delivery_date' => $deliveryDate,
        ]);

        return $statement->fetchAll();
    }

    public function summarizeSubmittedItemsByDeliveryDate(
        string $deliveryDate
    ): array {
        $statement = $this->pdo->prepare(
            'SELECT
                CASE
                    WHEN oi.article_number IS NOT NULL
                        AND oi.article_number <> \'\'
                    THEN CONCAT(
                        \'article:\',
                        oi.article_number
                    )
                    WHEN oi.product_id IS NOT NULL
                    THEN CONCAT(
                        \'product:\',
                        oi.product_id
                    )
                    ELSE CONCAT(
                        \'snapshot:\',
                        oi.product_name,
                        \'|\',
                        COALESCE(oi.unit, \'\')
                    )
                END AS aggregation_key,

                MAX(oi.product_id)
                    AS product_id,

                MAX(oi.article_number)
                    AS article_number,

                MAX(oi.product_name)
                    AS product_name,

                MAX(oi.unit)
                    AS unit,

                SUM(oi.quantity)
                    AS total_quantity,

                ROUND(
                    SUM(
                        oi.unit_price
                        * oi.quantity
                    ),
                    2
                ) AS total_amount

            FROM order_items oi

            INNER JOIN orders o
                ON o.id = oi.order_id

            WHERE o.delivery_date = :delivery_date
            AND o.status = \'submitted\'

            GROUP BY aggregation_key

            ORDER BY
                product_name ASC,
                article_number ASC'
        );

        $statement->execute([
            'delivery_date' => $deliveryDate,
        ]);

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                o.id,
                o.group_id,
                g.name AS group_name,
                o.delivery_date,
                o.status,
                o.submitted_at,
                o.created_at,
                o.updated_at,
                COUNT(oi.id) AS item_count,
                COALESCE(
                    ROUND(
                        SUM(
                            oi.unit_price * oi.quantity
                        ),
                        2
                    ),
                    0.00
                ) AS total_amount

            FROM orders o

            INNER JOIN groups g
                ON g.id = o.group_id

            LEFT JOIN order_items oi
                ON oi.order_id = o.id

            WHERE o.id = :id

            GROUP BY
                o.id,
                o.group_id,
                g.name,
                o.delivery_date,
                o.status,
                o.submitted_at,
                o.created_at,
                o.updated_at'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $order = $statement->fetch();

        if ($order === false) {
            return null;
        }

        return $order;
    }

    public function findByGroupAndDate(
        int $groupId,
        string $deliveryDate
    ): ?array {
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                group_id,
                delivery_date,
                status,
                submitted_at,
                created_at,
                updated_at

            FROM orders

            WHERE group_id = :group_id
            AND delivery_date = :delivery_date

            LIMIT 1'
        );

        $statement->execute([
            'group_id' => $groupId,
            'delivery_date' => $deliveryDate,
        ]);

        $order = $statement->fetch();

        if ($order === false) {
            return null;
        }

        return $order;
    }

    public function findItems(int $orderId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                order_id,
                product_id,
                article_number,
                product_name,
                unit,
                unit_price,
                quantity,

                ROUND(
                    unit_price * quantity,
                    2
                ) AS line_total_amount

            FROM order_items

            WHERE order_id = :order_id

            ORDER BY
                product_name ASC,
                id ASC'
        );

        $statement->execute([
            'order_id' => $orderId,
        ]);

        return $statement->fetchAll();
    }

    public function saveDraft(
        int $groupId,
        string $deliveryDate,
        array $items
    ): int {
        $ownsTransaction =
            !$this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $orderId =
                $this->findOrCreateOrderId(
                    $groupId,
                    $deliveryDate
                );

            $status =
                $this->lockAndGetStatus(
                    $orderId
                );

            if ($status !== 'draft') {
                throw new RuntimeException(
                    'Eine bereits bestätigte Bestellung kann '
                    . 'nicht als Entwurf überschrieben werden.'
                );
            }

            $deleteStatement =
                $this->pdo->prepare(
                    'DELETE FROM order_items
                    WHERE order_id = :order_id'
                );

            $deleteStatement->execute([
                'order_id' => $orderId,
            ]);

            $this->insertItems(
                $orderId,
                $items
            );

            $this->touchOrder(
                $orderId
            );

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return $orderId;
        } catch (Throwable $exception) {
            if (
                $ownsTransaction
                && $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function saveAsAdmin(
        int $groupId,
        string $deliveryDate,
        array $items
    ): int {
        $ownsTransaction =
            !$this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $orderId =
                $this->findOrCreateOrderId(
                    $groupId,
                    $deliveryDate
                );

            $this->lockAndGetStatus(
                $orderId
            );

            /*
             * Gelöschte Produkte haben in alten
             * Bestellpositionen product_id = NULL.
             *
             * Diese Snapshot-Positionen bleiben bei
             * einer Admin-Korrektur erhalten.
             */
            $deleteStatement =
                $this->pdo->prepare(
                    'DELETE FROM order_items
                    WHERE order_id = :order_id
                    AND product_id IS NOT NULL'
                );

            $deleteStatement->execute([
                'order_id' => $orderId,
            ]);

            $this->insertItems(
                $orderId,
                $items
            );

            $this->touchOrder(
                $orderId
            );

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return $orderId;
        } catch (Throwable $exception) {
            if (
                $ownsTransaction
                && $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function submit(int $orderId): void
    {
        $ownsTransaction =
            !$this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $status =
                $this->lockAndGetStatus(
                    $orderId
                );

            $itemCountStatement =
                $this->pdo->prepare(
                    'SELECT COUNT(*)
                    FROM order_items
                    WHERE order_id = :order_id'
                );

            $itemCountStatement->execute([
                'order_id' => $orderId,
            ]);

            $itemCount =
                (int) $itemCountStatement->fetchColumn();

            if ($itemCount === 0) {
                throw new RuntimeException(
                    'Eine leere Bestellung kann '
                    . 'nicht bestätigt werden.'
                );
            }

            if ($status === 'submitted') {
                if ($ownsTransaction) {
                    $this->pdo->commit();
                }

                return;
            }

            $updateStatement =
                $this->pdo->prepare(
                    'UPDATE orders
                    SET
                        status = \'submitted\',
                        submitted_at = CURRENT_TIMESTAMP
                    WHERE id = :id'
                );

            $updateStatement->execute([
                'id' => $orderId,
            ]);

            if ($ownsTransaction) {
                $this->pdo->commit();
            }
        } catch (Throwable $exception) {
            if (
                $ownsTransaction
                && $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function findOrCreateOrderId(
        int $groupId,
        string $deliveryDate
    ): int {
        $statement =
            $this->pdo->prepare(
                'INSERT INTO orders (
                    group_id,
                    delivery_date,
                    status
                ) VALUES (
                    :group_id,
                    :delivery_date,
                    \'draft\'
                )

                ON DUPLICATE KEY UPDATE
                    id = LAST_INSERT_ID(id)'
            );

        $statement->execute([
            'group_id' => $groupId,
            'delivery_date' => $deliveryDate,
        ]);

        $orderId =
            (int) $this->pdo->lastInsertId();

        if ($orderId < 1) {
            throw new RuntimeException(
                'Die Bestellung konnte nicht erstellt werden.'
            );
        }

        return $orderId;
    }

    private function lockAndGetStatus(
        int $orderId
    ): string {
        $statement =
            $this->pdo->prepare(
                'SELECT status
                FROM orders
                WHERE id = :id
                FOR UPDATE'
            );

        $statement->execute([
            'id' => $orderId,
        ]);

        $status =
            $statement->fetchColumn();

        if ($status === false) {
            throw new RuntimeException(
                'Die Bestellung konnte nicht gefunden werden.'
            );
        }

        return (string) $status;
    }

    private function insertItems(
        int $orderId,
        array $items
    ): void {
        if ($items === []) {
            return;
        }

        $statement =
            $this->pdo->prepare(
                'INSERT INTO order_items (
                    order_id,
                    product_id,
                    article_number,
                    product_name,
                    unit,
                    unit_price,
                    quantity
                ) VALUES (
                    :order_id,
                    :product_id,
                    :article_number,
                    :product_name,
                    :unit,
                    :unit_price,
                    :quantity
                )'
            );

        foreach ($items as $item) {
            $statement->execute([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'article_number' => $item['article_number'],
                'product_name' => $item['product_name'],
                'unit' => $item['unit'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
            ]);
        }
    }

    private function touchOrder(
        int $orderId
    ): void {
        $statement =
            $this->pdo->prepare(
                'UPDATE orders
                SET updated_at = CURRENT_TIMESTAMP
                WHERE id = :id'
            );

        $statement->execute([
            'id' => $orderId,
        ]);
    }
}