<?php
/** Homepage. @package NoorShine */
get_header();
$category_cards = array(
	array( 'Islamic Calligraphy', 'islamic-wall-art', 'Arabic calligraphy for focal walls and quiet corners.' ),
	array( 'Wall Clocks', 'islamic-wall-clocks', 'Timekeeping concepts shaped with restraint and balance.' ),
	array( 'Acrylic Wall Art', 'acrylic-wall-decor', 'Layered silhouettes and reflective finishes.' ),
	array( 'Geometric Panels', 'cnc-geometric-wall-panels', 'Architectural rhythm for contemporary feature walls.' ),
);
$custom_url = noorshine_page_url( 'custom-wall-art' );
?>
<main id="main-content">
	<section class="hero">
		<div class="container hero__grid">
			<div class="hero__copy reveal">
				<p class="eyebrow"><?php esc_html_e( 'Islamic art · Modern craft · Pakistan', 'noorshine' ); ?></p>
				<h1><?php esc_html_e( 'Illuminate Your Walls with Art', 'noorshine' ); ?></h1>
				<p class="hero__lede"><?php esc_html_e( 'Original calligraphy, sculptural clocks and personalized décor—developed for serene, beautifully considered homes.', 'noorshine' ); ?></p>
				<div class="button-row"><a class="button button--gold" href="<?php echo esc_url( noorshine_shop_url() ); ?>"><?php esc_html_e( 'Explore the collection', 'noorshine' ); ?></a><?php if ( $custom_url ) : ?><a class="text-link" href="<?php echo esc_url( $custom_url ); ?>"><?php esc_html_e( 'Design something personal', 'noorshine' ); ?> <span aria-hidden="true">→</span></a><?php endif; ?></div>
				<ul class="hero__notes"><li><?php esc_html_e( 'Original concepts', 'noorshine' ); ?></li><li><?php esc_html_e( 'Manual artwork review', 'noorshine' ); ?></li><li><?php esc_html_e( 'Made-to-order planning', 'noorshine' ); ?></li></ul>
			</div>
			<div class="hero-art reveal">
				<?php if ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'class' => 'hero-art__image' ) ); ?>
				<?php else : ?>
					<div class="hero-art__arch" aria-hidden="true"><span class="hero-art__star">✦</span><span class="hero-art__word">Noor</span></div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="section section--ivory">
		<div class="container"><div class="section-heading reveal"><h2><?php esc_html_e( 'Shop by collection', 'noorshine' ); ?></h2><?php if ( $collections_url = noorshine_page_url( 'all-collections' ) ) : ?><a class="text-link" href="<?php echo esc_url( $collections_url ); ?>"><?php esc_html_e( 'View all collections', 'noorshine' ); ?> →</a><?php endif; ?></div>
			<div class="collection-grid">
			<?php foreach ( $category_cards as $index => $card ) :
				$url = noorshine_collection_url( $card[1] );
				if ( ! $url ) continue;
				$term = get_term_by( 'slug', $card[1], 'product_cat' );
				$image = get_term_meta( $term->term_id, 'thumbnail_id', true );
				?>
				<a class="collection-card reveal" href="<?php echo esc_url( $url ); ?>">
					<div class="collection-card__visual collection-card__visual--<?php echo esc_attr( $index + 1 ); ?>"><?php if ( $image ) echo wp_get_attachment_image( $image, 'woocommerce_thumbnail', false, array( 'loading' => 'lazy' ) ); ?></div>
					<div><h3><?php echo esc_html( $card[0] ); ?></h3><p><?php echo esc_html( $card[2] ); ?></p><span class="card-link"><?php esc_html_e( 'Discover', 'noorshine' ); ?> →</span></div>
				</a>
			<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php if ( $custom_url ) : ?>
	<section class="section statement-section"><div class="container statement-grid"><div class="statement-art reveal" aria-hidden="true"><span class="statement-art__ring"></span><span class="statement-art__line"></span></div><div class="reveal"><h2><?php esc_html_e( 'Custom wall art', 'noorshine' ); ?></h2><p><?php esc_html_e( 'Choose a base design, material, finish and size. Preview a name or approved calligraphy before submitting your configuration for production review.', 'noorshine' ); ?></p><a class="button button--dark" href="<?php echo esc_url( $custom_url ); ?>"><?php esc_html_e( 'Open the designer', 'noorshine' ); ?></a></div></div></section>
	<?php endif; ?>

	<?php if ( class_exists( 'WooCommerce' ) ) :
		$new_products = wc_get_products( array( 'status' => 'publish', 'visibility' => 'visible', 'limit' => 4, 'orderby' => 'date', 'order' => 'DESC' ) );
		if ( $new_products ) : ?>
	<section class="section section--ivory"><div class="container"><div class="section-heading"><div><p class="eyebrow"><?php esc_html_e( 'Recently approved', 'noorshine' ); ?></p><h2><?php esc_html_e( 'New arrivals', 'noorshine' ); ?></h2></div></div><div class="product-grid"><?php foreach ( $new_products as $product ) : ?><article class="product-card"><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ); ?><h3><?php echo esc_html( $product->get_name() ); ?></h3><p class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p></a></article><?php endforeach; ?></div></div></section>
		<?php endif; endif; ?>

	<section class="section section--ivory"><div class="container"><h2><?php esc_html_e( 'Why choose NoorShine', 'noorshine' ); ?></h2><p><?php esc_html_e( 'Original designs, clearly listed materials and sizes, and manual approval for sacred artwork.', 'noorshine' ); ?></p></div></section>

	<section class="section process"><div class="container"><div class="section-heading section-heading--light"><div><p class="eyebrow"><?php esc_html_e( 'From line to form', 'noorshine' ); ?></p><h2><?php esc_html_e( 'A deliberate craft process', 'noorshine' ); ?></h2></div><p><?php esc_html_e( 'Production claims remain provisional until NoorShine approves the actual workshop workflow.', 'noorshine' ); ?></p></div><ol class="process-grid"><li><span>01</span><h3><?php esc_html_e( 'Compose', 'noorshine' ); ?></h3><p><?php esc_html_e( 'Original layouts are developed for proportion, legibility and the intended room.', 'noorshine' ); ?></p></li><li><span>02</span><h3><?php esc_html_e( 'Verify', 'noorshine' ); ?></h3><p><?php esc_html_e( 'Religious sources, Arabic letter connections and customer details receive manual review.', 'noorshine' ); ?></p></li><li><span>03</span><h3><?php esc_html_e( 'Prototype', 'noorshine' ); ?></h3><p><?php esc_html_e( 'Bridges, clearances, material thickness and mounting are tested before approval.', 'noorshine' ); ?></p></li><li><span>04</span><h3><?php esc_html_e( 'Finish', 'noorshine' ); ?></h3><p><?php esc_html_e( 'Approved pieces move to careful cutting, finishing, inspection and protective packing.', 'noorshine' ); ?></p></li></ol></div></section>

	<section class="section section--ivory"><div class="container faq-grid"><div><p class="eyebrow"><?php esc_html_e( 'Good to know', 'noorshine' ); ?></p><h2><?php esc_html_e( 'Questions, answered with care.', 'noorshine' ); ?></h2><p><?php esc_html_e( 'Clear expectations matter for made-to-order artwork. Final policies will be completed before launch.', 'noorshine' ); ?></p><a class="text-link" href="<?php echo esc_url( home_url( '/faq/' ) ); ?>"><?php esc_html_e( 'Read the full FAQ', 'noorshine' ); ?> →</a></div><div class="accordion" data-accordion><details><summary><?php esc_html_e( 'Can I request custom Quranic text?', 'noorshine' ); ?></summary><p><?php esc_html_e( 'You may submit a request, but it cannot be automatically approved. The source, Arabic script and production artwork must be manually verified first.', 'noorshine' ); ?></p></details><details><summary><?php esc_html_e( 'Are the displayed sizes confirmed?', 'noorshine' ); ?></summary><p><?php esc_html_e( 'Only sizes explicitly marked as approved on a live product are confirmed. Draft catalog sizes are planning options until prototype testing is complete.', 'noorshine' ); ?></p></details><details><summary><?php esc_html_e( 'Which payment methods are available?', 'noorshine' ); ?></summary><p><?php esc_html_e( 'Payment methods will appear only after the owner configures and tests them. No unapproved gateway or bank details are shown.', 'noorshine' ); ?></p></details></div></div></section>
</main>
<?php get_footer(); ?>

