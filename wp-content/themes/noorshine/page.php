<?php get_header(); ?>
<main id="main-content" class="container content-shell"><?php while ( have_posts() ) : the_post(); ?><article <?php post_class(); ?>><header class="page-header"><p class="eyebrow"><?php esc_html_e( 'NoorShine', 'noorshine' ); ?></p><h1><?php the_title(); ?></h1></header><div class="prose"><?php the_content(); ?></div></article><?php endwhile; ?></main>
<?php get_footer(); ?>

