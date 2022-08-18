<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://zezz.ee
 * @since             1.0.0
 * @package           Cleanwp
 *
 * @wordpress-plugin
 * Plugin Name:       Zezz Clean WP
 * Plugin URI:        https://zezz.ee
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.1
 * Author:            Birk Oidram
 * Author URI:        https://zezz.ee
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cleanwp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CLEANWP_VERSION', '1.0.1' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-cleanwp-activator.php
 */
function activate_cleanwp() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-cleanwp-activator.php';
    Cleanwp_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-cleanwp-deactivator.php
 */
function deactivate_cleanwp() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-cleanwp-deactivator.php';
    Cleanwp_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_cleanwp' );
register_deactivation_hook( __FILE__, 'deactivate_cleanwp' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-cleanwp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_cleanwp() {

    $plugin = new Cleanwp();
    $plugin->run();

}
run_cleanwp();



/* DISABLE GUTENBERG WIDGETS */

function example_theme_support() {
    remove_theme_support( 'widgets-block-editor' );
}

add_action( 'after_setup_theme', 'example_theme_support' );


/* DISABLE BLOCKS, SCIRPTS AND CSS */

// Disable Gutenberg on the back end.
add_filter( 'use_block_editor_for_post', '__return_false' );

// Disable Gutenberg for widgets.
add_filter( 'use_widgets_block_editor', '__return_false' );

add_action( 'wp_enqueue_scripts', function() {
    // Remove CSS on the front end.
    wp_dequeue_style( 'wp-block-library' );

    // Remove Gutenberg theme.
    wp_dequeue_style( 'wp-block-library-theme' );

    // Remove inline global CSS on the front end.
    wp_dequeue_style( 'global-styles' );
    
}, 20 );

/* DISABLE FRONTEND BLOCK STYLE FROM GUTENBERG */

function themesharbor_disable_woocommerce_block_styles() {
    wp_dequeue_style( 'wc-blocks-style' );
  }
  add_action( 'wp_enqueue_scripts', 'themesharbor_disable_woocommerce_block_styles' );

/* Disable Google fonts loaded by Ajax Search Pro */

  add_filter('asp_custom_fonts', 'asp_null_css');
function asp_null_css($css_arr) {
    return array();
}

/**
 * Disable Contact Form 7 scripts and styles on pages where it's not used
 */

function rjs_lwp_contactform_css_js() {
    global $post;
    if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'contact-form-7') ) {
        wp_enqueue_script('contact-form-7');
         wp_enqueue_style('contact-form-7');

    }else{
        wp_dequeue_script( 'contact-form-7' );
        wp_dequeue_style( 'contact-form-7' );
    }
}
add_action( 'wp_enqueue_scripts', 'rjs_lwp_contactform_css_js');

/**
 * Disable the emoji's
 */
function disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );    
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );  
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    
    // Remove from TinyMCE
    add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
}
add_action( 'init', 'disable_emojis' );

/**
 * Filter out the tinymce emoji plugin.
 */
function disable_emojis_tinymce( $plugins ) {
    if ( is_array( $plugins ) ) {
        return array_diff( $plugins, array( 'wpemoji' ) );
    } else {
        return array();
    }
}


/* REMOVE COMMENTS */

add_action('admin_init', function () {
    // Redirect any user trying to access comments page
    global $pagenow;
    
    if ($pagenow === 'edit-comments.php') {
        wp_redirect(admin_url());
        exit;
    }

    // Remove comments metabox from dashboard
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

    // Disable support for comments and trackbacks in post types
    foreach (get_post_types() as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
});

// Close comments on the front-end
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);

// Hide existing comments
add_filter('comments_array', '__return_empty_array', 10, 2);

// Remove comments page in menu
add_action('admin_menu', function () {
    remove_menu_page('edit-comments.php');
});

// Remove comments links from admin bar
add_action('init', function () {
    if (is_admin_bar_showing()) {
        remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
    }
});


add_filter( 'woocommerce_admin_features', function( $features ) {
    /**
     * Filter list of features and remove those not needed     *
     */
    return array_values(
        array_filter( $features, function($feature) {
            return $feature !== 'marketing';
        } ) 
    );
} );


// remove dashicons in frontend to non-admin 
    function wpdocs_dequeue_dashicon() {
        if (current_user_can( 'update_core' )) {
            return;
        }
        wp_deregister_style('dashicons');
    }
    add_action( 'wp_enqueue_scripts', 'wpdocs_dequeue_dashicon' );
    

/* REMOVE RSD LINK */

remove_action ('wp_head', 'rsd_link');


/* REMOVE WORDPRESS NAME FROM HEADER META */

function crunchify_remove_version() {
    return '';
}
add_filter('the_generator', 'crunchify_remove_version');

/* REMOVE WLV MANIFEST META */

remove_action( 'wp_head', 'wlwmanifest_link');
remove_action( 'wp_head', 'wp_shortlink_wp_head');

/* REMOVE DISCOVERY LINKS */

remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
