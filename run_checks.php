#!/usr/bin/env php
<?php
$exit_status = 0;

// Run PHP CS PHPCompatibility checks.

exec( ( file_exists( 'vendor/bin/phpcs' ) ? 'vendor/bin/phpcs' : 'phpcs' ) . ' -s plugin', $output, $returnval );
echo implode( "\n", $output );
if ( $returnval > 0 ) {

	// We don't want the ... outputs.
	foreach ( $output as $i => $line ) {
		if ( $line ) {
			unset( $output[ $i ] );
		} else {
			break;
		}
	}
	// Remove empty lines.
	foreach ( $output as $i => $line ) {
		if ( '' == trim( $line ) ) {
			unset( $output[ $i ] );
		} else {
			break;
		}
	}

	echo '::error::' . implode( '%0A', $output ) . "\n";
	$exit_status = 1;
}

// Run PHP8 linting.
exec( 'find plugin -name "*.php" -not -name "*polyfill*"', $files, $returnval );
foreach ( (array) $files as $php_file ) {
	$output = [];
	exec( 'php -l ' . escapeshellarg( $php_file ), $output );
	echo implode( "\n", $output ) . "\n";

	if ( ! str_contains( $output[0], 'No syntax errors detected' ) ) {
		echo '::error::' . implode( '%0A', $output ) . "\n";
		$exit_status = 1;
	}
}

exit( $exit_status );