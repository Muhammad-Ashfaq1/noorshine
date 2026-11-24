<?php /** Site footer. @package NoorShine */ ?>
<footer class="site-footer">
	<div class="container footer-grid">
		<div class="footer-brand">
			<a class="brand brand--light" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span class="brand__mark" aria-hidden="true">N</span><span><strong>NoorShine</strong><small><?php esc_html_e( 'Illuminate Your Walls with Art', 'noorshine' ); ?></small></span></a>
			<p><?php esc_html_e( 'Original Islamic wall art and contemporary décor concepts, developed with care for text, material and craft.', 'noorshine' ); ?></p>
		</div>
		<div><h2><?php esc_html_e( 'Explore', 'noorshine' ); ?></h2><?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'fallback_cb' => false ) ); ?></div>
		<?php
		$support = array_filter( array(
			__( 'Shipping policy', 'noorshine' )   => noorshine_page_url( 'shipping-policy' ),
			__( 'Returns & refunds', 'noorshine' ) => noorshine_page_url( 'return-refund-policy' ),
			__( 'FAQ', 'noorshine' )               => noorshine_page_url( 'faq' ),
			__( 'Contact', 'noorshine' )           => noorshine_page_url( 'contact' ),
		) );
		if ( $support ) : ?>
			<div><h2><?php esc_html_e( 'Customer care', 'noorshine' ); ?></h2><ul><?php foreach ( $support as $label => $url ) : ?><li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li><?php endforeach; ?></ul></div>
		<?php endif; ?>
		<div><h2><?php esc_html_e( 'Important', 'noorshine' ); ?></h2><p><?php esc_html_e( 'Quranic and Hadith artwork is published only after manual source and script verification.', 'noorshine' ); ?></p></div>
	</div>
	<div class="container footer-bottom">
		<span>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> NoorShine</span>
		<?php $legal = array_filter( array( __( 'Privacy', 'noorshine' ) => get_privacy_policy_url() ?: noorshine_page_url( 'privacy-policy' ), __( 'Terms', 'noorshine' ) => noorshine_page_url( 'terms-conditions' ) ) ); ?>
		<?php if ( $legal ) : ?><span><?php foreach ( $legal as $label => $url ) : ?><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a> <?php endforeach; ?></span><?php endif; ?>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>

