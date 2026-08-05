<?php
/**
 * One-click demo content.
 *
 * Builds a catalogue big enough to judge the design against: a three-level category tree,
 * a few dozen products with real price and sale-price spreads, navigation menus wired to
 * match, and homepage banners.
 *
 * Every image is generated here with GD from the theme's own palette. Nothing is copied from
 * the reference site, and nothing is fetched over the network — an importer that needs an
 * internet connection is an importer that fails on a local install.
 *
 * @package Simple_Bangla
 */

defined( 'ABSPATH' ) || exit;

/** Option flag recording that the import has run. */
const SIMPLE_BANGLA_DEMO_FLAG = 'simple_bangla_demo_imported';

/** Meta key marking every post, term and attachment the importer created. */
const SIMPLE_BANGLA_DEMO_MARK = '_simple_bangla_demo';

/**
 * The category tree. Slug => [name, parent slug].
 *
 * @return array<string,array{0:string,1:string}>
 */
function simple_bangla_demo_categories() {
	return array(
		'best-selling'          => array( 'Best Selling', '' ),
		'microphone'            => array( 'Microphone', '' ),
		'gadgets'               => array( 'Gadgets', '' ),
		'airpods'               => array( "Airpod's", 'gadgets' ),
		'airpods-case'          => array( "Airpod's Case", 'airpods' ),
		'power-bank'            => array( 'Power Bank', 'gadgets' ),
		'smart-watch'           => array( 'Smart Watch', 'gadgets' ),
		'watch-strap'           => array( 'Watch Strap', 'smart-watch' ),
		'headphone'             => array( 'Headphone', 'gadgets' ),
		'bluetooth-headphones'  => array( "Bluetooth Headphone's", 'headphone' ),
		'tws'                   => array( 'TWS', 'headphone' ),
		'neckband'              => array( 'Neckband', 'headphone' ),
		'mobile-charger'        => array( 'Mobile Charger', 'gadgets' ),
		'cable'                 => array( 'Cable', 'mobile-charger' ),
		'bluetooth-speaker'     => array( 'Bluetooth Speaker', 'gadgets' ),
		'rechargeable-fan'      => array( 'Rechargeable Fan', 'gadgets' ),
		'lighting'              => array( 'Lighting', '' ),
		'ring-light'            => array( 'Ring Light', 'lighting' ),
		'softbox'               => array( 'Softbox', 'lighting' ),
		'led-panel'             => array( 'LED Panel', 'lighting' ),
		'tripods'               => array( "Tripod's", '' ),
		'selfie-stick'          => array( 'Selfie Stick', 'tripods' ),
		'gimbal'                => array( 'Gimbal', 'tripods' ),
		'phone-holder'          => array( 'Phone Holder', 'tripods' ),
		'camera-accessories'    => array( 'Camera Accessories', '' ),
		'memory-card'           => array( 'Memory Card', 'camera-accessories' ),
		'camera-bag'            => array( 'Camera Bag', 'camera-accessories' ),
		'computer-accessories'  => array( 'Computer Accessories', '' ),
		'keyboard'              => array( 'Keyboard', 'computer-accessories' ),
		'mouse'                 => array( 'Mouse', 'computer-accessories' ),
		'usb-hub'               => array( 'USB Hub', 'computer-accessories' ),
		'home-appliance'        => array( 'Home Appliance', '' ),
		'trimmer'               => array( 'Trimmer', 'home-appliance' ),
		'hair-dryer'            => array( 'Hair Dryer', 'home-appliance' ),
	);
}

/**
 * The demo catalogue: [name, category slug, regular price, sale price or 0].
 *
 * Prices are in whole Taka and sit in the range a Bangladeshi gadget shop actually sells at,
 * so the price filter and the struck-through sale styling both have something real to show.
 *
 * @return array<int,array{0:string,1:string,2:int,3:int}>
 */
function simple_bangla_demo_products() {
	return array(
		array( 'Studio Lavalier Microphone SB-M10', 'microphone', 1290, 990 ),
		array( 'Wireless Dual Lapel Mic SB-M22', 'microphone', 3450, 2799 ),
		array( 'Shotgun Video Microphone SB-M40', 'microphone', 4900, 0 ),
		array( 'USB Condenser Mic SB-M55', 'microphone', 6200, 5490 ),

		array( 'True Wireless Earbuds SB-A1', 'airpods', 2100, 1590 ),
		array( 'Pro Noise Cancelling Buds SB-A3', 'airpods', 4800, 3990 ),
		array( 'Silicone Earbuds Case — Cream', 'airpods-case', 390, 0 ),
		array( 'Hard Shell Earbuds Case — Black', 'airpods-case', 550, 420 ),

		array( '10000mAh Slim Power Bank SB-P10', 'power-bank', 1750, 1390 ),
		array( '20000mAh Fast Power Bank SB-P20', 'power-bank', 2990, 2450 ),
		array( 'Magnetic Wireless Power Bank SB-P5', 'power-bank', 3400, 0 ),

		array( 'Amoled Smart Watch SB-W7', 'smart-watch', 3900, 2990 ),
		array( 'Fitness Smart Watch SB-W3', 'smart-watch', 2200, 0 ),
		array( 'Woven Nylon Watch Strap 22mm', 'watch-strap', 490, 350 ),
		array( 'Silicone Watch Strap 20mm', 'watch-strap', 350, 0 ),

		array( 'Over-Ear Bluetooth Headphone SB-H8', 'bluetooth-headphones', 3200, 2590 ),
		array( 'Foldable Bluetooth Headphone SB-H4', 'bluetooth-headphones', 1900, 0 ),
		array( 'Gaming TWS Earbuds SB-T9', 'tws', 2450, 1990 ),
		array( 'Compact TWS Earbuds SB-T2', 'tws', 1350, 0 ),
		array( 'Magnetic Neckband SB-N5', 'neckband', 1150, 890 ),
		array( 'Sports Neckband SB-N2', 'neckband', 890, 0 ),

		array( '33W GaN Fast Charger SB-C33', 'mobile-charger', 1450, 1150 ),
		array( '20W Dual Port Charger SB-C20', 'mobile-charger', 990, 0 ),
		array( 'Braided USB-C Cable 1.5m', 'cable', 390, 290 ),
		array( 'Lightning Cable 1m', 'cable', 450, 0 ),

		array( 'Portable Bluetooth Speaker SB-S6', 'bluetooth-speaker', 2400, 1890 ),
		array( 'Mini Bluetooth Speaker SB-S2', 'bluetooth-speaker', 1100, 0 ),
		array( 'Rechargeable Table Fan SB-F12', 'rechargeable-fan', 2650, 2190 ),

		array( '10 inch Ring Light with Stand', 'ring-light', 1650, 1290 ),
		array( '18 inch Studio Ring Light', 'ring-light', 5900, 4990 ),
		array( '60x60 Softbox Lighting Kit', 'softbox', 4200, 0 ),
		array( 'Bi-Colour LED Panel SB-L40', 'led-panel', 3800, 3190 ),

		array( 'Aluminium Selfie Stick Tripod SB-TR3', 'selfie-stick', 890, 690 ),
		array( 'Bluetooth Selfie Stick SB-TR1', 'selfie-stick', 650, 0 ),
		array( '3-Axis Phone Gimbal SB-G2', 'gimbal', 7900, 6790 ),
		array( 'Desk Phone Holder — Adjustable', 'phone-holder', 490, 0 ),
		array( '160cm Camera Tripod SB-TR9', 'tripods', 3200, 2590 ),

		array( '64GB Class 10 Memory Card', 'memory-card', 750, 590 ),
		array( '128GB High Speed Memory Card', 'memory-card', 1350, 0 ),
		array( 'Water Resistant Camera Bag', 'camera-bag', 2400, 1990 ),

		array( 'Mechanical Keyboard SB-K87', 'keyboard', 4500, 3790 ),
		array( 'Slim Wireless Keyboard SB-K10', 'keyboard', 1800, 0 ),
		array( 'Silent Wireless Mouse SB-MS4', 'mouse', 750, 590 ),
		array( 'RGB Gaming Mouse SB-MS9', 'mouse', 1450, 0 ),
		array( '6-in-1 USB-C Hub SB-U6', 'usb-hub', 2900, 2350 ),

		array( 'Cordless Beard Trimmer SB-TZ5', 'trimmer', 1650, 1290 ),
		array( 'Travel Hair Dryer SB-HD2', 'hair-dryer', 1900, 0 ),
	);
}

