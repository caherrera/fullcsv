<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload


$csv = new FullCsv(__DIR__ . '/example.csv');
$a = "open     ";echo "\n$a\n";var_dump($csv->open());
$a = "count    ";echo "\n$a\n";var_dump($csv->count());
$a = "longest  ";echo "\n$a\n";var_dump($csv->longestLine());
$a = "lenght   ";echo "\n$a\n";var_dump($csv->length);
$a = "fetchAll ";echo "\n$a\n";var_dump($csv->fetchAll());
$a = "data     ";echo "\n$a\n";var_dump($csv->data);
$a = "close    ";echo "\n$a\n";var_dump($csv->close());
