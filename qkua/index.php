<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}
/**
 * 首页
 */
// if(!current_user_can('administrator')) wp_die('维护中...');
get_header();

?>

<div class="qk-content">

    <div id="primary-home" class="content-area">

        <?php do_action('qk_index'); ?>
        
    </div>

</div>

<?php do_action('qk_index_after'); 

get_footer();