/* ------------------------------------------------------------------ *
 * Image generation
 * ------------------------------------------------------------------ */

/**
 * Is GD available to draw with?
 *
 * @return bool
 */
function simple_bangla_can_draw() {
	return function_exists( 'imagecreatetruecolor' ) && function_exists( 'imagepng' );
}

/**
 * Turn a string into a stable hue.
 *
 * Same product name always yields the same colour, so re-running the importer does not
 * reshuffle the catalogue's appearance.
 *
 * @param string $seed Any string.
 * @return int Hue, 0-359.
 */
function simple_bangla_demo_hue( $seed ) {
	return (int) ( hexdec( substr( md5( $seed ), 0, 4 ) ) % 360 );
}

/**
 * Allocate an RGB colour from HSL.
 *
 * @param resource|GdImage $image      Target image.
 * @param float            $hue        0-360.
 * @param float            $saturation 0-1.
 * @param float            $lightness  0-1.
 * @return int Colour identifier.
 */
function simple_bangla_demo_color( $image, $hue, $saturation, $lightness ) {

	$c = ( 1 - abs( 2 * $lightness - 1 ) ) * $saturation;
	$x = $c * ( 1 - abs( fmod( $hue / 60, 2 ) - 1 ) );
	$m = $lightness - $c / 2;

	$sector = (int) floor( $hue / 60 ) % 6;

	$table = array(
		array( $c, $x, 0 ),
		array( $x, $c, 0 ),
		array( 0, $c, $x ),
		array( 0, $x, $c ),
		array( $x, 0, $c ),
		array( $c, 0, $x ),
	);

	list( $r, $g, $b ) = $table[ $sector ];

	return imagecolorallocate(
		$image,
		(int) round( ( $r + $m ) * 255 ),
		(int) round( ( $g + $m ) * 255 ),
		(int) round( ( $b + $m ) * 255 )
	);
}

/**
 * Which silhouette a category's placeholders are drawn with.
 *
 * @return array<string,string> Category slug => shape key.
 */
function simple_bangla_demo_shapes() {
	return array(
		'microphone'           => 'mic',
		'airpods'              => 'earbuds',
		'airpods-case'         => 'earbuds',
		'power-bank'           => 'powerbank',
		'smart-watch'          => 'watch',
		'watch-strap'          => 'watch',
		'headphone'            => 'headphone',
		'bluetooth-headphones' => 'headphone',
		'tws'                  => 'earbuds',
		'neckband'             => 'neckband',
		'mobile-charger'       => 'charger',
		'cable'                => 'cable',
		'bluetooth-speaker'    => 'speaker',
		'rechargeable-fan'     => 'fan',
		'lighting'             => 'ringlight',
		'ring-light'           => 'ringlight',
		'softbox'              => 'softbox',
		'led-panel'            => 'softbox',
		'tripods'              => 'tripod',
		'selfie-stick'         => 'tripod',
		'gimbal'               => 'tripod',
		'phone-holder'         => 'phone',
		'camera-accessories'   => 'camera',
		'memory-card'          => 'memcard',
		'camera-bag'           => 'bag',
		'keyboard'             => 'keyboard',
		'mouse'                => 'mouse',
		'usb-hub'              => 'hub',
		'trimmer'              => 'trimmer',
		'hair-dryer'           => 'dryer',
		'best-selling'         => 'speaker',
		'gadgets'              => 'phone',
		'computer-accessories' => 'keyboard',
		'home-appliance'       => 'dryer',
	);
}

/**
 * Draw a filled rounded rectangle.
 *
 * GD has no primitive for this, and every silhouette below needs one.
 *
 * @param resource|GdImage $img    Target.
 * @param int              $x1     Left.
 * @param int              $y1     Top.
 * @param int              $x2     Right.
 * @param int              $y2     Bottom.
 * @param int              $radius Corner radius.
 * @param int              $color  Colour identifier.
 */
function simple_bangla_demo_rrect( $img, $x1, $y1, $x2, $y2, $radius, $color ) {

	$radius = max( 0, min( $radius, (int) ( min( $x2 - $x1, $y2 - $y1 ) / 2 ) ) );

	if ( ! $radius ) {
		imagefilledrectangle( $img, $x1, $y1, $x2, $y2, $color );
		return;
	}

	imagefilledrectangle( $img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color );
	imagefilledrectangle( $img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color );

	$d = $radius * 2;

	imagefilledellipse( $img, $x1 + $radius, $y1 + $radius, $d, $d, $color );
	imagefilledellipse( $img, $x2 - $radius, $y1 + $radius, $d, $d, $color );
	imagefilledellipse( $img, $x1 + $radius, $y2 - $radius, $d, $d, $color );
	imagefilledellipse( $img, $x2 - $radius, $y2 - $radius, $d, $d, $color );
}

