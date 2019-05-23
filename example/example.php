<?php
require_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use FullCsv\CsvReader;

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

$file = ( isset( $_SERVER['argv'][1] ) ) ? $_SERVER['argv'][1] : __DIR__ . '/example.csv';

try {


	$csv = new CsvReader( $file );
	$a   = "open     ";
	echo "\n$a\n";
	var_dump( $csv->open() );
	$a = "count    ";
	echo "\n$a\n";
	var_dump( $csv->count() );
	$a = "longest  ";
	echo "\n$a\n";
	var_dump( $csv->longestLine() );
	$a = "length   ";
	echo "\n$a\n";
	var_dump( $csv->length );
	$a = "fetchAll ";
	echo "\n$a\n";
	var_dump( $csv->fetchAll() );
	$a = "data     ";
	echo "\n$a\n";
	var_dump( $csv->data );
	$a = "close    ";
	echo "\n$a\n";
	var_dump( $csv->close() );


	echo "\n**********************************************";
	echo "\n                    DONE                      ";
	echo "\n**********************************************";
} catch ( Exception $e ) {
	echo "\n**********************************************";
	echo "\n                    ERRORS                    ";
	echo "\n**********************************************\n";

	print_r( $e );
}