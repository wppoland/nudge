<?php
/**
 * One-off seeding script for screenshot environment. NOT shipped (in .distignore by name? add guard).
 * Run via: wp eval-file wp-content/plugins/nudge/seed-ss.php
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { return; }

function ss_make_image( $name, $w, $h, $bg, $label ) {
	$upload = wp_upload_dir();
	$im = imagecreatetruecolor( $w, $h );
	sscanf( $bg, "%02x%02x%02x", $r, $g, $b );
	$c = imagecolorallocate( $im, $r, $g, $b );
	imagefilledrectangle( $im, 0, 0, $w, $h, $c );
	// soft accent block
	$ac = imagecolorallocatealpha( $im, 255, 255, 255, 100 );
	imagefilledrectangle( $im, $w*0.12, $h*0.55, $w*0.88, $h*0.78, $ac );
	$tc = imagecolorallocate( $im, 40, 40, 40 );
	imagestring( $im, 5, 30, 24, $label, $tc );
	$file = $upload['path'] . '/' . sanitize_title( $name ) . '.png';
	imagepng( $im, $file );
	imagedestroy( $im );
	$ft = wp_check_filetype( basename( $file ), null );
	$att = array(
		'post_mime_type' => $ft['type'],
		'post_title'     => $name,
		'post_status'    => 'inherit',
	);
	$id = wp_insert_attachment( $att, $file );
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$meta = wp_generate_attachment_metadata( $id, $file );
	wp_update_attachment_metadata( $id, $meta );
	return $id;
}

function ss_product( $name, $price, $sale = null, $sale_end = null, $img = null, $cat = null, $desc = '' ) {
	$existing = get_page_by_title( $name, OBJECT, 'product' );
	if ( $existing ) { return $existing->ID; }
	$p = new WC_Product_Simple();
	$p->set_name( $name );
	$p->set_regular_price( (string) $price );
	if ( $sale !== null ) {
		$p->set_sale_price( (string) $sale );
		if ( $sale_end ) { $p->set_date_on_sale_to( $sale_end ); }
	}
	$p->set_status( 'publish' );
	$p->set_catalog_visibility( 'visible' );
	$p->set_short_description( $desc ?: 'Crafted for everyday use. Durable materials, considered details.' );
	$p->set_description( '<p>' . ( $desc ?: 'A dependable everyday piece from Harbor & Co. Made to last, easy to love.' ) . '</p><p>Free returns within 30 days.</p>' );
	$p->set_manage_stock( false );
	$p->set_stock_status( 'instock' );
	if ( $img ) { $p->set_image_id( $img ); }
	$id = $p->save();
	if ( $cat ) {
		$term = term_exists( $cat, 'product_cat' );
		if ( ! $term ) { $term = wp_insert_term( $cat, 'product_cat' ); }
		wp_set_object_terms( $id, array( (int) $term['term_id'] ), 'product_cat' );
	}
	return $id;
}

// Store address (for shipping calc / free shipping)
update_option( 'woocommerce_store_address', '14 Harbor Way' );
update_option( 'woocommerce_store_city', 'Portland' );
update_option( 'woocommerce_default_country', 'US:OR' );
update_option( 'woocommerce_store_postcode', '97201' );
update_option( 'woocommerce_currency', 'USD' );
update_option( 'woocommerce_calc_taxes', 'no' );
update_option( 'woocommerce_enable_guest_checkout', 'yes' );

// Images
$img_tote   = ss_make_image( 'Canvas Tote', 800, 800, 'd9cdbf', 'Canvas Tote' );
$img_bottle = ss_make_image( 'Steel Bottle', 800, 800, 'cdd6da', 'Steel Bottle' );
$img_tee    = ss_make_image( 'Everyday Tee', 800, 800, 'c9d2c4', 'Everyday Tee' );
$img_mug    = ss_make_image( 'Stoneware Mug', 800, 800, 'e0d4c8', 'Stoneware Mug' );
$img_socks  = ss_make_image( 'Wool Socks', 800, 800, 'd5cbe0', 'Wool Socks' );

// Sale end 6 days out for ticker
$sale_end = strtotime( '+6 days 8 hours' );

$ids = array();
$ids['tote']   = ss_product( 'Canvas Tote Bag', 38, null, null, $img_tote, 'Bags', 'Roomy waxed-canvas tote with leather handles.' );
$ids['bottle'] = ss_product( 'Insulated Steel Bottle', 29, 22, $sale_end, $img_bottle, 'Drinkware', 'Keeps drinks cold 24h, hot 12h. 600ml.' );
$ids['tee']    = ss_product( 'Everyday Cotton Tee', 24, null, null, $img_tee, 'Apparel', 'Heavyweight organic cotton crew neck.' );
$ids['mug']    = ss_product( 'Stoneware Mug', 16, null, null, $img_mug, 'Drinkware', 'Hand-glazed 350ml stoneware mug.' );
$ids['socks']  = ss_product( 'Merino Wool Socks', 18, null, null, $img_socks, 'Apparel', 'Soft merino blend, cushioned sole.' );

echo "PRODUCTS:\n";
foreach ( $ids as $k => $v ) { echo "$k=$v\n"; }

// Free shipping zone with $50 threshold (for nudge)
global $wpdb;
$zone = new WC_Shipping_Zone();
$zone->set_zone_name( 'United States' );
$zone->set_zone_order( 1 );
$zone->add_location( 'US', 'country' );
$zid = $zone->save();
$inst = $zone->add_shipping_method( 'free_shipping' );
$opt_key = 'woocommerce_free_shipping_' . $inst . '_settings';
update_option( $opt_key, array(
	'title'      => 'Free shipping',
	'requires'   => 'min_amount',
	'min_amount' => '50',
	'ignore_discounts' => 'no',
) );
// also a flat rate so cart has shipping options
$inst2 = $zone->add_shipping_method( 'flat_rate' );
update_option( 'woocommerce_flat_rate_' . $inst2 . '_settings', array(
	'title' => 'Standard',
	'cost'  => '6',
) );
echo "ZONE=$zid free_inst=$inst flat_inst=$inst2\n";

echo "DONE_SEED\n";