/**
 * Draw an annulus by punching a hole out of a disc.
 *
 * @param resource|GdImage $img       Target.
 * @param int              $cx        Centre x.
 * @param int              $cy        Centre y.
 * @param int              $diameter  Outer diameter.
 * @param int              $thickness Ring thickness.
 * @param int              $color     Ring colour.
 * @param int              $hole      Colour to punch the middle with.
 */
function simple_bangla_demo_ring( $img, $cx, $cy, $diameter, $thickness, $color, $hole ) {
	imagefilledellipse( $img, $cx, $cy, $diameter, $diameter, $color );
	imagefilledellipse( $img, $cx, $cy, max( 1, $diameter - $thickness * 2 ), max( 1, $diameter - $thickness * 2 ), $hole );
}

/**
 * Draw one product silhouette, centred on the canvas.
 *
 * Coordinates are expressed against a 100-unit square and scaled, so every shape keeps its
 * proportions at any canvas size.
 *
 * @param resource|GdImage $img    Target.
 * @param string           $shape  Shape key from simple_bangla_demo_shapes().
 * @param int              $size   Canvas edge.
 * @param int              $body   Main colour.
 * @param int              $accent Secondary colour.
 * @param int              $paper  Background colour, used to punch holes.
 */
function simple_bangla_demo_draw( $img, $shape, $size, $body, $accent, $paper ) {

	$u = function ( $n ) use ( $size ) {
		return (int) round( $size * $n / 100 );
	};

	switch ( $shape ) {

		case 'headphone':
			simple_bangla_demo_ring( $img, $u( 50 ), $u( 52 ), $u( 62 ), $u( 7 ), $body, $paper );
			// The band is an arc, so the lower half of the ring is painted back out.
			imagefilledrectangle( $img, $u( 10 ), $u( 52 ), $u( 90 ), $u( 90 ), $paper );
			simple_bangla_demo_rrect( $img, $u( 15 ), $u( 45 ), $u( 31 ), $u( 72 ), $u( 7 ), $accent );
			simple_bangla_demo_rrect( $img, $u( 69 ), $u( 45 ), $u( 85 ), $u( 72 ), $u( 7 ), $accent );
			break;

		case 'earbuds':
			simple_bangla_demo_rrect( $img, $u( 30 ), $u( 52 ), $u( 70 ), $u( 80 ), $u( 9 ), $body );
			imagefilledellipse( $img, $u( 39 ), $u( 34 ), $u( 17 ), $u( 17 ), $accent );
			imagefilledellipse( $img, $u( 61 ), $u( 34 ), $u( 17 ), $u( 17 ), $accent );
			simple_bangla_demo_rrect( $img, $u( 36 ), $u( 34 ), $u( 42 ), $u( 50 ), $u( 3 ), $accent );
			simple_bangla_demo_rrect( $img, $u( 58 ), $u( 34 ), $u( 64 ), $u( 50 ), $u( 3 ), $accent );
			break;

		case 'watch':
			simple_bangla_demo_rrect( $img, $u( 40 ), $u( 12 ), $u( 60 ), $u( 34 ), $u( 5 ), $accent );
			simple_bangla_demo_rrect( $img, $u( 40 ), $u( 66 ), $u( 60 ), $u( 88 ), $u( 5 ), $accent );
			simple_bangla_demo_rrect( $img, $u( 32 ), $u( 30 ), $u( 68 ), $u( 70 ), $u( 11 ), $body );
			simple_bangla_demo_rrect( $img, $u( 38 ), $u( 36 ), $u( 62 ), $u( 64 ), $u( 8 ), $paper );
			break;

		case 'powerbank':
			simple_bangla_demo_rrect( $img, $u( 33 ), $u( 18 ), $u( 67 ), $u( 82 ), $u( 8 ), $body );
			for ( $i = 0; $i < 4; $i++ ) {
				simple_bangla_demo_rrect( $img, $u( 40 ), $u( 30 + $i * 11 ), $u( 60 ), $u( 36 + $i * 11 ), $u( 2 ), $paper );
			}
			break;

		case 'speaker':
			simple_bangla_demo_rrect( $img, $u( 25 ), $u( 22 ), $u( 75 ), $u( 78 ), $u( 10 ), $body );
			simple_bangla_demo_ring( $img, $u( 50 ), $u( 40 ), $u( 26 ), $u( 5 ), $accent, $body );
			simple_bangla_demo_ring( $img, $u( 50 ), $u( 64 ), $u( 16 ), $u( 4 ), $accent, $body );
			break;

		case 'mic':
			simple_bangla_demo_rrect( $img, $u( 41 ), $u( 14 ), $u( 59 ), $u( 52 ), $u( 9 ), $body );
			simple_bangla_demo_ring( $img, $u( 50 ), $u( 50 ), $u( 40 ), $u( 5 ), $accent, $paper );
			imagefilledrectangle( $img, $u( 26 ), $u( 20 ), $u( 74 ), $u( 50 ), $paper );
			simple_bangla_demo_rrect( $img, $u( 41 ), $u( 14 ), $u( 59 ), $u( 52 ), $u( 9 ), $body );
			simple_bangla_demo_rrect( $img, $u( 47 ), $u( 62 ), $u( 53 ), $u( 80 ), $u( 2 ), $accent );
			simple_bangla_demo_rrect( $img, $u( 34 ), $u( 80 ), $u( 66 ), $u( 86 ), $u( 3 ), $accent );
			break;

		case 'neckband':
			simple_bangla_demo_ring( $img, $u( 50 ), $u( 46 ), $u( 60 ), $u( 6 ), $body, $paper );
			imagefilledrectangle( $img, $u( 40 ), $u( 10 ), $u( 60 ), $u( 46 ), $paper );
			imagefilledellipse( $img, $u( 22 ), $u( 62 ), $u( 14 ), $u( 14 ), $accent );
			imagefilledellipse( $img, $u( 78 ), $u( 62 ), $u( 14 ), $u( 14 ), $accent );
			break;

		case 'charger':
			simple_bangla_demo_rrect( $img, $u( 30 ), $u( 32 ), $u( 70 ), $u( 78 ), $u( 10 ), $body );
			simple_bangla_demo_rrect( $img, $u( 40 ), $u( 18 ), $u( 45 ), $u( 34 ), $u( 2 ), $accent );
			simple_bangla_demo_rrect( $img, $u( 55 ), $u( 18 ), $u( 60 ), $u( 34 ), $u( 2 ), $accent );
			simple_bangla_demo_rrect( $img, $u( 42 ), $u( 60 ), $u( 58 ), $u( 66 ), $u( 3 ), $paper );
			break;

		case 'cable':
			simple_bangla_demo_rrect( $img, $u( 16 ), $u( 20 ), $u( 30 ), $u( 38 ), $u( 4 ), $accent );
			simple_bangla_demo_rrect( $img, $u( 70 ), $u( 62 ), $u( 84 ), $u( 80 ), $u( 4 ), $accent );
			imagesetthickness( $img, max( 2, $u( 5 ) ) );
			imagearc( $img, $u( 50 ), $u( 50 ), $u( 56 ), $u( 56 ), 200, 20, $body );
			imagesetthickness( $img, 1 );
			break;

		case 'fan':
			simple_bangla_demo_ring( $img, $u( 50 ), $u( 46 ), $u( 62 ), $u( 5 ), $body, $paper );
			for ( $i = 0; $i < 3; $i++ ) {
				imagefilledarc( $img, $u( 50 ), $u( 46 ), $u( 48 ), $u( 48 ), $i * 120, $i * 120 + 72, $accent, IMG_ARC_PIE );
			}
			imagefilledellipse( $img, $u( 50 ), $u( 46 ), $u( 12 ), $u( 12 ), $body );
			simple_bangla_demo_rrect( $img, $u( 44 ), $u( 78 ), $u( 56 ), $u( 88 ), $u( 3 ), $body );
			break;

		case 'ringlight':
			simple_bangla_demo_ring( $img, $u( 50 ), $u( 42 ), $u( 64 ), $u( 10 ), $body, $paper );
			simple_bangla_demo_rrect( $img, $u( 47 ), $u( 72 ), $u( 53 ), $u( 88 ), $u( 2 ), $accent );
			simple_bangla_demo_rrect( $img, $u( 34 ), $u( 86 ), $u( 66 ), $u( 92 ), $u( 3 ), $accent );
			break;

		case 'softbox':
			imagefilledpolygon(
				$img,
				array( $u( 22 ), $u( 24 ), $u( 78 ), $u( 24 ), $u( 66 ), $u( 66 ), $u( 34 ), $u( 66 ) ),
				$body
			);
			simple_bangla_demo_rrect( $img, $u( 47 ), $u( 66 ), $u( 53 ), $u( 88 ), $u( 2 ), $accent );
			simple_bangla_demo_rrect( $img, $u( 34 ), $u( 86 ), $u( 66 ), $u( 92 ), $u( 3 ), $accent );
			break;

		case 'tripod':
			simple_bangla_demo_rrect( $img, $u( 40 ), $u( 14 ), $u( 60 ), $u( 30 ), $u( 4 ), $body );
			imagesetthickness( $img, max( 2, $u( 4 ) ) );
			imageline( $img, $u( 50 ), $u( 30 ), $u( 24 ), $u( 86 ), $accent );
			imageline( $img, $u( 50 ), $u( 30 ), $u( 76 ), $u( 86 ), $accent );
			imageline( $img, $u( 50 ), $u( 30 ), $u( 50 ), $u( 80 ), $accent );
			imagesetthickness( $img, 1 );
			break;

		case 'phone':
			simple_bangla_demo_rrect( $img, $u( 34 ), $u( 12 ), $u( 66 ), $u( 76 ), $u( 7 ), $body );
			simple_bangla_demo_rrect( $img, $u( 39 ), $u( 19 ), $u( 61 ), $u( 68 ), $u( 3 ), $paper );
			simple_bangla_demo_rrect( $img, $u( 38 ), $u( 76 ), $u( 62 ), $u( 90 ), $u( 5 ), $accent );
			break;

		case 'camera':
			simple_bangla_demo_rrect( $img, $u( 20 ), $u( 32 ), $u( 80 ), $u( 76 ), $u( 8 ), $body );
			simple_bangla_demo_rrect( $img, $u( 36 ), $u( 24 ), $u( 56 ), $u( 34 ), $u( 3 ), $body );
			simple_bangla_demo_ring( $img, $u( 50 ), $u( 55 ), $u( 30 ), $u( 6 ), $accent, $paper );
			imagefilledellipse( $img, $u( 70 ), $u( 40 ), $u( 6 ), $u( 6 ), $accent );
			break;

		case 'memcard':
			imagefilledpolygon(
				$img,
				array( $u( 34 ), $u( 20 ), $u( 60 ), $u( 20 ), $u( 68 ), $u( 30 ), $u( 68 ), $u( 80 ), $u( 34 ), $u( 80 ) ),
				$body
			);
			for ( $i = 0; $i < 4; $i++ ) {
				imagefilledrectangle( $img, $u( 39 + $i * 7 ), $u( 34 ), $u( 43 + $i * 7 ), $u( 50 ), $paper );
			}
			break;

		case 'bag':
			simple_bangla_demo_rrect( $img, $u( 22 ), $u( 38 ), $u( 78 ), $u( 82 ), $u( 8 ), $body );
			imagesetthickness( $img, max( 2, $u( 5 ) ) );
			imagearc( $img, $u( 50 ), $u( 40 ), $u( 34 ), $u( 34 ), 180, 360, $accent );
			imagesetthickness( $img, 1 );
			simple_bangla_demo_rrect( $img, $u( 22 ), $u( 54 ), $u( 78 ), $u( 60 ), 0, $accent );
			break;

		case 'keyboard':
			simple_bangla_demo_rrect( $img, $u( 14 ), $u( 34 ), $u( 86 ), $u( 68 ), $u( 6 ), $body );
			for ( $row = 0; $row < 3; $row++ ) {
				for ( $col = 0; $col < 8; $col++ ) {
					imagefilledrectangle( $img, $u( 20 + $col * 8 ), $u( 40 + $row * 8 ), $u( 25 + $col * 8 ), $u( 45 + $row * 8 ), $paper );
				}
			}
			break;

		case 'mouse':
			simple_bangla_demo_rrect( $img, $u( 34 ), $u( 20 ), $u( 66 ), $u( 82 ), $u( 16 ), $body );
			imagefilledrectangle( $img, $u( 49 ), $u( 22 ), $u( 51 ), $u( 44 ), $paper );
			simple_bangla_demo_rrect( $img, $u( 47 ), $u( 34 ), $u( 53 ), $u( 46 ), $u( 3 ), $accent );
			break;

		case 'hub':
			simple_bangla_demo_rrect( $img, $u( 18 ), $u( 40 ), $u( 82 ), $u( 62 ), $u( 6 ), $body );
			for ( $i = 0; $i < 4; $i++ ) {
				imagefilledrectangle( $img, $u( 26 + $i * 14 ), $u( 46 ), $u( 34 + $i * 14 ), $u( 52 ), $paper );
			}
			simple_bangla_demo_rrect( $img, $u( 46 ), $u( 20 ), $u( 54 ), $u( 40 ), $u( 2 ), $accent );
			break;

		case 'trimmer':
			simple_bangla_demo_rrect( $img, $u( 42 ), $u( 26 ), $u( 58 ), $u( 84 ), $u( 6 ), $body );
			simple_bangla_demo_rrect( $img, $u( 38 ), $u( 14 ), $u( 62 ), $u( 26 ), $u( 3 ), $accent );
			for ( $i = 0; $i < 5; $i++ ) {
				imagefilledrectangle( $img, $u( 39 + $i * 5 ), $u( 10 ), $u( 41 + $i * 5 ), $u( 16 ), $accent );
			}
			simple_bangla_demo_rrect( $img, $u( 45 ), $u( 46 ), $u( 55 ), $u( 52 ), $u( 2 ), $paper );
			break;

		case 'dryer':
			simple_bangla_demo_rrect( $img, $u( 24 ), $u( 24 ), $u( 70 ), $u( 52 ), $u( 14 ), $body );
			simple_bangla_demo_rrect( $img, $u( 16 ), $u( 28 ), $u( 26 ), $u( 48 ), $u( 4 ), $accent );
			imagefilledpolygon(
				$img,
				array( $u( 46 ), $u( 52 ), $u( 62 ), $u( 52 ), $u( 58 ), $u( 88 ), $u( 44 ), $u( 88 ) ),
				$accent
			);
			break;

		default:
			simple_bangla_demo_rrect( $img, $u( 26 ), $u( 26 ), $u( 74 ), $u( 74 ), $u( 12 ), $body );
			imagefilledellipse( $img, $u( 50 ), $u( 50 ), $u( 26 ), $u( 26 ), $accent );
			break;
	}
}

