#!/usr/bin/env php
<?php
$exit_status = 0;

// Compat function for testing on older PHP.
if ( ! function_exists( 'str_contains' ) ) {
	function str_contains( $haystack, $needle ) {
		return '' === $needle || false !== strpos($haystack, $needle );
	}
}

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

	// Strip color/bolding in output. We want the highlighting usually, just not here.
	$output = preg_replace('/\e[[][A-Za-z0-9];?[0-9]*m?/', '', $output );

	// Remove the last two lines, they're the errata after the test.
	$output = array_slice( $output, 0, -3 );

	echo '::error::' . implode( '%0A', $output ) . "\n";
	$exit_status = 1;
}

// Run PHP8 linting.
exec( 'find plugin -name "*.php" -not -name "*polyfill*"', $files, $returnval );
foreach ( (array) $files as $php_file ) {
	$output = [];
	$php_file = preg_replace( '|^plugin/|', '', $php_file );

	exec( 'cd plugin && php -l ' . escapeshellarg( $php_file ) . ' 2>&1', $output );
	echo implode( "\n", $output ) . "\n";

	if ( ! str_contains( $output[0], 'No syntax errors detected' ) ) {
		echo '::error::' . implode( '%0A', array_slice( $output, 0, -1 ) ) . "\n";
		$exit_status = 1;
	}
}

exit( $exit_status );