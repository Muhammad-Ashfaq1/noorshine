<?php
/** Homepage. @package NoorShine */
get_header();
$category_cards = array(
	array( 'Islamic Calligraphy', 'islamic-wall-art', 'Arabic calligraphy in black, gold and emerald finishes.' ),
	array( 'Wall Clocks', 'islamic-wall-clocks', 'Clocks with calligraphy, crescent and mosque motifs.' ),
	array( 'Bismillah & Alhamdulillah', 'bismillah-alhamdulillah-art', 'Horizontal and vertical plaques for entrances and living spaces.' ),
	array( 'Geometric Wall Art', 'cnc-geometric-wall-panels', 'Open rosette patterns and decorative corner accents.' ),
);
$custom_url = noorshine_page_url( 'custom-wall-art' );
$faq_url = noorshine_page_url( 'faq' );
$hero_image = get_the_post_thumbnail(
	get_queried_object_id(),
	'full',
	array( 'class' => 'hero__photo', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw' )
);
?>
<main id="main-content">
	<section class="hero<?php echo $hero_image ? ' hero--photo' : ''; ?>">
		<div class="container hero__grid">
			<div class="hero__copy">
				<p class="eyebrow"><?php esc_html_e( 'Islamic wall art & décor', 'noorshine' ); ?></p>
				<h1><?php esc_html_e( 'Illuminate Your Walls with Art', 'noorshine' ); ?></h1>
				<p class="hero__lede"><?php esc_html_e( 'Discover Arabic calligraphy, statement wall clocks and personalized décor for your home.', 'noorshine' ); ?></p>
				<div class="button-row"><a class="button button--gold" href="<?php echo esc_url( noorshine_shop_url() ); ?>"><?php esc_html_e( 'Explore the collection', 'noorshine' ); ?></a><?php if ( $custom_url ) : ?><a class="text-link" href="<?php echo esc_url( $custom_url ); ?>"><?php esc_html_e( 'Design something personal', 'noorshine' ); ?> <span aria-hidden="true">→</span></a><?php endif; ?></div>
			</div>
		</div>
		<?php echo wp_kses_post( $hero_image ); ?>
	</section>

	<section class="section section--ivory">
		<div class="container"><div class="section-heading reveal"><h2><?php esc_html_e( 'Shop by collection', 'noorshine' ); ?></h2><?php if ( $collections_url = noorshine_page_url( 'all-collections' ) ) : ?><a class="text-link" href="<?php echo esc_url( $collections_url ); ?>"><?php esc_html_e( 'View all collections', 'noorshine' ); ?> →</a><?php endif; ?></div>
			<div class="collection-grid">
			<?php foreach ( $category_cards as $card ) :
				$url = noorshine_collection_url( $card[1] );
				if ( ! $url ) continue;
				$term = get_term_by( 'slug', $card[1], 'product_cat' );
				$image = get_term_meta( $term->term_id, 'thumbnail_id', true );
				?>
				<a class="collection-card reveal" href="<?php echo esc_url( $url ); ?>">
					<?php if ( $image ) : ?><div class="collection-card__visual"><?php echo wp_get_attachment_image( $image, 'medium_large', false, array( 'loading' => 'lazy', 'alt' => '' ) ); ?></div><?php endif; ?>
					<div><h3><?php echo esc_html( $card[0] ); ?></h3><p><?php echo esc_html( $card[2] ); ?></p><span class="card-link"><?php esc_html_e( 'Discover', 'noorshine' ); ?> →</span></div>
				</a>
			<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php if ( $custom_url ) : ?>
	<section class="section statement-section"><div class="container"><div class="statement-copy reveal"><h2><?php esc_html_e( 'Custom wall art', 'noorshine' ); ?></h2><p><?php esc_html_e( 'Choose a base design, material, finish and size. Preview a name or approved calligraphy before submitting your configuration for production review.', 'noorshine' ); ?></p><a class="button button--dark" href="<?php echo esc_url( $custom_url ); ?>"><?php esc_html_e( 'Open the designer', 'noorshine' ); ?></a></div></div></section>
	<?php endif; ?>

	<?php if ( class_exists( 'WooCommerce' ) ) :
		$new_products = wc_get_products( array( 'status' => 'publish', 'visibility' => 'visible', 'limit' => 4, 'orderby' => 'date', 'order' => 'DESC' ) );
		if ( $new_products ) : ?>
	<section class="section section--ivory"><div class="container"><div class="section-heading"><div><p class="eyebrow"><?php esc_html_e( 'Recently approved', 'noorshine' ); ?></p><h2><?php esc_html_e( 'New arrivals', 'noorshine' ); ?></h2></div></div><div class="product-grid"><?php foreach ( $new_products as $product ) : ?><article class="product-card"><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ); ?><h3><?php echo esc_html( $product->get_name() ); ?></h3><p class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p></a></article><?php endforeach; ?></div></div></section>
		<?php endif; endif; ?>

	<section class="section section--ivory"><div class="container"><h2><?php esc_html_e( 'Why choose NoorShine', 'noorshine' ); ?></h2><p><?php esc_html_e( 'Original designs, clearly listed materials and sizes, and manual approval for sacred artwork.', 'noorshine' ); ?></p></div></section>

	<section class="section process"><div class="container"><div class="section-heading section-heading--light"><div><p class="eyebrow"><?php esc_html_e( 'From line to form', 'noorshine' ); ?></p><h2><?php esc_html_e( 'A deliberate craft process', 'noorshine' ); ?></h2></div><p><?php esc_html_e( 'Production claims remain provisional until NoorShine approves the actual workshop workflow.', 'noorshine' ); ?></p></div><ol class="process-grid"><li><span>01</span><h3><?php esc_html_e( 'Compose', 'noorshine' ); ?></h3><p><?php esc_html_e( 'Original layouts are developed for proportion, legibility and the intended room.', 'noorshine' ); ?></p></li><li><span>02</span><h3><?php esc_html_e( 'Verify', 'noorshine' ); ?></h3><p><?php esc_html_e( 'Religious sources, Arabic letter connections and customer details receive manual review.', 'noorshine' ); ?></p></li><li><span>03</span><h3><?php esc_html_e( 'Prototype', 'noorshine' ); ?></h3><p><?php esc_html_e( 'Bridges, clearances, material thickness and mounting are tested before approval.', 'noorshine' ); ?></p></li><li><span>04</span><h3><?php esc_html_e( 'Finish', 'noorshine' ); ?></h3><p><?php esc_html_e( 'Approved pieces move to careful cutting, finishing, inspection and protective packing.', 'noorshine' ); ?></p></li></ol></div></section>

	<section class="section section--ivory"><div class="container faq-grid"><div><p class="eyebrow"><?php esc_html_e( 'Good to know', 'noorshine' ); ?></p><h2><?php esc_html_e( 'Questions, answered with care.', 'noorshine' ); ?></h2><p><?php esc_html_e( 'Clear expectations matter for made-to-order artwork. Final policies will be completed before launch.', 'noorshine' ); ?></p><?php if ( $faq_url ) : ?><a class="text-link" href="<?php echo esc_url( $faq_url ); ?>"><?php esc_html_e( 'Read the full FAQ', 'noorshine' ); ?> →</a><?php endif; ?></div><div class="accordion" data-accordion><details><summary><?php esc_html_e( 'Can I request custom Quranic text?', 'noorshine' ); ?></summary><p><?php esc_html_e( 'The source, Arabic script and production artwork need manual verification before a commission is approved.', 'noorshine' ); ?></p></details><details><summary><?php esc_html_e( 'Are the displayed sizes confirmed?', 'noorshine' ); ?></summary><p><?php esc_html_e( 'Only sizes explicitly marked as approved on a live product are confirmed. Draft catalog sizes are planning options until prototype testing is complete.', 'noorshine' ); ?></p></details><details><summary><?php esc_html_e( 'Which payment methods are available?', 'noorshine' ); ?></summary><p><?php esc_html_e( 'Available payment methods appear at checkout once configured and tested.', 'noorshine' ); ?></p></details></div></div></section>
</main>
<?php get_footer(); ?>