/**
 * Draw a placeholder product image and add it to the media library.
 *
 * Each category gets a recognisable silhouette — a watch for watches, a ring light for
 * lighting — on a soft tinted field, with the hue derived from the product name so no two
 * items in a row look identical. It is obviously artwork rather than photography, which is
 * the point: it fills the layout honestly until real photos replace it.
 *
 * @param string $label    Product name.
 * @param string $filename Slug used for the file name.
 * @param int    $size     Square edge in pixels.
 * @param string $shape    Shape key; falls back to a neutral device.
 * @return int Attachment ID, or 0 on failure.
 */
function simple_bangla_demo_image( $label, $filename, $size = 800, $shape = 'default' ) {

	if ( ! simple_bangla_can_draw() ) {
		return 0;
	}

	$image = imagecreatetruecolor( $size, $size );
	$hue   = simple_bangla_demo_hue( $label );

	$paper  = simple_bangla_demo_color( $image, $hue, 0.30, 0.95 );
	$panel  = imagecolorallocate( $image, 255, 255, 255 );
	$body   = simple_bangla_demo_color( $image, $hue, 0.34, 0.52 );
	$accent = simple_bangla_demo_color( $image, $hue, 0.45, 0.68 );

	imagefilledrectangle( $image, 0, 0, $size, $size, $paper );

	// A white panel inset from the edge, so the silhouette reads as a product on a backdrop
	// rather than a flat sticker filling the tile.
	simple_bangla_demo_rrect( $image, (int) ( $size * 0.08 ), (int) ( $size * 0.08 ), (int) ( $size * 0.92 ), (int) ( $size * 0.92 ), (int) ( $size * 0.06 ), $panel );

	simple_bangla_demo_draw( $image, $shape, $size, $body, $accent, $panel );

	return simple_bangla_demo_store_image( $image, $filename, $label );
}

