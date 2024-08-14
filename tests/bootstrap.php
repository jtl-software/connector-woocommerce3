<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$wpdb = new class {
    public string $prefix = '';
};
