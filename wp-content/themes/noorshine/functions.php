<?php
/**
 * NoorShine theme setup.
 *
 * @package NoorShine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NOORSHINE_THEME_VERSION', '1.0.4' );

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

function noorshine_catalog_previews() {
	static $products;
	if ( isset( $products ) ) {
		return $products;
	}

	$products = array();
	$posts = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'draft',
			'posts_per_page' => -1,
			'meta_key'       => '_noorshine_catalog_preview',
			'meta_value'     => 'yes',
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	foreach ( $posts as $post ) {
		$product = wc_get_product( $post->ID );
		if ( $product && wp_attachment_is_image( $product->get_image_id() ) ) {
			$products[] = $product;
		}
	}
	return $products;
}

function noorshine_collection_terms() {
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'pad_counts' => true ) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}

	$preview_terms = array();
	foreach ( noorshine_catalog_previews() as $product ) {
		foreach ( $product->get_category_ids() as $term_id ) {
			$preview_terms[] = $term_id;
			$preview_terms = array_merge( $preview_terms, get_ancestors( $term_id, 'product_cat' ) );
		}
	}
	return array_filter(
		$terms,
		static function ( $term ) use ( $preview_terms ) {
			return $term->count > 0 || in_array( $term->term_id, $preview_terms, true );
		}
	);
}

function noorshine_collection_filter() {
	if ( ! is_shop() && ! is_product_category() ) return;
	$terms = noorshine_collection_terms();
	if ( ! $terms ) return;
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

function noorshine_collection_shortcode_atts( $atts ) {
	if ( is_page( 'all-collections' ) && '' === $atts['ids'] ) {
		$ids = wp_list_pluck( noorshine_collection_terms(), 'term_id' );
		if ( $ids ) {
			$atts['ids'] = implode( ',', $ids );
			$atts['hide_empty'] = 0;
		}
	}
	return $atts;
}
add_filter( 'shortcode_atts_product_categories', 'noorshine_collection_shortcode_atts' );

function noorshine_shop_empty_state() {
	if ( ( ! is_shop() && ! is_product_category() ) || is_search() || is_paged() ||
		is_filtered() || wc_get_loop_prop( 'is_shortcode' ) ) {
		wc_no_products_found();
		return;
	}

	$products = noorshine_catalog_previews();
	if ( is_product_category() ) {
		$term_id  = get_queried_object_id();
		$children = get_term_children( $term_id, 'product_cat' );
		$term_ids = is_wp_error( $children ) ? array( $term_id ) : array_merge( array( $term_id ), $children );
		$products = array_filter(
			$products,
			static function ( $product ) use ( $term_ids ) {
				return (bool) array_intersect( $term_ids, $product->get_category_ids() );
			}
		);
	}
	if ( ! $products ) {
		wc_no_products_found();
		return;
	}
	?>
	<section class="catalog-previews" aria-labelledby="catalog-preview-title">
		<h2 id="catalog-preview-title"><?php esc_html_e( 'Product previews', 'noorshine' ); ?></h2>
		<p><?php esc_html_e( 'Prices are estimates. Ordering will open once availability is confirmed.', 'noorshine' ); ?></p>
		<ul class="product-grid">
			<?php foreach ( $products as $product ) : ?>
				<li class="product-card">
					<?php echo wp_get_attachment_image( $product->get_image_id(), 'medium_large', false, array( 'loading' => 'lazy' ) ); ?>
					<h3><?php echo esc_html( $product->get_name() ); ?></h3>
					<p><?php echo esc_html( wp_strip_all_tags( $product->get_short_description() ) ); ?></p>
					<?php if ( '' !== $product->get_regular_price() ) : ?>
						<p class="price"><?php echo wp_kses_post( wc_price( $product->get_regular_price() ) ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
}

function noorshine_catalog_preview_hooks() {
	remove_action( 'woocommerce_no_products_found', 'wc_no_products_found' );
	add_action( 'woocommerce_no_products_found', 'noorshine_shop_empty_state' );
}
add_action( 'after_setup_theme', 'noorshine_catalog_preview_hooks', 20 );

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
	$canonical   = '';
	if ( is_front_page() ) {
		$description = __( 'Explore NoorShine Islamic wall art in Pakistan, from Arabic calligraphy and acrylic décor to wall clocks and personalized pieces.', 'noorshine' );
	} elseif ( is_tax( 'product_cat' ) ) {
		$description = term_description();
		$canonical = get_term_link( get_queried_object() );
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$description = __( 'Shop NoorShine wall art, clocks and personalized décor by collection.', 'noorshine' );
		$canonical = noorshine_shop_url();
	} elseif ( is_singular( 'product' ) && 'publish' === get_post_status() ) {
		$description = get_post_meta( get_queried_object_id(), '_noorshine_meta_description', true );
	} elseif ( is_singular( 'page' ) && has_excerpt() ) {
		$description = get_the_excerpt();
	}
	if ( is_string( $description ) && '' !== $description ) {
		echo '<meta name="description" content="' . esc_attr( wp_trim_words( wp_strip_all_tags( $description ), 27, '…' ) ) . '">' . "\n";
	}
	if ( is_string( $canonical ) && '' !== $canonical && ! is_singular() ) {
		if ( is_paged() ) $canonical = strtok( get_pagenum_link( get_query_var( 'paged' ), false ), '?' );
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
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