/**
 * Draw a wide promotional banner.
 *
 * @param string $label    Banner caption.
 * @param string $filename File slug.
 * @param int    $width    Width in pixels.
 * @param int    $height   Height in pixels.
 * @return int Attachment ID, or 0 on failure.
 */
function simple_bangla_demo_banner( $label, $filename, $width, $height ) {

	if ( ! simple_bangla_can_draw() ) {
		return 0;
	}

	$image = imagecreatetruecolor( $width, $height );
	$hue   = simple_bangla_demo_hue( $label );

	$background = simple_bangla_demo_color( $image, $hue, 0.40, 0.88 );
	$accent     = simple_bangla_demo_color( $image, $hue, 0.50, 0.55 );

	imagefilledrectangle( $image, 0, 0, $width, $height, $background );

	// Two overlapping discs off the right edge — a plain, obviously-placeholder composition.
	imagefilledellipse( $image, (int) ( $width * 0.82 ), (int) ( $height * 0.5 ), $height, $height, $accent );
	imagefilledellipse( $image, (int) ( $width * 0.97 ), (int) ( $height * 0.3 ), (int) ( $height * 0.8 ), (int) ( $height * 0.8 ), $background );

	$strip   = imagecreatetruecolor( 120, 20 );
	$stripbg = imagecolorallocate( $strip, 255, 255, 255 );
	$striptx = imagecolorallocate( $strip, 34, 34, 34 );

	imagefilledrectangle( $strip, 0, 0, 120, 20, $stripbg );
	imagecolortransparent( $strip, $stripbg );
	imagestring( $strip, 5, 0, 2, substr( $label, 0, 20 ), $striptx );

	$target = (int) ( $width * 0.42 );
	imagecopyresampled( $image, $strip, (int) ( $width * 0.07 ), (int) ( $height * 0.4 ), 0, 0, $target, (int) ( $target / 6 ), 120, 20 );
	imagedestroy( $strip );

	return simple_bangla_demo_store_image( $image, $filename, $label );
}

/**
 * Write a GD image into the uploads directory and register it as an attachment.
 *
 * @param resource|GdImage $image    Image to write. Destroyed by this function.
 * @param string           $filename Slug for the file name.
 * @param string           $label    Alt text and title.
 * @return int Attachment ID, or 0 on failure.
 */
function simple_bangla_demo_store_image( $image, $filename, $label ) {

	ob_start();
	imagepng( $image, null, 6 );
	$binary = ob_get_clean();

	imagedestroy( $image );

	if ( ! $binary ) {
		return 0;
	}

	$upload = wp_upload_bits( sanitize_file_name( $filename . '.png' ), null, $binary );

	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => $label,
			'post_status'    => 'inherit',
		),
		$upload['file']
	);

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';

	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $label );
	update_post_meta( $attachment_id, SIMPLE_BANGLA_DEMO_MARK, 1 );

	return (int) $attachment_id;
}

