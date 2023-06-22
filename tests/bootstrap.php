<?php

require_once __DIR__ . '/../vendor/autoload.php';

$wpdb = new class {
    public $prefix = '';
};