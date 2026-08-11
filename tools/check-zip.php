<?php
/**
 * Read the built packages with PHP's own ZipArchive and assert what WordPress's installers
 * assert. Everything here has been a real failure at least once: a backslash separator that
 * made a complete theme report a missing style.css, an untracked vendored module that made a
 * plugin's interface never boot, and a required file left out of the archive.
 *
 * Usage: php -d extension=php_zip.dll check-zip.php <dist-dir>
 */

$dist = rtrim( $argv[1], "\\/" );

$pass = 0;
$fail = 0;
function ok( $name, $cond, $detail = '' ) {
	global $pass, $fail;
	if ( $cond ) { $pass++; echo "PASS  $name\n"; }
	else { $fail++; echo "FAIL  $name" . ( $detail ? "  — $detail" : '' ) . "\n"; }
}

$packages = array(
	'theme'  => array(
		'zip'     => glob( "$dist/simple-bangla-[0-9]*.zip" ),
		'slug'    => 'simple-bangla',
		'header'  => array( 'style.css', 'Theme Name:' ),
		'boot'    => 'functions.php',
		'const'   => 'SIMPLE_BANGLA_DIR',
		'require' => array( 'functions.php' ),
	),
	'plugin' => array(
		'zip'     => glob( "$dist/simple-bangla-cms-*.zip" ),
		'slug'    => 'simple-bangla-cms',
		'header'  => array( 'simple-bangla-cms.php', 'Plugin Name:' ),
		'boot'    => 'simple-bangla-cms.php',
		'const'   => 'SIMPLE_BANGLA_CMS_DIR',
		'require' => array( 'simple-bangla-cms.php' ),
		'must'    => array( 'assets/vendor/preact-htm.module.js' ),
	),
);

foreach ( $packages as $kind => $spec ) {

	// The theme glob would also catch the plugin's zip; keep only the exact slug.
	$candidates = array_values( array_filter( $spec['zip'], function ( $p ) use ( $spec ) {
		return preg_match( '~/' . preg_quote( $spec['slug'], '~' ) . '-\d[\d.]*\.zip$~', str_replace( '\\', '/', $p ) );
	} ) );

	echo "\n--- $kind: " . ( $candidates ? basename( end( $candidates ) ) : 'MISSING' ) . " ---\n";
	ok( "$kind package exists", count( $candidates ) > 0 );
	if ( ! $candidates ) { continue; }

	$path = end( $candidates );
	$zip  = new ZipArchive();
	ok( "$kind archive opens", true === $zip->open( $path ) );

	$names = array();
	for ( $i = 0; $i < $zip->numFiles; $i++ ) {
		$names[] = $zip->getNameIndex( $i );
	}

	$backslashed = array_filter( $names, function ( $n ) { return false !== strpos( $n, '\\' ); } );
	ok( "$kind: no entry name contains a backslash", empty( $backslashed ), implode( ', ', array_slice( $backslashed, 0, 3 ) ) );

	$tops = array_unique( array_map( function ( $n ) { return explode( '/', $n )[0]; }, $names ) );
	ok( "$kind: exactly one top-level folder", 1 === count( $tops ), implode( ', ', $tops ) );
	ok( "$kind: that folder is named {$spec['slug']}", array( $spec['slug'] ) === array_values( $tops ), implode( ', ', $tops ) );

	$has = function ( $rel ) use ( $names, $spec ) {
		return in_array( $spec['slug'] . '/' . $rel, $names, true );
	};
	$read = function ( $rel ) use ( $zip, $spec ) {
		return $zip->getFromName( $spec['slug'] . '/' . $rel );
	};

	list( $headerFile, $headerText ) = $spec['header'];
	ok( "$kind: $headerFile is present", $has( $headerFile ) );
	ok( "$kind: it carries the \"$headerText\" header", false !== strpos( (string) $read( $headerFile ), $headerText ) );
	ok( "$kind: {$spec['boot']} is present", $has( $spec['boot'] ) );

	foreach ( $spec['must'] ?? array() as $rel ) {
		ok( "$kind: $rel is in the package", $has( $rel ) );
	}

	// Every file the bootstrap requires must actually be in the archive.
	$missingRequires = array();
	$requireCount    = 0;
	foreach ( $spec['require'] as $file ) {
		$src = (string) $read( $file );
		if ( preg_match_all( '~require(?:_once)?\s+' . preg_quote( $spec['const'], '~' ) . '\s*\.\s*\'([^\']+)\'~', $src, $m ) ) {
			foreach ( $m[1] as $rel ) {
				$requireCount++;
				if ( ! $has( $rel ) ) { $missingRequires[] = $rel; }
			}
		}
	}
	ok( "$kind: all $requireCount required files are in the package", empty( $missingRequires ), implode( ', ', $missingRequires ) );

	// Every relative ES-module import must resolve inside the package. One missing module is a
	// blank screen at /manage rather than an error anyone would see in PHP.
	$badImports = array();
	$importCount = 0;
	foreach ( $names as $n ) {
		if ( '.js' !== substr( $n, -3 ) ) { continue; }
		$src = (string) $zip->getFromName( $n );
		$dir = dirname( $n );
		if ( preg_match_all( '~(?:^|[\s;{])(?:import|export)[^\'"\n]*?from\s*[\'"]([^\'"]+)[\'"]|import\s*\(\s*[\'"]([^\'"]+)[\'"]~', $src, $m ) ) {
			foreach ( array_merge( $m[1], $m[2] ) as $spec2 ) {
				if ( '' === $spec2 || '.' !== $spec2[0] ) { continue; }
				$importCount++;
				$target = $dir . '/' . $spec2;
				// Resolve ./ and ../ by hand; realpath cannot see inside an archive.
				$out = array();
				foreach ( explode( '/', $target ) as $part ) {
					if ( '.' === $part || '' === $part ) { continue; }
					if ( '..' === $part ) { array_pop( $out ); continue; }
					$out[] = $part;
				}
				$resolved = implode( '/', $out );
				if ( ! in_array( $resolved, $names, true ) ) {
					$badImports[] = "$n -> $spec2";
				}
			}
		}
	}
	ok( "$kind: all $importCount relative module imports resolve inside the package", empty( $badImports ), implode( '; ', array_slice( $badImports, 0, 3 ) ) );

	$cruft = array_filter( $names, function ( $n ) {
		return preg_match( '~(^|/)(\.git|node_modules|\.DS_Store|Thumbs\.db|.*\.swp)($|/)~', $n );
	} );
	ok( "$kind: no editor or VCS cruft", empty( $cruft ), implode( ', ', array_slice( $cruft, 0, 3 ) ) );

	echo "      {$zip->numFiles} entries\n";
	$zip->close();
}

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