/* ------------------------------------------------------------------ *
 * The import
 * ------------------------------------------------------------------ */

/**
 * Create categories, products, menus and banners.
 *
 * Safe to run twice: anything already present by slug is reused rather than duplicated.
 *
 * @return array{categories:int,products:int,images:int} Counts of what was created.
 */
function simple_bangla_run_demo_import() {

	$created = array(
		'categories' => 0,
		'products'   => 0,
		'images'     => 0,
	);

	if ( ! class_exists( 'WooCommerce' ) ) {
		return $created;
	}

	// Image generation for a few dozen products outruns the default limit on slow hosts.
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Disabled on some hosts.
	}

	/*
	 * A demo catalogue priced in Taka should display in Taka. Only touched while the store is
	 * still on WooCommerce's untouched default, so a deliberate currency choice is never
	 * overwritten — the theme itself never writes this option.
	 */
	if ( 'USD' === get_option( 'woocommerce_currency' ) && ! get_option( SIMPLE_BANGLA_DEMO_FLAG ) ) {
		update_option( 'woocommerce_currency', 'BDT' );
		update_option( 'woocommerce_default_country', 'BD:BD-13' );

		// WooCommerce's Taka symbol already ends in a non-breaking space, so the
		// "left with space" position would render ৳ two spaces from the number.
		update_option( 'woocommerce_currency_pos', 'left' );
	}

	/* -- Categories -- */

	$term_ids = array();

	foreach ( simple_bangla_demo_categories() as $slug => $definition ) {

		list( $name, $parent_slug ) = $definition;

		$existing = get_term_by( 'slug', $slug, 'product_cat' );

		if ( $existing ) {
			$term_ids[ $slug ] = (int) $existing->term_id;
			continue;
		}

		$result = wp_insert_term(
			$name,
			'product_cat',
			array(
				'slug'   => $slug,
				'parent' => $parent_slug && isset( $term_ids[ $parent_slug ] ) ? $term_ids[ $parent_slug ] : 0,
			)
		);

		if ( is_wp_error( $result ) ) {
			continue;
		}

		$term_ids[ $slug ] = (int) $result['term_id'];
		$created['categories']++;

		update_term_meta( $term_ids[ $slug ], SIMPLE_BANGLA_DEMO_MARK, 1 );

		// Only top-level categories appear as homepage circles, so only they need artwork.
		if ( ! $parent_slug ) {

			$shapes = simple_bangla_demo_shapes();
			$thumb  = simple_bangla_demo_image(
				$name,
				'sb-cat-' . $slug,
				300,
				isset( $shapes[ $slug ] ) ? $shapes[ $slug ] : 'default'
			);

			if ( $thumb ) {
				update_term_meta( $term_ids[ $slug ], 'thumbnail_id', $thumb );
				$created['images']++;
			}
		}
	}

	/* -- Products -- */

	foreach ( simple_bangla_demo_products() as $definition ) {

		list( $name, $category, $regular, $sale ) = $definition;

		$slug = sanitize_title( $name );

		if ( get_page_by_path( $slug, OBJECT, 'product' ) ) {
			continue;
		}

		$product = new WC_Product_Simple();

		$product->set_name( $name );
		$product->set_slug( $slug );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_regular_price( (string) $regular );

		if ( $sale ) {
			$product->set_sale_price( (string) $sale );
		}

		$product->set_short_description(
			sprintf(
				/* translators: %s: product name. */
				__( '%s — demo product created by the Simple Bangla importer. Replace this copy with your own description.', 'simple-bangla' ),
				$name
			)
		);

		$product->set_description(
			__( 'This is placeholder copy so the layout can be judged with realistic text in it. Every product created by the importer carries the same description; edit or delete them once your real catalogue is in.', 'simple-bangla' )
		);

		$product->set_manage_stock( false );
		$product->set_stock_status( 'instock' );
		$product->set_sku( 'SB-' . strtoupper( substr( md5( $slug ), 0, 6 ) ) );

		$categories = array();

		if ( isset( $term_ids[ $category ] ) ) {
			$categories[] = $term_ids[ $category ];
		}

		// Roughly every third product also lands in Best Selling, so that row is populated.
		if ( isset( $term_ids['best-selling'] ) && 0 === crc32( $slug ) % 3 ) {
			$categories[] = $term_ids['best-selling'];
		}

		if ( $categories ) {
			$product->set_category_ids( array_unique( $categories ) );
		}

		$product_id = $product->save();

		if ( ! $product_id ) {
			continue;
		}

		update_post_meta( $product_id, SIMPLE_BANGLA_DEMO_MARK, 1 );
		$created['products']++;

		$shapes   = simple_bangla_demo_shapes();
		$image_id = simple_bangla_demo_image(
			$name,
			'sb-product-' . $slug,
			800,
			isset( $shapes[ $category ] ) ? $shapes[ $category ] : 'default'
		);

		if ( $image_id ) {
			set_post_thumbnail( $product_id, $image_id );
			$created['images']++;
		}
	}

	/*
	 * Best Selling ends up empty when crc32 never hits the modulo — belt and braces so the
	 * homepage's first row always has stock in it.
	 */
	if ( isset( $term_ids['best-selling'] ) ) {
		simple_bangla_demo_seed_best_selling( $term_ids['best-selling'] );
	}

	simple_bangla_demo_checkout_setup();
	simple_bangla_demo_hero();
	simple_bangla_demo_banners();
	simple_bangla_demo_menus( $term_ids );
	simple_bangla_demo_front_page();

	delete_transient( 'simple_bangla_price_bounds' );
	update_option( SIMPLE_BANGLA_DEMO_FLAG, time() );

	return $created;
}

/**
 * Make sure the Best Selling category is not empty.
 *
 * @param int $term_id Best Selling term ID.
 */
function simple_bangla_demo_seed_best_selling( $term_id ) {

	$count = (int) get_term( $term_id )->count;

	if ( $count >= 8 ) {
		return;
	}

	$products = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'orderby'        => 'rand',
			'fields'         => 'ids',
		)
	);

	foreach ( $products as $product_id ) {
		wp_set_object_terms( $product_id, array( (int) $term_id ), 'product_cat', true );
	}
}

/**
 * Make the store able to actually take an order.
 *
 * A fresh WooCommerce install has no payment method enabled and no shipping rates, so the
 * checkout renders but the order can never be completed. It also ships the Cart and Checkout
 * pages as blocks, which render entirely client-side and cannot be templated by a classic
 * theme — so the theme's checkout design would never appear.
 *
 * All three are fixed here rather than in the theme itself: they are store configuration, and
 * a theme that rewrites a live store's payment settings on activation would be a menace.
 */
