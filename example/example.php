<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload


$csv = new FullCsv(__DIR__ . '/example.csv');
$a = "open     ;echo "\n$a\n";var_dump($csv->open());
$a = "count    ;echo "\n$a\n";var_dump($csv->count());
$a = "lon      ;echo "\n$a\n";var_dump($csv->longestLine());
$a = "         ;echo "\n$a\n";var_dump($csv->length);
$a = "         ;echo "\n$a\n";var_dump($csv->fetchAll());
$a = "         ;echo "\n$a\n";var_dump($csv->data);
$a = "         ;echo "\n$a\n";var_dump($csv->close());
