<?php
/**
 * Per-plugin configuration + content seeding for the screenshot environment.
 * Run via wp eval-file. Not shipped.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { return; }

// Product ids from seed-ss.php (stable): 15 tote, 16 bottle, 17 tee, 18 mug, 19 socks.
$P = array( 'tote' => 15, 'bottle' => 16, 'tee' => 17, 'mug' => 18, 'socks' => 19 );

/* ---------- NOTICE: announcement bar ---------- */
update_option( 'notice_settings', array(
	'enabled'      => true,
	'message'      => 'Free shipping on orders over $50 — this weekend only.',
	'link_url'     => home_url( '/shop/' ),
	'link_label'   => 'Shop now',
	'link_new_tab' => false,
	'bg_color'     => '#1f2933',
	'text_color'   => '#ffffff',
	'link_color'   => '#ffd166',
	'dismissible'  => true,
	'dismiss_days' => 7,
) );
echo "notice configured\n";

/* ---------- TABBY: reusable tabs ---------- */
update_option( 'tabby_settings', array(
	'enabled'     => true,
	'global_tabs' => array(
		array(
			'id'      => 'shipping-returns',
			'title'   => 'Shipping & Returns',
			'content' => '<p>Free standard shipping on orders over $50. Most orders ship within 1–2 business days.</p><p>Not the right fit? Send it back within <strong>30 days</strong> for a full refund — no questions asked.</p>',
			'enabled' => true,
		),
		array(
			'id'      => 'care-guide',
			'title'   => 'Care Guide',
			'content' => '<ul><li>Wipe clean with a damp cloth.</li><li>Air dry away from direct heat.</li><li>Re-wax canvas yearly to keep it water-resistant.</li></ul>',
			'enabled' => true,
		),
	),
) );
echo "tabby configured\n";

/* ---------- ESTIMATE: quote mode = all + quote page ---------- */
update_option( 'estimate_settings', array(
	'enabled'     => true,
	'mode'        => 'all',
	'hide_price'  => true,
	'button_text' => '',
	'recipient'   => '',
) );
// Quote page with shortcode.
$quote_page = get_page_by_path( 'request-a-quote' );
if ( ! $quote_page ) {
	$qid = wp_insert_post( array(
		'post_title'   => 'Request a Quote',
		'post_name'    => 'request-a-quote',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '[estimate_quote]',
	) );
} else {
	$qid = $quote_page->ID;
}
update_option( 'estimate_quote_page_id', (int) $qid );
echo "estimate configured, quote_page=$qid\n";

/* ---------- SIZER: build a chart + assign to tee ---------- */
update_option( 'sizer_charts', array(
	array(
		'id'      => 'apparel',
		'name'    => 'Apparel Size Guide',
		'caption' => 'All measurements in inches. Garment laid flat.',
		'columns' => array( 'Size', 'Chest', 'Waist', 'Length' ),
		'rows'    => array(
			array( 'S',  '36–38', '30–32', '27' ),
			array( 'M',  '39–41', '33–35', '28' ),
			array( 'L',  '42–44', '36–38', '29' ),
			array( 'XL', '45–47', '39–41', '30' ),
		),
	),
), false );
update_post_meta( $P['tee'], '_sizer_chart_id', 'apparel' );
echo "sizer configured\n";

/* ---------- LOOKBOOK: post with image + hotspots + page ---------- */
// Build a wide lifestyle image for the lookbook stage.
$upload = wp_upload_dir();
$w = 1400; $h = 900;
$im = imagecreatetruecolor( $w, $h );
$bg = imagecolorallocate( $im, 0xcf, 0xc4, 0xb4 );
imagefilledrectangle( $im, 0, 0, $w, $h, $bg );
// a few soft blocks to suggest a styled flat-lay
$blocks = array(
	array( 120, 140, 520, 520, 0xd8, 0xce, 0xc0 ),
	array( 760, 120, 1240, 460, 0xc6, 0xcf, 0xc2 ),
	array( 560, 520, 1040, 820, 0xe2, 0xd6, 0xc8 ),
);
foreach ( $blocks as $b ) { $c = imagecolorallocate( $im, $b[4], $b[5], $b[6] ); imagefilledrectangle( $im, $b[0], $b[1], $b[2], $b[3], $c ); }
$tc = imagecolorallocate( $im, 0x44, 0x40, 0x3a );
imagestring( $im, 5, 60, 40, 'The Everyday Edit', $tc );
$file = $upload['path'] . '/lookbook-scene.png';
imagepng( $im, $file );
imagedestroy( $im );
$ft = wp_check_filetype( basename( $file ), null );
$att_id = wp_insert_attachment( array(
	'post_mime_type' => $ft['type'],
	'post_title'     => 'The Everyday Edit',
	'post_status'    => 'inherit',
), $file );
require_once ABSPATH . 'wp-admin/includes/image.php';
wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $file ) );

$lb = get_page_by_path( 'the-everyday-edit', OBJECT, 'lookbook' );
if ( ! $lb ) {
	$lb_id = wp_insert_post( array(
		'post_title'  => 'The Everyday Edit',
		'post_name'   => 'the-everyday-edit',
		'post_status' => 'publish',
		'post_type'   => 'lookbook',
	) );
} else { $lb_id = $lb->ID; }
set_post_thumbnail( $lb_id, $att_id );
update_post_meta( $lb_id, '_lookbook_hotspots', array(
	array( 'x' => 23.0, 'y' => 42.0, 'product_id' => $P['tote'] ),
	array( 'x' => 71.0, 'y' => 32.0, 'product_id' => $P['bottle'] ),
	array( 'x' => 57.0, 'y' => 74.0, 'product_id' => $P['mug'] ),
) );
echo "lookbook id=$lb_id image=$att_id\n";

// Page embedding the lookbook shortcode.
$lp = get_page_by_path( 'shop-the-look' );
if ( ! $lp ) {
	$lp_id = wp_insert_post( array(
		'post_title'   => 'Shop the Look',
		'post_name'    => 'shop-the-look',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '[lookbook id="' . (int) $lb_id . '"]',
	) );
} else {
	$lp_id = $lp->ID;
	wp_update_post( array( 'ID' => $lp_id, 'post_content' => '[lookbook id="' . (int) $lb_id . '"]' ) );
}
echo "lookbook_page=$lp_id\n";

echo "DONE_CONFIG\n";
