<?php
/**
 * A .pot generator for this project, because wp-cli is not installed on this machine.
 *
 * It tokenises with token_get_all() rather than matching with a regular expression: a
 * gettext call whose argument contains a comma, a parenthesis or an apostrophe is common
 * enough in this codebase ("Delivery &amp; Return", "%1$s of %2$s") that a regex would
 * either miss those strings or truncate them, and a .pot that is quietly short is worse
 * than one that is obviously stale.
 *
 * It lives at the repository root rather than inside the theme or the plugin because it is
 * build tooling, and the packages must contain nothing a store does not run.
 *
 * Usage: php makepot.php <source-dir> <text-domain> <out.pot> <package-name> <version>
 *
 *   php tools/makepot.php simple-bangla simple-bangla \
 *       simple-bangla/languages/simple-bangla.pot "Simple Bangla" 1.3.0
 *
 *   php tools/makepot.php simple-bangla-cms simple-bangla-cms \
 *       simple-bangla-cms/languages/simple-bangla-cms.pot "Simple Bangla CMS" 1.5.0
 *
 * It prints any call it could not extract — a text argument that is a variable, or a call
 * carrying another text domain — so a string that quietly never reaches translators is
 * reported rather than silently absent.
 */

$src     = rtrim( $argv[1], "\\/" );
$domain  = $argv[2];
$out     = $argv[3];
$package = $argv[4];
$version = $argv[5];

/**
 * Argument positions per function, zero-indexed: singular, plural, context, domain.
 * null means the function has no such argument.
 */
$FUNCS = array(
	'__'                             => array( 0, null, null, 1 ),
	'_e'                             => array( 0, null, null, 1 ),
	'esc_html__'                     => array( 0, null, null, 1 ),
	'esc_html_e'                     => array( 0, null, null, 1 ),
	'esc_attr__'                     => array( 0, null, null, 1 ),
	'esc_attr_e'                     => array( 0, null, null, 1 ),
	'translate'                      => array( 0, null, null, 1 ),
	'_x'                             => array( 0, null, 1, 2 ),
	'_ex'                            => array( 0, null, 1, 2 ),
	'esc_html_x'                     => array( 0, null, 1, 2 ),
	'esc_attr_x'                     => array( 0, null, 1, 2 ),
	'translate_with_gettext_context' => array( 0, null, 1, 2 ),
	'_n'                             => array( 0, 1, null, 3 ),
	'_nx'                            => array( 0, 1, 3, 4 ),
	'_n_noop'                        => array( 0, 1, null, 2 ),
	'_nx_noop'                       => array( 0, 1, 2, 3 ),
);

/** Turn a PHP string literal token back into its value. */
function literal_value( $token ) {
	$raw   = substr( $token, 1, -1 );
	$quote = $token[0];

	if ( "'" === $quote ) {
		return str_replace( array( '\\\\', "\\'" ), array( '\\', "'" ), $raw );
	}

	// Double-quoted. The tokeniser only hands back T_CONSTANT_ENCAPSED_STRING when there
	// was no interpolation, so a surviving $ is a literal dollar sign.
	return str_replace(
		array( '\\n', '\\r', '\\t', '\\v', '\\f', '\\e', '\\$', '\\"', '\\\\' ),
		array( "\n", "\r", "\t", "\v", "\f", "\x1b", '$', '"', '\\' ),
		$raw
	);
}

/**
 * Evaluate one argument's token list. Returns the string, or null when the argument is
 * anything other than literals joined by "." — a variable cannot be extracted.
 */
function argument_value( $tokens ) {
	$value = '';
	$saw   = false;

	foreach ( $tokens as $t ) {
		if ( is_array( $t ) ) {
			if ( T_CONSTANT_ENCAPSED_STRING === $t[0] ) {
				$value .= literal_value( $t[1] );
				$saw    = true;
				continue;
			}
			if ( T_WHITESPACE === $t[0] || T_COMMENT === $t[0] || T_DOC_COMMENT === $t[0] ) {
				continue;
			}
			return null;
		}
		if ( '.' === $t ) {
			continue;
		}
		return null;
	}

	return $saw ? $value : null;
}

