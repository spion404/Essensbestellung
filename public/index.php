<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo '<h1>Essensbestellung</h1>';

echo '<p>Umgebung: '
    . htmlspecialchars($_ENV['APP_ENV'] ?? 'unknown')
    . '</p>';