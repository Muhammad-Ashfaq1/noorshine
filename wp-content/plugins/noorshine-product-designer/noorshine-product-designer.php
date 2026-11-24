<?php
/**
 * Plugin Name: NoorShine Product Designer
 * Description: Safe 2D SVG product previews, configurable options, pricing rules, and WooCommerce order metadata for NoorShine.
 * Version: 1.0.1
 * Author: NoorShine
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * Text Domain: noorshine-designer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NSPD_VERSION', '1.0.1' );
define( 'NSPD_FILE', __FILE__ );

final class NoorShine_Product_Designer {
	const OPTION = 'nspd_settings';
	const NONCE  = 'nspd_add_configuration';

	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'boot' ) );
	}

	public static function boot() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'woocommerce_notice' ) );
			return;
		}
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'option_page_capability_nspd_group', static fn() => 'manage_woocommerce' );
		add_action( 'add_meta_boxes_product', array( __CLASS__, 'product_meta_box' ) );
		add_action( 'save_post_product', array( __CLASS__, 'save_product_meta' ) );
		add_shortcode( 'noorshine_designer', array( __CLASS__, 'shortcode' ) );
		add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'render_on_product' ), 18 );
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate_add_to_cart' ), 10, 5 );
		add_action( 'woocommerce_store_api_validate_add_to_cart', array( __CLASS__, 'validate_store_api_add_to_cart' ) );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( __CLASS__, 'loop_designer_link' ), 10, 2 );
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_item_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'order_item_data' ), 10, 4 );
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'apply_approved_price_adjustment' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function defaults() {
		return array(
			'styles'            => array( 'Classic Arch', 'Geometric Halo', 'Quiet Minimal' ),
			'materials'         => array( 'MDF', 'Plywood', 'Acrylic', 'Mirror acrylic' ),
			'finishes'          => array( 'Matte Black', 'Gold', 'Silver', 'Natural Wood', 'Walnut', 'White' ),
			'sizes'             => array( '12 inches', '18 inches', '24 inches', '30 inches', '36 inches' ),
			'approved_texts'    => array(
				array( 'label' => 'No text / geometric only', 'value' => '', 'verified' => true ),
			),
			'pricing_rules'     => array(),
			'pricing_approved'  => false,
			'templates'         => array( 'arch', 'round', 'landscape' ),
			'production_notice' => 'Your preview is illustrative. Every custom configuration receives a manual production review.',
		);
	}

	public static function settings() {
		return wp_parse_args( get_option( self::OPTION, array() ), self::defaults() );
	}

	public static function woocommerce_notice() {
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'NoorShine Product Designer requires WooCommerce to be installed and active.', 'noorshine-designer' ) . '</p></div>';
	}

	public static function admin_menu() {
		add_submenu_page( 'woocommerce', __( 'Product Designer', 'noorshine-designer' ), __( 'Product Designer', 'noorshine-designer' ), 'manage_woocommerce', 'noorshine-product-designer', array( __CLASS__, 'admin_page' ) );
	}

	public static function register_settings() {
		register_setting( 'nspd_group', self::OPTION, array( 'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ), 'default' => self::defaults() ) );
	}

	private static function lines( $value ) {
		if ( is_array( $value ) ) return array_values( array_filter( array_map( 'sanitize_text_field', $value ) ) );
		return array_values( array_filter( array_map( 'sanitize_text_field', preg_split( '/\r?\n/', (string) $value ) ) ) );
	}

	public static function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) return self::settings();
		$old = self::settings();
		$output = self::defaults();
		foreach ( array( 'styles', 'materials', 'finishes', 'sizes', 'templates' ) as $key ) $output[ $key ] = self::lines( $input[ $key ] ?? array() );
		$output['production_notice'] = sanitize_textarea_field( $input['production_notice'] ?? $old['production_notice'] );
		$output['pricing_approved'] = ! empty( $input['pricing_approved'] );
		$output['approved_texts'] = array();
		$raw_labels = $input['approved_text_labels'] ?? '';
		$labels = array_map( 'sanitize_text_field', is_array( $raw_labels ) ? $raw_labels : preg_split( '/\r?\n/', (string) $raw_labels ) );
		$raw_values = is_array( $input['approved_text_values'] ?? null ) ? $input['approved_text_values'] : preg_split( '/\r?\n/', (string) ( $input['approved_text_values'] ?? '' ) );
		$values = array_map( 'sanitize_text_field', $raw_values );
		foreach ( $labels as $index => $label ) {
			if ( '' === $label || ! array_key_exists( $index, $values ) || '__custom__' === $values[ $index ] ) continue;
			$output['approved_texts'][] = array( 'label' => $label, 'value' => sanitize_text_field( $values[ $index ] ?? '' ), 'verified' => true );
		}
		if ( ! $output['approved_texts'] ) $output['approved_texts'] = self::defaults()['approved_texts'];
		$output['pricing_rules'] = array();
		$rules_input = $input['pricing_rules_json'] ?? '[]';
		$rules = is_string( $rules_input ) ? json_decode( $rules_input, true ) : null;
		if ( is_array( $rules ) ) {
			foreach ( $rules as $rule ) {
				if ( ! is_array( $rule ) || empty( $rule['size'] ) || ! isset( $rule['adjustment'] ) || ! is_numeric( $rule['adjustment'] ) || ! is_finite( (float) $rule['adjustment'] ) ) continue;
				$output['pricing_rules'][] = array(
					'size'       => sanitize_text_field( $rule['size'] ),
					'material'   => sanitize_text_field( $rule['material'] ?? '*' ),
					'adjustment' => (float) $rule['adjustment'],
				);
			}
		}
		return $output;
	}

	public static function admin_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) return;
		$settings = self::settings();
		$labels = wp_list_pluck( $settings['approved_texts'], 'label' );
		$values = wp_list_pluck( $settings['approved_texts'], 'value' );
		?>
		<div class="wrap"><h1><?php esc_html_e( 'NoorShine Product Designer', 'noorshine-designer' ); ?></h1>
		<p><?php esc_html_e( 'Only enter calligraphy text that has completed source, script and production review. Pricing stays hidden until the approval box is enabled.', 'noorshine-designer' ); ?></p>
		<form method="post" action="options.php"><?php settings_fields( 'nspd_group' ); ?>
		<table class="form-table" role="presentation">
		<?php foreach ( array( 'styles' => 'Styles', 'materials' => 'Materials', 'finishes' => 'Finishes', 'sizes' => 'Sizes', 'templates' => 'Templates' ) as $key => $label ) : ?>
		<tr><th scope="row"><label for="nspd-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th><td><textarea class="large-text code" rows="5" id="nspd-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( self::OPTION ); ?>[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( implode( "\n", $settings[ $key ] ) ); ?></textarea><p class="description"><?php esc_html_e( 'One approved option per line.', 'noorshine-designer' ); ?></p></td></tr>
		<?php endforeach; ?>
		<tr><th scope="row"><?php esc_html_e( 'Approved text labels', 'noorshine-designer' ); ?></th><td><textarea class="large-text code" rows="4" name="<?php echo esc_attr( self::OPTION ); ?>[approved_text_labels]"><?php echo esc_textarea( implode( "\n", $labels ) ); ?></textarea></td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Approved text values', 'noorshine-designer' ); ?></th><td><textarea class="large-text code" dir="auto" rows="4" name="<?php echo esc_attr( self::OPTION ); ?>[approved_text_values]"><?php echo esc_textarea( implode( "\n", $values ) ); ?></textarea><p class="description"><?php esc_html_e( 'Line order must match the labels. Do not add Quranic or Hadith text before manual verification.', 'noorshine-designer' ); ?></p></td></tr>
		<tr><th scope="row"><label for="nspd-rules"><?php esc_html_e( 'Pricing rules JSON', 'noorshine-designer' ); ?></label></th><td><textarea class="large-text code" id="nspd-rules" rows="8" name="<?php echo esc_attr( self::OPTION ); ?>[pricing_rules_json]"><?php echo esc_textarea( wp_json_encode( $settings['pricing_rules'], JSON_PRETTY_PRINT ) ); ?></textarea><p class="description">[{"size":"24 inches","material":"Acrylic","adjustment":1000}]</p></td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Pricing approval', 'noorshine-designer' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[pricing_approved]" value="1" <?php checked( $settings['pricing_approved'] ); ?>> <?php esc_html_e( 'Owner has approved these adjustment rules for customer estimates', 'noorshine-designer' ); ?></label></td></tr>
		<tr><th scope="row"><label for="nspd-notice"><?php esc_html_e( 'Production notice', 'noorshine-designer' ); ?></label></th><td><textarea class="large-text" id="nspd-notice" rows="3" name="<?php echo esc_attr( self::OPTION ); ?>[production_notice]"><?php echo esc_textarea( $settings['production_notice'] ); ?></textarea></td></tr>
		</table><?php submit_button(); ?></form></div>
		<?php
	}

	public static function product_meta_box() {
		add_meta_box( 'nspd_product', __( 'NoorShine Designer', 'noorshine-designer' ), array( __CLASS__, 'product_meta_box_html' ), 'product', 'side' );
	}

	public static function product_meta_box_html( $post ) {
		wp_nonce_field( 'nspd_product_meta', 'nspd_product_nonce' );
		$enabled = get_post_meta( $post->ID, '_noorshine_designer_enabled', true );
		$status  = get_post_meta( $post->ID, '_noorshine_production_status', true );
		?><p><label><input type="checkbox" name="nspd_enabled" value="yes" <?php checked( $enabled, 'yes' ); ?>> <?php esc_html_e( 'Enable designer on this product', 'noorshine-designer' ); ?></label></p><p><label><?php esc_html_e( 'Production approval status', 'noorshine-designer' ); ?><select name="nspd_production_status" style="width:100%"><option value="concept-only" <?php selected( $status, 'concept-only' ); ?>><?php esc_html_e( 'Concept only', 'noorshine-designer' ); ?></option><option value="prototype-review" <?php selected( $status, 'prototype-review' ); ?>><?php esc_html_e( 'Prototype review', 'noorshine-designer' ); ?></option><option value="approved" <?php selected( $status, 'approved' ); ?>><?php esc_html_e( 'Production approved', 'noorshine-designer' ); ?></option></select></label></p><?php
	}

	public static function save_product_meta( $post_id ) {
		if ( ! wp_verify_nonce( self::posted( 'nspd_product_nonce' ), 'nspd_product_meta' ) || ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) return;
		update_post_meta( $post_id, '_noorshine_designer_enabled', isset( $_POST['nspd_enabled'] ) ? 'yes' : 'no' );
		$status = sanitize_key( self::posted( 'nspd_production_status' ) );
		if ( ! in_array( $status, array( 'concept-only', 'prototype-review', 'approved' ), true ) ) $status = 'concept-only';
		update_post_meta( $post_id, '_noorshine_production_status', $status );
	}

	public static function register_assets() {
		wp_register_style( 'nspd', plugins_url( 'assets/designer.css', __FILE__ ), array(), NSPD_VERSION );
		wp_register_script( 'nspd', plugins_url( 'assets/designer.js', __FILE__ ), array( 'jquery' ), NSPD_VERSION, true );
	}

	public static function render_on_product() {
		global $product;
		if ( $product && self::designer_enabled( $product->get_id() ) ) echo self::shortcode( array( 'product_id' => $product->get_id() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	private static function safe_options( $items ) {
		return array_map( 'sanitize_text_field', array_values( array_filter( (array) $items ) ) );
	}

	public static function shortcode( $attributes ) {
		$attributes = shortcode_atts( array( 'product_id' => is_singular( 'product' ) ? get_the_ID() : 0 ), $attributes );
		$product_id = absint( $attributes['product_id'] );
		$product = wc_get_product( $product_id );
		if ( ! $product || ! is_singular( 'product' ) || get_queried_object_id() !== $product_id || ! self::designer_enabled( $product_id ) || post_password_required( $product_id ) ) return self::base_product_chooser();
		$settings = self::settings();
		wp_enqueue_style( 'nspd' );
		wp_enqueue_script( 'nspd' );
		wp_localize_script( 'nspd', 'nspdConfig', array( 'currency' => get_woocommerce_currency(), 'basePrice' => $product->is_type( 'variable' ) ? null : (float) wc_get_price_to_display( $product ), 'pricingApproved' => (bool) $settings['pricing_approved'], 'pricingRules' => $settings['pricing_approved'] ? $settings['pricing_rules'] : array() ) );
		$preview_id = wp_unique_id( 'nspd-preview-' );
		ob_start();
		?>
		<section class="nspd" data-nspd>
			<div class="nspd__heading"><p><?php esc_html_e( 'Make it yours', 'noorshine-designer' ); ?></p><h2><?php esc_html_e( 'Preview your configuration', 'noorshine-designer' ); ?></h2></div>
			<div class="nspd__layout">
				<div class="nspd__preview" data-preview-shell>
					<svg viewBox="0 0 640 520" role="img" aria-labelledby="<?php echo esc_attr( $preview_id . '-title ' . $preview_id . '-desc' ); ?>"><title id="<?php echo esc_attr( $preview_id . '-title' ); ?>"><?php esc_html_e( 'Product design preview', 'noorshine-designer' ); ?></title><desc id="<?php echo esc_attr( $preview_id . '-desc' ); ?>"><?php esc_html_e( 'Illustrative preview for manual review.', 'noorshine-designer' ); ?></desc><rect width="640" height="520" fill="#eee7da"/><g data-preview-art><path data-preview-shape d="M170 420V220C170 105 470 105 470 220V420Z" fill="#1f1e1a"/><circle data-preview-ring cx="320" cy="250" r="94" fill="none" stroke="#c6a463" stroke-width="3"/><text data-preview-text x="320" y="265" text-anchor="middle" fill="#f4ead9" font-size="42" font-family="Georgia, serif" unicode-bidi="plaintext"></text></g></svg>
					<span class="nspd__preview-badge"><?php esc_html_e( 'Illustrative 2D preview', 'noorshine-designer' ); ?></span>
				</div>
				<div class="nspd__controls">
					<?php wp_nonce_field( self::NONCE, 'nspd_nonce' ); ?><input type="hidden" name="nspd_active" value="1">
					<label><?php esc_html_e( 'Text option', 'noorshine-designer' ); ?><select name="nspd_text_option" data-nspd-text-option><?php foreach ( $settings['approved_texts'] as $item ) : if ( empty( $item['verified'] ) ) continue; ?><option value="<?php echo esc_attr( $item['value'] ); ?>"><?php echo esc_html( $item['label'] ); ?></option><?php endforeach; ?><option value="__custom__"><?php esc_html_e( 'Custom non-religious text', 'noorshine-designer' ); ?></option></select></label>
					<div class="nspd__custom" data-custom-fields hidden><label><?php esc_html_e( 'Your text', 'noorshine-designer' ); ?><input type="text" name="nspd_custom_text" maxlength="40" autocomplete="off" data-custom-text></label><label class="nspd__check"><input type="checkbox" name="nspd_non_religious_confirm" value="yes"> <?php esc_html_e( 'I confirm this is a name or other non-religious text. Quranic and Hadith requests must use the manual commission process.', 'noorshine-designer' ); ?></label></div>
					<div class="nspd__two"><?php self::select_field( 'nspd_style', __( 'Style', 'noorshine-designer' ), self::safe_options( $settings['styles'] ), 'data-nspd-style' ); ?><?php self::select_field( 'nspd_template', __( 'Template', 'noorshine-designer' ), self::safe_options( $settings['templates'] ), 'data-nspd-template' ); ?></div>
					<div class="nspd__two"><?php self::select_field( 'nspd_material', __( 'Material', 'noorshine-designer' ), self::safe_options( $settings['materials'] ), 'data-nspd-material' ); ?><?php self::select_field( 'nspd_finish', __( 'Finish', 'noorshine-designer' ), self::safe_options( $settings['finishes'] ), 'data-nspd-finish' ); ?></div>
					<?php self::select_field( 'nspd_size', __( 'Size', 'noorshine-designer' ), self::safe_options( $settings['sizes'] ), 'data-nspd-size' ); ?>
					<div class="nspd__estimate" data-nspd-estimate><?php echo $settings['pricing_approved'] ? esc_html__( 'Choose options to see the approved estimate.', 'noorshine-designer' ) : esc_html__( 'Price estimate awaits owner-approved rules.', 'noorshine-designer' ); ?></div>
					<p class="nspd__notice"><?php echo esc_html( $settings['production_notice'] ); ?></p>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	private static function base_product_chooser() {
		$query = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 12,
				'has_password'  => false,
				'no_found_rows' => true,
				'meta_query'    => array(
					array( 'key' => '_noorshine_designer_enabled', 'value' => 'yes' ),
					array( 'key' => '_noorshine_production_status', 'value' => 'approved' ),
				),
			)
		);
		ob_start();
		?>
		<section class="nspd nspd--chooser"><div class="nspd__heading"><p><?php esc_html_e( 'Step one', 'noorshine-designer' ); ?></p><h2><?php esc_html_e( 'Choose an approved base product', 'noorshine-designer' ); ?></h2></div>
		<?php if ( $query->have_posts() ) : ?><div class="nspd__products"><?php while ( $query->have_posts() ) : $query->the_post(); $product = wc_get_product( get_the_ID() ); ?><a href="<?php the_permalink(); ?>"><?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ); ?><strong><?php the_title(); ?></strong><span><?php esc_html_e( 'Customize this design →', 'noorshine-designer' ); ?></span></a><?php endwhile; ?></div><?php else : ?><p><?php esc_html_e( 'No base product has completed approval yet. Draft concepts are intentionally unavailable to customers.', 'noorshine-designer' ); ?></p><?php endif; wp_reset_postdata(); ?>
		</section>
		<?php
		wp_enqueue_style( 'nspd' );
		return ob_get_clean();
	}

	private static function select_field( $name, $label, $options, $attribute = '' ) {
		?><label><?php echo esc_html( $label ); ?><select name="<?php echo esc_attr( $name ); ?>" <?php echo esc_attr( $attribute ); ?>><?php foreach ( $options as $option ) : ?><option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option><?php endforeach; ?></select></label><?php
	}

	private static function posted_configuration() {
		if ( '1' !== self::posted( 'nspd_active' ) || ! wp_verify_nonce( self::posted( 'nspd_nonce' ), self::NONCE ) ) return null;
		$settings = self::settings();
		$text_option = self::posted( 'nspd_text_option' );
		$verified_values = wp_list_pluck( array_filter( $settings['approved_texts'], fn( $item ) => ! empty( $item['verified'] ) ), 'value' );
		if ( '__custom__' === $text_option ) {
			if ( 'yes' !== ( $_POST['nspd_non_religious_confirm'] ?? '' ) ) throw new Exception( __( 'Confirm that custom text is non-religious.', 'noorshine-designer' ) );
			$text = self::posted( 'nspd_custom_text' );
			if ( '' === $text || ! preg_match( '/^.{1,40}$/us', $text ) ) throw new Exception( __( 'Enter custom text up to 40 characters.', 'noorshine-designer' ) );
			$text_type = 'custom-non-religious';
		} else {
			if ( ! in_array( $text_option, $verified_values, true ) ) throw new Exception( __( 'Select an approved text option.', 'noorshine-designer' ) );
			$text = $text_option;
			$text_type = 'approved-template';
		}
		$map = array( 'style' => 'styles', 'template' => 'templates', 'material' => 'materials', 'finish' => 'finishes', 'size' => 'sizes' );
		$config = array( 'text' => $text, 'text_type' => $text_type, 'review_status' => 'manual-production-review-required' );
		foreach ( $map as $field => $setting ) {
			$value = self::posted( 'nspd_' . $field );
			if ( ! in_array( $value, $settings[ $setting ], true ) ) throw new Exception( sprintf( __( 'Invalid %s option.', 'noorshine-designer' ), $field ) );
			$config[ $field ] = $value;
		}
		return $config;
	}

	public static function validate_add_to_cart( $passed, $product_id, $quantity, $variation_id = 0, $variation_attributes = array() ) {
		if ( 'yes' !== get_post_meta( $product_id, '_noorshine_designer_enabled', true ) ) return $passed;
		try {
			if ( ! self::designer_enabled( $product_id ) ) throw new Exception( __( 'This design is awaiting production approval.', 'noorshine-designer' ) );
			$config = self::posted_configuration();
			if ( ! $config ) throw new Exception( __( 'Choose and confirm a product design configuration before adding this item.', 'noorshine-designer' ) );
			if ( $variation_id ) {
				foreach ( array( 'material', 'finish', 'size' ) as $field ) {
					$value = $variation_attributes[ 'attribute_pa_' . $field ] ?? $variation_attributes[ 'attribute_' . $field ] ?? '';
					if ( '' !== $value && sanitize_title( $value ) !== sanitize_title( $config[ $field ] ) ) {
						throw new Exception( __( 'Match the material, finish and size in the designer to your selected product variation.', 'noorshine-designer' ) );
					}
				}
			}
		} catch ( Exception $error ) {
			wc_add_notice( $error->getMessage(), 'error' );
			return false;
		}
		return $passed;
	}

	private static function posted( $key ) {
		return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	private static function designer_enabled( $product_id ) {
		return 'yes' === get_post_meta( $product_id, '_noorshine_designer_enabled', true ) && 'approved' === get_post_meta( $product_id, '_noorshine_production_status', true );
	}

	public static function validate_store_api_add_to_cart( $product ) {
		$product_id = $product->get_parent_id() ?: $product->get_id();
		if ( 'yes' === get_post_meta( $product_id, '_noorshine_designer_enabled', true ) ) {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'nspd_design_required', __( 'Configure this design on its product page.', 'noorshine-designer' ), 400 );
		}
	}

	public static function loop_designer_link( $link, $product ) {
		if ( 'yes' !== get_post_meta( $product->get_id(), '_noorshine_designer_enabled', true ) ) return $link;
		return sprintf( '<a class="button" href="%s">%s</a>', esc_url( $product->get_permalink() ), esc_html__( 'View design', 'noorshine-designer' ) );
	}

	public static function cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		if ( ! self::designer_enabled( $product_id ) ) return $cart_item_data;
		try { $config = self::posted_configuration(); } catch ( Exception $error ) { wc_add_notice( $error->getMessage(), 'error' ); return $cart_item_data; }
		if ( $config ) { $cart_item_data['noorshine_design'] = $config; $cart_item_data['noorshine_design_key'] = wp_generate_uuid4(); }
		return $cart_item_data;
	}

	public static function display_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item['noorshine_design'] ) ) return $item_data;
		$labels = array( 'text' => __( 'Design text', 'noorshine-designer' ), 'style' => __( 'Style', 'noorshine-designer' ), 'template' => __( 'Template', 'noorshine-designer' ), 'material' => __( 'Material', 'noorshine-designer' ), 'finish' => __( 'Finish', 'noorshine-designer' ), 'size' => __( 'Size', 'noorshine-designer' ), 'review_status' => __( 'Review status', 'noorshine-designer' ) );
		foreach ( $labels as $key => $label ) if ( isset( $cart_item['noorshine_design'][ $key ] ) ) $item_data[] = array( 'key' => $label, 'value' => wc_clean( $cart_item['noorshine_design'][ $key ] ) );
		return $item_data;
	}

	public static function order_item_data( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values['noorshine_design'] ) ) return;
		$labels = array( 'text' => 'NoorShine Text', 'text_type' => 'NoorShine Text Type', 'style' => 'NoorShine Style', 'template' => 'NoorShine Template', 'material' => 'NoorShine Material', 'finish' => 'NoorShine Finish', 'size' => 'NoorShine Size', 'review_status' => 'NoorShine Review Status' );
		foreach ( $values['noorshine_design'] as $key => $value ) {
			if ( isset( $labels[ $key ] ) ) $item->add_meta_data( $labels[ $key ], wc_clean( $value ), true );
		}
		$item->add_meta_data( '_noorshine_design_json', wp_json_encode( $values['noorshine_design'] ), true );
	}

	private static function approved_adjustment( $config ) {
		$settings = self::settings();
		if ( empty( $settings['pricing_approved'] ) ) return 0.0;
		$total = 0.0;
		foreach ( $settings['pricing_rules'] as $rule ) {
			if ( $rule['size'] === $config['size'] && ( '*' === $rule['material'] || $rule['material'] === $config['material'] ) ) $total += (float) $rule['adjustment'];
		}
		return max( 0.0, $total );
	}

	public static function apply_approved_price_adjustment( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
		foreach ( $cart->get_cart() as $item ) {
			if ( empty( $item['noorshine_design'] ) || empty( $item['data'] ) ) continue;
			$catalog_product = wc_get_product( $item['variation_id'] ?: $item['product_id'] );
			if ( ! $catalog_product || '' === $catalog_product->get_price() ) continue;
			$item['data']->set_price( (float) $catalog_product->get_price() + self::approved_adjustment( $item['noorshine_design'] ) );
		}
	}
}

NoorShine_Product_Designer::init();