/** Reduce a /* translators: ... *​/ or // translators: ... comment to its text. */
function translator_note( $comment ) {
	$text = preg_replace( '~^/\*+|\*+/$|^//|^#~', '', trim( $comment ) );
	$text = preg_replace( '~^[ \t]*\*[ \t]?~m', '', $text );
	$lines = array_filter( array_map( 'trim', explode( "\n", $text ) ), 'strlen' );

	return implode( ' ', $lines );
}

/** Escape a value for a PO msgid. */
function po_escape( $s ) {
	return str_replace(
		array( '\\', '"', "\n", "\t", "\r" ),
		array( '\\\\', '\\"', '\\n', '\\t', '\\r' ),
		$s
	);
}

// ---------------------------------------------------------------------------

$files = array();
$it    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $src, FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $file ) {
	if ( 'php' === strtolower( $file->getExtension() ) ) {
		$files[] = $file->getPathname();
	}
}
sort( $files );

$entries = array();   // key => entry
$foreign = array();   // strings carrying another domain, reported not extracted
$dynamic = array();   // calls whose text is not a literal

foreach ( $files as $path ) {
	$rel    = str_replace( '\\', '/', substr( $path, strlen( $src ) + 1 ) );
	$tokens = token_get_all( file_get_contents( $path ) );
	$count  = count( $tokens );

	for ( $i = 0; $i < $count; $i++ ) {
		$tok = $tokens[ $i ];

		if ( ! is_array( $tok ) || T_STRING !== $tok[0] || ! isset( $FUNCS[ $tok[1] ] ) ) {
			continue;
		}

		// A method or a declaration of the same name is not a gettext call.
		for ( $b = $i - 1; $b >= 0 && is_array( $tokens[ $b ] ) && T_WHITESPACE === $tokens[ $b ][0]; $b-- );
		if ( $b >= 0 ) {
			$prev = $tokens[ $b ];
			if ( is_array( $prev ) && in_array( $prev[0], array( T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION ), true ) ) {
				continue;
			}
			if ( ! is_array( $prev ) && '$' === $prev ) {
				continue;
			}
		}

		// Find the opening parenthesis.
		for ( $j = $i + 1; $j < $count && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0]; $j++ );
		if ( $j >= $count || '(' !== $tokens[ $j ] ) {
			continue;
		}

		// Split the argument list on top-level commas.
		$args  = array();
		$cur   = array();
		$depth = 0;
		for ( $k = $j; $k < $count; $k++ ) {
			$t = $tokens[ $k ];

			if ( ! is_array( $t ) ) {
				if ( '(' === $t || '[' === $t || '{' === $t ) {
					$depth++;
					if ( 1 === $depth ) {
						continue;
					}
				} elseif ( ')' === $t || ']' === $t || '}' === $t ) {
					$depth--;
					if ( 0 === $depth ) {
						$args[] = $cur;
						break;
					}
				} elseif ( ',' === $t && 1 === $depth ) {
					$args[] = $cur;
					$cur    = array();
					continue;
				}
			}

			$cur[] = $t;
		}

		list( $pSingular, $pPlural, $pContext, $pDomain ) = $FUNCS[ $tok[1] ];

		$callDomain = isset( $args[ $pDomain ] ) ? argument_value( $args[ $pDomain ] ) : null;
		if ( $callDomain !== $domain ) {
			if ( null !== $callDomain ) {
				$foreign[] = "$rel:{$tok[2]}  {$tok[1]}(…, '$callDomain')";
			} else {
				$foreign[] = "$rel:{$tok[2]}  {$tok[1]}() with no literal text domain";
			}
			continue;
		}

		$singular = argument_value( $args[ $pSingular ] );
		if ( null === $singular ) {
			$dynamic[] = "$rel:{$tok[2]}  {$tok[1]}() text is not a literal";
			continue;
		}

		$plural  = ( null !== $pPlural && isset( $args[ $pPlural ] ) ) ? argument_value( $args[ $pPlural ] ) : null;
		$context = ( null !== $pContext && isset( $args[ $pContext ] ) ) ? argument_value( $args[ $pContext ] ) : null;

		/*
		 * A translators: comment counts when it ends on the call's own line or the line
		 * above it — makepot's rule, and the reason it is a line test rather than a list
		 * of token types that may sit between the two. This codebase writes the comment
		 * above `'label' => sprintf( __( … ) )` and above `$x = _x( … )` alike, and any
		 * whitelist wide enough for both would also reach comments belonging to the
		 * statement before.
		 */
		$note  = null;
		$line  = (int) $tok[2];
		for ( $b = $i - 1; $b >= 0; $b-- ) {
			$t = $tokens[ $b ];
			if ( ! is_array( $t ) ) {
				continue;
			}
			if ( T_COMMENT === $t[0] || T_DOC_COMMENT === $t[0] ) {
				$ends = (int) $t[2] + substr_count( $t[1], "\n" );
				if ( $ends < $line - 1 ) {
					break;
				}
				if ( false !== stripos( $t[1], 'translators:' ) ) {
					$note = translator_note( $t[1] );
					break;
				}
				continue;
			}
			if ( (int) $t[2] < $line - 1 ) {
				break;
			}
		}

		$key = ( null === $context ? '' : $context . "\4" ) . $singular . ( null === $plural ? '' : "\5" . $plural );

		if ( ! isset( $entries[ $key ] ) ) {
			$entries[ $key ] = array(
				'singular' => $singular,
				'plural'   => $plural,
				'context'  => $context,
				'notes'    => array(),
				'refs'     => array(),
			);
		}
		$entries[ $key ]['refs'][] = array( $rel, (int) $tok[2] );
		if ( $note && ! in_array( $note, $entries[ $key ]['notes'], true ) ) {
			$entries[ $key ]['notes'][] = $note;
		}
	}
}

