#!/usr/bin/env php
<?php

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

	echo '::error::' . implode( '%0A', $output );
}