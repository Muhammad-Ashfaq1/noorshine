<?php
/**
 * Run with wp eval-file tests/wordpress-smoke.php on a disposable local WordPress installation.
 */
if ( wp_get_environment_type() !== 'local' || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	throw new RuntimeException( 'This fixture requires an isolated loopback WordPress installation marked local.' );
}

function noorshine_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

noorshine_test_assert( wp_get_theme()->get_stylesheet() === 'noorshine', 'The NoorShine theme must be active.' );
noorshine_test_assert( class_exists( 'WooCommerce' ) && class_exists( 'NoorShine_Product_Designer' ), 'WooCommerce and Product Designer must be active.' );
wp_set_current_user( 1 );
WC()->initialize_session();
WC()->initialize_cart();
update_option( 'woocommerce_currency', 'PKR' );
update_option( 'woocommerce_default_country', 'PK' );
update_option( 'woocommerce_calc_taxes', 'no' );
$home = wp_insert_post( array( 'post_title' => 'Home', 'post_status' => 'publish', 'post_type' => 'page' ) );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home );

$category = wp_insert_term( 'Islamic Wall Art', 'product_cat', array( 'slug' => 'islamic-wall-art' ) );
noorshine_test_assert( ! is_wp_error( $category ), 'A fresh QA database is required.' );
$draft = new WC_Product_Simple();
$draft->set_name( 'Draft test fixture' );
$draft->set_status( 'draft' );
$draft->set_catalog_visibility( 'hidden' );
$draft->save();
noorshine_test_assert( ! $draft->is_purchasable(), 'Unpriced drafts must be unavailable to purchase.' );

$base = new WC_Product_Variable();
$base->set_name( 'QA Designer Fixture' );
$base->set_status( 'publish' );
$base->set_category_ids( array( $category['term_id'] ) );
$base->update_meta_data( '_noorshine_designer_enabled', 'yes' );
$base->update_meta_data( '_noorshine_production_status', 'approved' );
$base->update_meta_data( '_noorshine_seo_title', 'Designer Test Fixture | NoorShine' );
$base->update_meta_data( '_noorshine_meta_description', 'A local-only test fixture for the NoorShine customizer.' );
$attributes = array();
foreach ( array( 'Material' => 'MDF', 'Size' => '12 inches', 'Finish' => 'Matte Black' ) as $name => $option ) {
	$attribute = new WC_Product_Attribute();
	$attribute->set_name( $name );
	$attribute->set_options( array( $option ) );
	$attribute->set_visible( true );
	$attribute->set_variation( true );
	$attributes[] = $attribute;
}
$base->set_attributes( $attributes );
$base_id = $base->save();
$attributes = array( 'material' => 'MDF', 'size' => '12 inches', 'finish' => 'Matte Black' );
$variation = new WC_Product_Variation();
$variation->set_parent_id( $base_id );
$variation->set_regular_price( '1200' );
$variation->set_virtual( true );
$variation->set_attributes( $attributes );
$variation_id = $variation->save();
WC_Product_Variable::sync( $base_id );

$settings = NoorShine_Product_Designer::defaults();
$settings['approved_texts'][] = array( 'label' => 'Approved Arabic fixture', 'value' => 'نور', 'verified' => true );
$settings['pricing_approved'] = true;
$settings['pricing_rules'] = array( array( 'size' => '12 inches', 'material' => 'MDF', 'adjustment' => 150 ) );
update_option( NoorShine_Product_Designer::OPTION, $settings );

$_POST = array(
	'nspd_active' => '1',
	'nspd_text_option' => 'نور',
	'nspd_style' => 'Classic Arch',
	'nspd_template' => 'arch',
	'nspd_material' => 'MDF',
	'nspd_finish' => 'Matte Black',
	'nspd_size' => '12 inches',
);
$variation_attributes = wc_get_product( $variation_id )->get_variation_attributes();
noorshine_test_assert( ! NoorShine_Product_Designer::validate_add_to_cart( true, $base_id, 1, $variation_id, $variation_attributes ), 'A missing nonce must be rejected.' );
wc_clear_notices();
$_POST['nspd_nonce'] = wp_create_nonce( NoorShine_Product_Designer::NONCE );
$_POST['nspd_size'] = '24 inches';
noorshine_test_assert( ! NoorShine_Product_Designer::validate_add_to_cart( true, $base_id, 1, $variation_id, $variation_attributes ), 'A mismatched variation must be rejected.' );
wc_clear_notices();
$_POST['nspd_size'] = '12 inches';
noorshine_test_assert( NoorShine_Product_Designer::validate_add_to_cart( true, $base_id, 1, $variation_id, $variation_attributes ), 'A verified Arabic choice and matching variation must pass.' );
$key = WC()->cart->add_to_cart( $base_id, 1, $variation_id, $variation_attributes );
noorshine_test_assert( $key, 'WooCommerce should accept the valid customized variation.' );
WC()->cart->calculate_totals();
WC()->cart->calculate_totals();
$item = WC()->cart->get_cart_item( $key );
noorshine_test_assert( 1350.0 === (float) $item['data']->get_price(), 'Repeated totals must apply the price adjustment once.' );
noorshine_test_assert( 'نور' === $item['noorshine_design']['text'], 'Arabic text must survive the cart lifecycle.' );
$order_id = WC()->checkout()->create_order( array(
	'billing_first_name' => 'QA',
	'billing_last_name'  => 'Fixture',
	'billing_country'    => 'PK',
	'billing_email'      => 'qa@example.invalid',
	'payment_method'    => '',
) );
noorshine_test_assert( is_int( $order_id ) && $order_id > 0, 'The local checkout must create an unpaid test order.' );
$order = wc_get_order( $order_id );
$line_items = $order->get_items();
$line = reset( $line_items );
noorshine_test_assert( $line instanceof WC_Order_Item_Product && ! $order->is_paid(), 'Expected an unpaid order with a product line.' );
noorshine_test_assert( 'manual-production-review-required' === $line->get_meta( 'NoorShine Review Status' ), 'Order line must retain the manual review requirement.' );
noorshine_test_assert( 'نور' === $line->get_meta( 'NoorShine Text' ), 'Order line must retain the selected text.' );

echo "Theme and plugin active. Draft safety, nonce, variation, Arabic text, cart totals and unpaid checkout order metadata passed.\n";
echo 'Fixture URL: ' . esc_url_raw( get_permalink( $base_id ) ) . "\n";