// Sort by first reference, so the file is stable across runs and reads in file order.
uasort(
	$entries,
	function ( $a, $b ) {
		$c = strcmp( $a['refs'][0][0], $b['refs'][0][0] );
		return $c ?: ( $a['refs'][0][1] <=> $b['refs'][0][1] );
	}
);

$po  = "# Copyright (C) " . gmdate( 'Y' ) . " $package\n";
$po .= "# This file is distributed under the GNU General Public License v2 or later.\n";
$po .= "msgid \"\"\nmsgstr \"\"\n";
$po .= "\"Project-Id-Version: $package $version\\n\"\n";
$po .= "\"Report-Msgid-Bugs-To: https://simplebangla.com/\\n\"\n";
$po .= '"POT-Creation-Date: ' . gmdate( 'Y-m-d H:i' ) . "+0000\\n\"\n";
$po .= "\"MIME-Version: 1.0\\n\"\n";
$po .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n";
$po .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
$po .= "\"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n\"\n";
$po .= "\"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n\"\n";
$po .= "\"Language-Team: LANGUAGE <LL@li.org>\\n\"\n";
$po .= "\"Plural-Forms: nplurals=2; plural=(n != 1);\\n\"\n";
$po .= "\"X-Domain: $domain\\n\"\n";

foreach ( $entries as $e ) {
	$po .= "\n";
	foreach ( $e['notes'] as $note ) {
		$po .= "#. $note\n";
	}
	foreach ( $e['refs'] as $ref ) {
		$po .= "#: {$ref[0]}:{$ref[1]}\n";
	}
	if ( null !== $e['context'] ) {
		$po .= 'msgctxt "' . po_escape( $e['context'] ) . "\"\n";
	}
	$po .= 'msgid "' . po_escape( $e['singular'] ) . "\"\n";
	if ( null !== $e['plural'] ) {
		$po .= 'msgid_plural "' . po_escape( $e['plural'] ) . "\"\n";
		$po .= "msgstr[0] \"\"\nmsgstr[1] \"\"\n";
	} else {
		$po .= "msgstr \"\"\n";
	}
}

file_put_contents( $out, $po );

printf( "%s: %d strings from %d files -> %s\n", $domain, count( $entries ), count( $files ), $out );

if ( $dynamic ) {
	echo "\n  Not extractable (text is not a literal):\n";
	foreach ( array_unique( $dynamic ) as $d ) {
		echo "    $d\n";
	}
}
if ( $foreign ) {
	echo "\n  Skipped (other or missing text domain):\n";
	foreach ( array_unique( $foreign ) as $f ) {
		echo "    $f\n";
	}
}
