<?php
    get_header();
?>
    <div class="qk-single-content wrapper">

        <div id="primary-home" class="content-area">

            <?php  while ( have_posts() ) : the_post(); ?>
                
                <article id="post-<?php the_ID(); ?>" class="single-article qk-radius box">
                	<header class="entry-header">
                		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                	</header><!-- .entry-header -->
                
                	<div class="entry-content">
                		<?php
                			the_content();
                
                			wp_link_pages( array(
                				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'qk' ),
                				'after'  => '</div>',
                			) );
                		?>
                	</div><!-- .entry-content -->
                </article><!-- #post-<?php the_ID(); ?> -->
				
            <?php 
                if ( (comments_open() || get_comments_number())) :
                    comments_template();
                endif;

                endwhile; 
            ?>

        </div>

    <?php 
        get_sidebar(); 
    ?>
    
    </div>

<?php
get_footer();