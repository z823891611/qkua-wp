<?php
/**
 * Qkua child theme - enqueue integration assets and localize config
 * Paste or include this snippet into your child theme's functions.php
 *
 * IMPORTANT: backup functions.php before editing.
 */

if ( ! function_exists( 'qkua_child_enqueue_steam_front' ) ) {
    function qkua_child_enqueue_steam_front() {
        $handle = 'steam-front-qkua';
        $dir = get_stylesheet_directory_uri() . '/assets';
        // Register CSS
        wp_register_style( $handle . '-css', $dir . '/css/steam-front-qkua.css', array(), '20250926' );
        wp_enqueue_style( $handle . '-css' );

        // Register JS
        wp_register_script( $handle . '-js', $dir . '/js/steam-front-qkua.js', array( 'jquery' ), '20250926', true );

        // default cfg - you can change these values or make them dynamic via theme options
        $cfg = array(
            'place' => 'toolbar',        // toolbar or sidebar
            'fixCategory' => true,       // if true, set default category on write page
            'categoryId' => 4,           // default category id
            'lockCredit' => false,       // if true, force content permission to credit
            'creditAmount' => 500,       // default credit amount (used only if lockCredit true)
            'fillTags' => true,          // if true, try to auto-fill tags (enter to confirm)
            'extraVideo' => true,        // enable extra video field mapping
            'extraDownload' => true,     // enable extra download field mapping
            'preferQkuaTpl' => true      // prefer qkua download template select
        );

        wp_localize_script( $handle . '-js', 'QkuaSteamCfg', $cfg );
        wp_enqueue_script( $handle . '-js' );
    }
    add_action( 'wp_enqueue_scripts', 'qkua_child_enqueue_steam_front' );
    add_action( 'admin_enqueue_scripts', 'qkua_child_enqueue_steam_front' ); // ensure available in admin/post front-end area if needed
}
?>