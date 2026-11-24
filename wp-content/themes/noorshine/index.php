<?php get_header(); ?>
<main id="main-content" class="container content-shell">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?><article <?php post_class(); ?>><h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1><?php the_excerpt(); ?></article><?php endwhile; the_posts_pagination(); else : ?><p><?php esc_html_e( 'Nothing found.', 'noorshine' ); ?></p><?php endif; ?>
</main>
<?php get_footer(); ?>

