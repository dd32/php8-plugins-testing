#!/usr/bin/env php
<?php
$exit_status = 0;

// PHP8 compat functions for testing.
require __DIR__ . '/vendor/symfony/polyfill-php80/Php80.php';
require __DIR__ . '/vendor/symfony/polyfill-php80/bootstrap.php';

// Prepend some useful links.. This should be an info rather than warning/error? Not sure GitHub has one though?
if ( !empty( $argv[1] ) ) {
	$slug    = $argv[1];
	$version = $argv[2] ?? '';
	echo '::warning::' .
		"Plugin: https://wordpress.org/plugins/$slug/" . "%0A" .
		"Trac: https://plugins.trac.wordpress.org/browser/$slug/" . ( $version && 'trunk' != $version ? 'tags/' : '' ) . $version;
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
	$output = array_slice( $output, 0, -2 );

	echo '::error::' . implode( '%0A', $output ) . "\n";
	$exit_status = 1;
}

// Run PHP8 linting.
exec( 'find plugin -name "*.php" -not -name "*polyfill*"', $files, $returnval );
$notices = [ 'error' => [], 'warning' => [] ];

foreach ( (array) $files as $_php_file ) {
	$output = [];
	$php_file = preg_replace( '|^plugin/|', '', $_php_file );

	exec( 'cd plugin && php -l ' . escapeshellarg( $php_file ) . ' 2>&1', $output );
	echo implode( "\n", $output ) . "\n";

	if ( ! str_contains( $output[0], 'No syntax errors detected' ) ) {
		$error = 'error';
		if ( str_starts_with( $output[0], 'PHP Warning:' ) && ! str_contains( $output[0], 'PHP Fatal error' ) ) {
			$error = 'warning';
		}

		// Remove the filename from all messages, shorter easier to read.
		foreach ( $output as $i => $line ) {
			$output[ $i ] = str_replace( [ "in $php_file on", "in $_php_file on" ], 'on', $line );
		}

		$notices[ $error ] = array_merge(
			$notices[ $error ],
			[ '', '', $php_file ], // prepend the filename.
			$output
		);
		$exit_status = 1;
	}
}
foreach ( $notices as $type => $data ) {
	if ( $data ) {
		echo "::$type::" . implode( '%0A', array_slice( $data, 0, -1 ) ) . "\n";
	}
}

exit( $exit_status );