function simple_bangla_demo_checkout_setup() {

	/* -- Cash on delivery, which is how most orders in Bangladesh are actually paid -- */

	$cod = (array) get_option( 'woocommerce_cod_settings', array() );

	if ( empty( $cod['enabled'] ) || 'yes' !== $cod['enabled'] ) {

		$cod['enabled']     = 'yes';
		$cod['title']       = __( 'Cash on delivery', 'simple-bangla' );
		$cod['description'] = __( 'Pay with cash when your order arrives.', 'simple-bangla' );

		update_option( 'woocommerce_cod_settings', $cod );
	}

	/* -- Bangladesh only -- */

	/*
	 * Not merely a policy setting. With exactly one country allowed, WooCommerce renders the
	 * checkout's country field as a fixed value beside a hidden input instead of a select
	 * carrying all 250 countries — which is what the field should look like on a store that
	 * ships to one, and about 15 KB less markup on the page.
	 *
	 * Only ever applied to a store still on WooCommerce's default, so an owner who has opened
	 * up other countries is never rolled back.
	 */
	if ( 'all' === get_option( 'woocommerce_allowed_countries', 'all' ) ) {

		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'BD' ) );

		// Empty means "ship to whatever I sell to", so this stays true if the list is widened.
		update_option( 'woocommerce_ship_to_countries', '' );
	}

	/* -- Classic cart and checkout, so woocommerce/ template overrides apply -- */

	$shortcodes = array(
		'woocommerce_cart_page_id'     => '[woocommerce_cart]',
		'woocommerce_checkout_page_id' => '[woocommerce_checkout]',
	);

	foreach ( $shortcodes as $option => $shortcode ) {

		$page_id = (int) get_option( $option );

		if ( ! $page_id ) {
			continue;
		}

		$page = get_post( $page_id );

		// Only swap a page that still holds WooCommerce's own block markup. A page the store
		// owner has written themselves is left alone.
		if ( ! $page || false === strpos( $page->post_content, 'wp:woocommerce/' ) ) {
			continue;
		}

		wp_update_post(
			array(
				'ID'           => $page_id,
				'post_content' => $shortcode,
			)
		);
	}

	/* -- Shipping -- */

	if ( ! class_exists( 'WC_Shipping_Zone' ) ) {
		return;
	}

	/*
	 * Both rates live in the catch-all zone so the customer picks between them, rather than
	 * the zone being matched from a state field. That is how the reference store does it, and
	 * it is what lets the checkout form stay as short as it does — no city, state or postcode
	 * is needed for the right delivery charge to apply.
	 */
	// A one-shot flag rather than "has any method", so re-running the importer after the rates
	// were changed here does not leave a half-configured zone behind.
	if ( get_option( 'simple_bangla_shipping_ready' ) ) {
		return;
	}

	// Clear out anything a previous run left, including named zones, so the two rates below
	// are the only ones a customer is offered.
	foreach ( WC_Shipping_Zones::get_zones() as $existing ) {
		$old = new WC_Shipping_Zone( $existing['zone_id'] );
		$old->delete();
	}

	$zone = new WC_Shipping_Zone( 0 );

	foreach ( $zone->get_shipping_methods() as $method ) {
		$zone->delete_shipping_method( $method->instance_id );
	}

	$rates = array(
		array( __( 'ঢাকার ভেতরে', 'simple-bangla' ), '70' ),
		array( __( 'ঢাকার বাইরে', 'simple-bangla' ), '120' ),
	);

	foreach ( $rates as $rate ) {

		$instance = $zone->add_shipping_method( 'flat_rate' );

		if ( ! $instance ) {
			continue;
		}

		update_option(
			'woocommerce_flat_rate_' . $instance . '_settings',
			array(
				'title'      => $rate[0],
				'cost'       => $rate[1],
				'tax_status' => 'none',
			)
		);
	}

	update_option( 'simple_bangla_shipping_ready', time() );
}

/**
 * Generate and assign the hero carousel slides.
 */
function simple_bangla_demo_hero() {

	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

	$slides = array(
		'Student Combo Offer',
		'Special Offer — 30% Off',
		'New Arrivals This Week',
	);

	foreach ( $slides as $index => $label ) {

		$slot = $index + 1;

		if ( get_theme_mod( 'simple_bangla_hero_' . $slot . '_image' ) ) {
			continue;
		}

		// 16:7, matching the aspect ratio the hero renders at on a desktop.
		$image_id = simple_bangla_demo_banner( $label, 'sb-hero-' . $slot, 1400, 612 );

		if ( ! $image_id ) {
			continue;
		}

		set_theme_mod( 'simple_bangla_hero_' . $slot . '_image', $image_id );
		set_theme_mod( 'simple_bangla_hero_' . $slot . '_link', $shop );
	}
}

/**
 * Generate and assign the two homepage banner pairs.
 */
function simple_bangla_demo_banners() {

	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

	$banners = array(
		array( 1, 'small', 'New Arrivals', 600, 280 ),
		array( 1, 'wide', 'Up to 40% Off Audio', 1024, 512 ),
		array( 2, 'small', 'Lighting Deals', 600, 280 ),
		array( 2, 'wide', 'Free Delivery in Dhaka', 1024, 390 ),
	);

	foreach ( $banners as $banner ) {

		list( $pair, $slot, $label, $width, $height ) = $banner;

		$setting = 'simple_bangla_home_banner_' . $pair . '_' . $slot;

		if ( get_theme_mod( $setting . '_image' ) ) {
			continue;
		}

		$image_id = simple_bangla_demo_banner( $label, 'sb-banner-' . $pair . '-' . $slot, $width, $height );

		if ( ! $image_id ) {
			continue;
		}

		set_theme_mod( $setting . '_image', $image_id );
		set_theme_mod( $setting . '_link', $shop );
	}
}

/**
 * Build the primary and footer menus and assign them to their locations.
 *
 * @param array<string,int> $term_ids Category slug => term ID.
 */
