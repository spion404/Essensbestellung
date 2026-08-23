<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class OrderCutoffService
{
    private readonly DateTimeZone $timezone;

    public function __construct(
        ?string $timezoneName = null
    ) {
        $timezoneName ??=
            $_ENV['APP_TIMEZONE']
            ?? 'Europe/Zurich';

        try {
            $this->timezone =
                new DateTimeZone($timezoneName);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                'Ungültige APP_TIMEZONE: '
                . $timezoneName,
                0,
                $exception
            );
        }
    }

    public function getStatus(
        string $deliveryDate,
        string $cutoffTime,
        ?DateTimeImmutable $now = null
    ): array {
        $deadline = $this->calculateDeadline(
            $deliveryDate,
            $cutoffTime
        );

        $currentTime = $now === null
            ? new DateTimeImmutable(
                'now',
                $this->timezone
            )
            : $now->setTimezone(
                $this->timezone
            );

        return [
            'deadline' => $deadline,
            'is_open' => $currentTime < $deadline,
        ];
    }

    public function assertOpen(
        string $deliveryDate,
        string $cutoffTime
    ): void {
        $status = $this->getStatus(
            $deliveryDate,
            $cutoffTime
        );

        if ($status['is_open']) {
            return;
        }

        /** @var DateTimeImmutable $deadline */
        $deadline = $status['deadline'];

        throw new RuntimeException(
            'Der Bestellschluss für diese Lieferung '
            . 'war am '
            . $deadline->format('d.m.Y')
            . ' um '
            . $deadline->format('H:i')
            . ' Uhr.'
        );
    }

    public function calculateDeadline(
        string $deliveryDate,
        string $cutoffTime
    ): DateTimeImmutable {
        $deliveryDate = trim(
            $deliveryDate
        );

        $cutoffTime = trim(
            $cutoffTime
        );

        $delivery = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $deliveryDate,
            $this->timezone
        );

        if (
            $delivery === false
            || $delivery->format('Y-m-d')
                !== $deliveryDate
        ) {
            throw new InvalidArgumentException(
                'Ungültiges Lieferdatum.'
            );
        }

        if (
            preg_match(
                '/^(?<hour>[01]\d|2[0-3]):'
                . '(?<minute>[0-5]\d)'
                . '(?::(?<second>[0-5]\d))?$/',
                $cutoffTime,
                $matches
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'Ungültige Bestellschlusszeit.'
            );
        }

        $hour = (int) $matches['hour'];
        $minute = (int) $matches['minute'];

        $second =
            isset($matches['second'])
            && $matches['second'] !== ''
                ? (int) $matches['second']
                : 0;

        return $delivery
            ->modify('-1 day')
            ->setTime(
                $hour,
                $minute,
                $second
            );
    }
}