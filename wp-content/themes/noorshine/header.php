<?php
/** Site header. @package NoorShine */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main-content"><?php esc_html_e( 'Skip to content', 'noorshine' ); ?></a>
<div class="announcement" role="note">
	<div class="container announcement__inner">
		<span><?php esc_html_e( 'Original décor, thoughtfully designed in Pakistan', 'noorshine' ); ?></span>
		<span><?php esc_html_e( 'Pricing and production details shown only after verification', 'noorshine' ); ?></span>
	</div>
</div>
<header class="site-header" data-site-header>
	<div class="container site-header__inner">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'NoorShine home', 'noorshine' ); ?>">
			<span class="brand__mark" aria-hidden="true">N</span>
			<span><strong>NoorShine</strong><small><?php esc_html_e( 'Illuminate Your Walls with Art', 'noorshine' ); ?></small></span>
		</a>
		<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-menu" data-menu-toggle>
			<span></span><span></span><span></span><span class="screen-reader-text"><?php esc_html_e( 'Toggle menu', 'noorshine' ); ?></span>
		</button>
		<nav class="primary-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'noorshine' ); ?>" data-primary-nav>
			<?php if ( has_nav_menu( 'primary' ) ) :
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'menu_id'        => 'primary-menu',
					'container'      => false,
					'fallback_cb'    => false,
				)
			);
			else : ?>
				<ul id="primary-menu" class="menu">
					<?php if ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 ) : ?><li><a href="<?php echo esc_url( noorshine_shop_url() ); ?>"><?php esc_html_e( 'Shop', 'noorshine' ); ?></a></li><?php endif; ?>
					<?php foreach ( array( 'all-collections' => __( 'Collections', 'noorshine' ), 'custom-wall-art' => __( 'Custom Wall Art', 'noorshine' ), 'about-noorshine' => __( 'About', 'noorshine' ) ) as $slug => $label ) : ?>
						<?php if ( $url = noorshine_page_url( $slug ) ) : ?><li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li><?php endif; ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</nav>
		<div class="header-actions">
			<?php if ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'myaccount' ) > 0 ) : ?><a class="site-account" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Account', 'noorshine' ); ?></a><?php endif; ?>
			<?php if ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'cart' ) > 0 ) : ?><a class="site-cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Bag', 'noorshine' ); ?><span class="site-cart__count" aria-label="<?php esc_attr_e( 'Items in cart', 'noorshine' ); ?>"><?php echo esc_html( noorshine_cart_count() ); ?></span></a><?php endif; ?>
		</div>
	</div>
</header>

