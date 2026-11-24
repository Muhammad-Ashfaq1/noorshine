<?php
/**
 * NoorShine theme setup.
 *
 * @package NoorShine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NOORSHINE_THEME_VERSION', '1.0.1' );

function noorshine_setup() {
	load_theme_textdomain( 'noorshine', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	register_nav_menus(
		array(
			'primary' => __( 'Primary menu', 'noorshine' ),
			'footer'  => __( 'Footer menu', 'noorshine' ),
		)
	);
}
add_action( 'after_setup_theme', 'noorshine_setup' );

function noorshine_assets() {
	wp_enqueue_style( 'noorshine-style', get_template_directory_uri() . '/assets/css/main.css', array(), NOORSHINE_THEME_VERSION );
	wp_enqueue_script( 'noorshine-script', get_template_directory_uri() . '/assets/js/main.js', array(), NOORSHINE_THEME_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'noorshine_assets' );

function noorshine_shop_url() {
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
}

function noorshine_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page && 'publish' === $page->post_status ? get_permalink( $page ) : '';
}

function noorshine_collection_url( $slug ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	$link = $term ? get_term_link( $term ) : '';
	return is_string( $link ) ? $link : '';
}

function noorshine_woocommerce_wrappers() {
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	add_action( 'woocommerce_before_main_content', static function () {
		echo '<main id="main-content" class="container shop-shell">';
	}, 10 );
	add_action( 'woocommerce_after_main_content', static function () {
		echo '</main>';
	}, 10 );
}
add_action( 'after_setup_theme', 'noorshine_woocommerce_wrappers', 20 );

function noorshine_collection_filter() {
	if ( ! is_shop() && ! is_product_category() ) return;
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
	if ( is_wp_error( $terms ) || ! $terms ) return;
	?>
	<nav class="collection-filter" aria-label="<?php esc_attr_e( 'Filter by collection', 'noorshine' ); ?>">
		<a href="<?php echo esc_url( noorshine_shop_url() ); ?>" <?php if ( is_shop() ) echo 'aria-current="page"'; ?>><?php esc_html_e( 'All products', 'noorshine' ); ?></a>
		<?php foreach ( $terms as $term ) : $link = get_term_link( $term ); ?>
			<?php if ( is_wp_error( $link ) ) continue; ?>
			<a href="<?php echo esc_url( $link ); ?>" <?php if ( is_product_category( $term->slug ) ) echo 'aria-current="page"'; ?>><?php echo esc_html( $term->name ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php
}
add_action( 'woocommerce_before_main_content', 'noorshine_collection_filter', 25 );

function noorshine_custom_product_title( $title ) {
	if ( ! is_singular( 'product' ) || noorshine_seo_plugin_active() ) return $title;
	$custom = get_post_meta( get_queried_object_id(), '_noorshine_seo_title', true );
	return is_string( $custom ) && '' !== $custom ? wp_strip_all_tags( $custom ) : $title;
}
add_filter( 'pre_get_document_title', 'noorshine_custom_product_title' );

function noorshine_seo_plugin_active() {
	$providers = array( 'WPSEO_VERSION', 'RANK_MATH_VERSION', 'AIOSEO_VERSION', 'SEOPRESS_VERSION', 'THE_SEO_FRAMEWORK_VERSION', 'SLIM_SEO_VER' );
	return (bool) apply_filters( 'noorshine_external_seo_active', (bool) array_filter( $providers, 'defined' ) );
}

function noorshine_search_metadata() {
	if ( noorshine_seo_plugin_active() ) return;
	$description = '';
	if ( is_front_page() ) {
		$description = __( 'Explore NoorShine Islamic wall art in Pakistan, from Arabic calligraphy and acrylic décor to wall clocks and personalized pieces.', 'noorshine' );
	} elseif ( is_tax( 'product_cat' ) ) {
		$description = term_description();
	} elseif ( is_singular( 'product' ) && 'publish' === get_post_status() ) {
		$description = get_post_meta( get_queried_object_id(), '_noorshine_meta_description', true );
	}
	if ( is_string( $description ) && '' !== $description ) {
		echo '<meta name="description" content="' . esc_attr( wp_trim_words( wp_strip_all_tags( $description ), 27, '…' ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'noorshine_search_metadata', 5 );

function noorshine_cart_count() {
	return function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
}

function noorshine_cart_fragments( $fragments ) {
	ob_start();
	?>
	<span class="site-cart__count" aria-label="<?php esc_attr_e( 'Items in cart', 'noorshine' ); ?>"><?php echo esc_html( noorshine_cart_count() ); ?></span>
	<?php
	$fragments['.site-cart__count'] = ob_get_clean();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'noorshine_cart_fragments' );

function noorshine_body_classes( $classes ) {
	$classes[] = 'noorshine-site';
	return $classes;
}
add_filter( 'body_class', 'noorshine_body_classes' );