function simple_bangla_demo_menus( $term_ids ) {

	$locations = get_theme_mod( 'nav_menu_locations', array() );

	/* -- Primary: the category tree, three levels deep -- */

	if ( empty( $locations['primary'] ) || ! wp_get_nav_menu_object( $locations['primary'] ) ) {

		$menu_id = wp_create_nav_menu( __( 'Primary Menu', 'simple-bangla' ) );

		if ( ! is_wp_error( $menu_id ) ) {

			$tree = simple_bangla_demo_categories();
			$item_ids = array();

			foreach ( $tree as $slug => $definition ) {

				list( , $parent_slug ) = $definition;

				if ( ! isset( $term_ids[ $slug ] ) ) {
					continue;
				}

				// Three levels is the walker's limit; anything deeper would be dropped anyway.
				if ( $parent_slug && isset( $tree[ $parent_slug ][1] ) && $tree[ $parent_slug ][1] ) {
					$grandparent = $tree[ $parent_slug ][1];

					if ( $grandparent && isset( $tree[ $grandparent ][1] ) && $tree[ $grandparent ][1] ) {
						continue;
					}
				}

				$item_ids[ $slug ] = wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'     => $definition[0],
						'menu-item-object'    => 'product_cat',
						'menu-item-object-id' => $term_ids[ $slug ],
						'menu-item-type'      => 'taxonomy',
						'menu-item-status'    => 'publish',
						'menu-item-parent-id' => $parent_slug && isset( $item_ids[ $parent_slug ] ) ? $item_ids[ $parent_slug ] : 0,
					)
				);
			}

			$locations['primary'] = $menu_id;
		}
	}

	/* -- Footer columns -- */

	$footer_menus = array(
		'footer-1' => array(
			'name'  => __( 'Footer — Company', 'simple-bangla' ),
			'pages' => array( 'about-us', 'privacy-policy', 'refund_returns', 'terms-and-condition' ),
		),
		'footer-2' => array(
			'name'  => __( 'Footer — Helps', 'simple-bangla' ),
			'pages' => array( 'tutorials', 'warranty-policy', 'special-deals' ),
		),
		'footer-3' => array(
			'name'  => __( 'Footer — Customer Service', 'simple-bangla' ),
			'pages' => array( 'contact-us', 'register', 'login' ),
		),
	);

	foreach ( $footer_menus as $location => $definition ) {

		if ( ! empty( $locations[ $location ] ) && wp_get_nav_menu_object( $locations[ $location ] ) ) {
			continue;
		}

		$menu_id = wp_create_nav_menu( $definition['name'] );

		if ( is_wp_error( $menu_id ) ) {
			continue;
		}

		foreach ( $definition['pages'] as $slug ) {

			$page = get_page_by_path( $slug );

			if ( ! $page ) {
				continue;
			}

			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => get_the_title( $page ),
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $page->ID,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}

		$locations[ $location ] = $menu_id;
	}

	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Point the site at a static front page so front-page.php is what visitors land on.
 */
function simple_bangla_demo_front_page() {

	if ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) ) {
		return;
	}

	$home = get_page_by_path( 'home' );

	if ( ! $home ) {

		$home_id = wp_insert_post(
			array(
				'post_title'  => __( 'Home', 'simple-bangla' ),
				'post_name'   => 'home',
				'post_status' => 'publish',
				'post_type'   => 'page',
			)
		);

		if ( is_wp_error( $home_id ) || ! $home_id ) {
			return;
		}

		update_post_meta( $home_id, SIMPLE_BANGLA_DEMO_MARK, 1 );

	} else {
		$home_id = $home->ID;
	}

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home_id );
}

/* ------------------------------------------------------------------ *
 * Admin screen
 * ------------------------------------------------------------------ */

/**
 * Register the importer under Appearance.
 */
function simple_bangla_demo_menu_page() {

	add_theme_page(
		__( 'Simple Bangla Demo Content', 'simple-bangla' ),
		__( 'Demo Content', 'simple-bangla' ),
		'edit_theme_options',
		'simple-bangla-demo',
		'simple_bangla_demo_screen'
	);
}
add_action( 'admin_menu', 'simple_bangla_demo_menu_page' );

/**
 * Render the importer screen and handle its form.
 */
function simple_bangla_demo_screen() {

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'simple-bangla' ) );
	}

	$result = null;

	if ( isset( $_POST['simple_bangla_demo_import'] ) ) {

		check_admin_referer( 'simple_bangla_demo' );

		$result = simple_bangla_run_demo_import();
	}

	$imported = (int) get_option( SIMPLE_BANGLA_DEMO_FLAG, 0 );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Demo Content', 'simple-bangla' ); ?></h1>

		<?php if ( ! class_exists( 'WooCommerce' ) ) : ?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'WooCommerce needs to be active before the catalogue can be imported.', 'simple-bangla' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! simple_bangla_can_draw() ) : ?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'PHP has no GD image support here, so products will be created without placeholder images.', 'simple-bangla' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( is_array( $result ) ) : ?>
			<div class="notice notice-success">
				<p>
					<?php
					printf(
						/* translators: 1: categories created, 2: products created, 3: images created. */
						esc_html__( 'Done. Created %1$d categories, %2$d products and %3$d images.', 'simple-bangla' ),
						(int) $result['categories'],
						(int) $result['products'],
						(int) $result['images']
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<p>
			<?php esc_html_e( 'Fills the store with a demo catalogue so the design can be judged with real content in it: a three-level category tree, a few dozen products with sale prices, navigation menus, homepage banners and generated placeholder images.', 'simple-bangla' ); ?>
		</p>

		<p>
			<strong><?php esc_html_e( 'Nothing is overwritten.', 'simple-bangla' ); ?></strong>
			<?php esc_html_e( 'Anything that already exists under the same slug is left exactly as it is, so running this twice is harmless.', 'simple-bangla' ); ?>
		</p>

		<?php if ( $imported ) : ?>
			<p class="description">
				<?php
				printf(
					/* translators: %s: human-readable date and time. */
					esc_html__( 'Last run: %s', 'simple-bangla' ),
					esc_html( wp_date( 'j F Y, H:i', $imported ) )
				);
				?>
			</p>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'simple_bangla_demo' ); ?>
			<p>
				<button type="submit" name="simple_bangla_demo_import" value="1" class="button button-primary">
					<?php esc_html_e( 'Import demo content', 'simple-bangla' ); ?>
				</button>
			</p>
		</form>
	</div>
	<?php
}

/**
 * Nudge the store owner towards the importer on a catalogue-less install.
 */
function simple_bangla_demo_notice() {

	if ( ! current_user_can( 'edit_theme_options' ) || get_option( SIMPLE_BANGLA_DEMO_FLAG ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( $screen && 'appearance_page_simple-bangla-demo' === $screen->id ) {
		return;
	}

	printf(
		'<div class="notice notice-info is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
		esc_html__( 'Simple Bangla can fill your store with demo products so you can see the design working.', 'simple-bangla' ),
		esc_url( admin_url( 'themes.php?page=simple-bangla-demo' ) ),
		esc_html__( 'Import demo content', 'simple-bangla' )
	);
}
add_action( 'admin_notices', 'simple_bangla_demo_notice' );
