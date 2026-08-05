<?php
/**
 * Site header.
 *
 * One header in the DOM, made sticky with position: sticky and an .is-stuck class that the
 * header script sets. The reference site duplicates its markup for the stuck state; we do not,
 * because two copies of a menu means two copies of every id and aria relationship.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main">
	<?php esc_html_e( 'Skip to content', 'simple-bangla' ); ?>
</a>

<div id="page" class="sb-site">

	<header id="masthead" class="sb-header">
		<?php get_template_part( 'template-parts/header/bar' ); ?>
		<?php get_template_part( 'template-parts/header/nav' ); ?>
	</header>

	<?php get_template_part( 'template-parts/header/drawer' ); ?>
