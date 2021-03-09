<?php
/*!
 * Plugin Name: Don's Optimization Anthology Plugin
 * Plugin URI: https://github.com/donvoorhies/oap
 * Description: An "anthology" of (IMO) some snazzy functions that I've come across over time, and which I usually hardcode into 'functions.php' to optimize my Wordpress-installs (Including: removing some (unecessary) code and -functions in the header, making the RECAPTCHA- and WPCF7-code function exclusively on the contact page, linking common jQuery-libs to a CDN, removing versions + params (scopes) on URI's, sharpen resized image files, deferring/async js, defering CSS-files and moving js from head to to footer, minimize the used HTML-code)
 * Version: 1.0.0 [RC-2]
 * Author:  Various Contributors and sources | Compiled and assembled by Don W.Voorhies (See the referenced URLs regarding the specific contributing-credits)...
 * Author URI: https://donvoorhies.github.io/oap/
 * License: Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License (extended with additional conditions)
 */

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*! 
Remove Google Fonts 
(If you want to use Google Fonts and/or your theme breaks, either comment out - or completely remove - the following block of code)
*/
add_filter( 'style_loader_src', function($href){
if(strpos($href, "//fonts.googleapis.com/") === false) {
return $href;
}
return false;
});
/*! 
Sources:
https://stackoverflow.com/questions/29134113/how-to-remove-or-dequeue-google-fonts-in-wordpress-twentyfifteen/45633445#45633445
https://stackoverflow.com/users/839434/payter 
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*! 
Prevents WordPress from testing ssl capability on domain.com/xmlrpc.php?rsd #Speed-optimization 
*/
remove_filter('atom_service_url','atom_service_url_filter');

/*! 
https://wordpress.stackexchange.com/revisions/1769/5 
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*! 
Remove version info from head and feeds #Security/Hardening 
*/
function complete_version_removal() {
    return '';
}
add_filter('the_generator', 'complete_version_removal');

/*! 
Sources:
https://wordpress.stackexchange.com/questions/1567/best-collection-of-code-for-your-functions-php-file
https://wordpress.stackexchange.com/users/472/derek-perkins 
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*! 
Remove unnecessary header info 
*/

add_action( 'init', 'remove_header_info' );
function remove_header_info() {
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'start_post_rel_link' );
remove_action( 'wp_head', 'index_rel_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head');
//remove_action( 'wp_head', 'adjacent_posts_rel_link' );         // for WordPress < 3.0
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' ); // for WordPress >= 3.0
}
/*!
https://bhoover.com/remove-unnecessary-code-from-your-wordpress-blog-header/
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
Disabling pingback and trackback notifications
*/

add_action( 'pre_ping', 'wp_internal_pingbacks' );
add_filter( 'wp_headers', 'wp_x_pingback');
add_filter( 'bloginfo_url', 'wp_pingback_url') ;
add_filter( 'bloginfo', 'wp_pingback_url') ;
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', 'wp_xmlrpc_methods' );

function wp_internal_pingbacks( &$links ) { // Disable internal pingbacks
    foreach ( $links as $l => $link ) {
        if ( 0 === strpos( $link, get_option( 'home' ) ) ) {
            unset( $links[$l] );
        }
    }
}
function wp_x_pingback( $headers ) { // Disable x-pingback
    unset( $headers['X-Pingback'] );
    return $headers;
}
function wp_pingback_url( $output, $show='') { // Remove pingback URLs
    if ( $show == 'pingback_url' ) $output = '';
    return $output;
}
function wp_xmlrpc_methods( $methods ) { // Disable XML-RPC methods
    unset( $methods['pingback.ping'] );
    return $methods;
}

/**
https://wordpress.stackexchange.com/questions/190346/disabling-pingback-and-trackback-notifications 
*/ 

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/* Disbable Self-Pingbacks*/
function wpsites_disable_self_pingbacks( &$links ) {
 foreach ( $links as $l => $link )
 if ( 0 === strpos( $link, get_option( 'home' ) ) )
 unset($links[$l]);
}
add_action( 'pre_ping', 'wpsites_disable_self_pingbacks' );

/* 
Source: Brian Jackson: "How To Speed-up wordpress" (PDF)- kinsta.com
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*!
Safely disable WP REST API 
*/
add_filter( 'rest_authentication_errors', function( $result ) {
    // If a previous authentication check was applied,
    // pass that result along without modification.
    if ( true === $result || is_wp_error( $result ) ) {
        return $result;
    }
    // No authentication has been performed yet.
    // Return an error if user is not logged in.
    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_not_logged_in',
            __( 'You are not currently logged in.' ),
            array( 'status' => 401 )
        );
    }
    // Our custom authentication check should have no effect
    // on logged-in requests
    return $result;
});

/*!
Sources: 
https://stackoverflow.com/questions/41191655/safely-disable-wp-rest-api
https://developer.wordpress.org/rest-api/frequently-asked-questions/#can-i-disable-the-rest-api
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*! 
Remove extra CSS that the 'Recent Comments' widget injects
*/
add_action( 'widgets_init', 'remove_recent_comments_style' );
function remove_recent_comments_style() {
global $wp_widget_factory;
remove_action( 'wp_head', array(
$wp_widget_factory->widgets['WP_Widget_Recent_Comments'],
'recent_comments_style'
));
}
/*! https://wordpress.stackexchange.com/revisions/3816/5 */
/*! Orignally by: Andrew Ryno */

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*! 
This code-block has replaced the previous used, as it's been deemed more efficient/plays well with the rest of the code.
Removes wp-version number params (scopes) from scripts and styles
*/
function remove_css_js_version( $src ) {
    if( strpos( $src, '?ver=' ) )
        $src = remove_query_arg( 'ver', $src );
    return $src;
}
add_filter( 'style_loader_src', 'remove_css_js_version', 9999 );
add_filter( 'script_loader_src', 'remove_css_js_version', 9999 );
/*! 
https://artisansweb.net/remove-version-css-js-wordpress/ 
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*! 
Remove WPCF7-code, Google ReCaptcha-code and -badge everywhere sitewide, apart from the page(s) using contact-form-7 
If you're using another mail-form, then remove or comment out, and otherwise you're on your own 
*/
function contactform_dequeue_scripts() {
    $load_scripts = false;
    if( is_singular() ) {
    	$post = get_post();
    	if( has_shortcode($post->post_content, 'contact-form-7') ) {
        	$load_scripts = true;			
		}
    }
    if( ! $load_scripts ) {
		wp_dequeue_script('contact-form-7');
        wp_dequeue_script('wpcf7-recaptcha-js-extra');
		wp_dequeue_script('wpcf7-recaptcha');
		wp_dequeue_script('google-recaptcha');
        wp_dequeue_style('contact-form-7');		
    }
}
add_action( 'wp_enqueue_scripts', 'contactform_dequeue_scripts', 99 );
/*! 
https://wordpress.org/support/topic/recaptcha-v3-script-is-being-added-to-all-pages/#post-10983560

NOTE:
To find the handler-names, I used the code at: //https://cameronjonesweb.com.au/blog/how-to-find-out-the-handle-for-enqueued-wordpress-scripts/ 
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*! 
The main intention here is as follows:
1. Remove the "outdated" jQuery core-lib used (in the front-end, mind)---
2. Add and enqueue the latest/newest jQuery core-lib

3. Otherwise just add/queue-up your needed external jQuery-scripts - after the dotted line!
*/
function replace_add_core_jquery_version(){
if	(!is_admin()){
	
wp_deregister_script('jquery-core');
//wp_deregister_script('jquery-migrate');

	
wp_register_script('jquery-core',"https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js",array(),'3.5.1',true);
wp_script_add_data('jquery-core', array( 'module','integrity','crossorigin' ) , array( 'sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==', 'anonymous' ) );
//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
/*! 
Although, jQuery-Migrate is no longer included/necessary from Wordpress v5.5, I added it anyway, if one should need it for whatever reason!
Uncomment and enqueue, if this should otherwise be the case...
*/ 
//wp_enqueue_script('jquery-migrate',"https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.3.2/jquery-migrate.min.js",array(),'3.3.2',true);
//wp_script_add_data( 'jquery-migrate', array( 'module','integrity','crossorigin' ) , array( 'sha512-3fMsI1vtU2e/tVxZORSEeuMhXnT9By80xlmXlsOku7hNwZSHJjwcOBpmy+uu+fyWwGCLkMvdVbHkeoXdAzBv+w==', 'anonymous' ) );


wp_enqueue_script('instantpage',"https://cdnjs.cloudflare.com/ajax/libs/instant.page/5.1.0/instantpage.min.js",array(),'5.1.0',true);
wp_script_add_data( 'instantpage', array( 'module','integrity','crossorigin' ) , array( 'sha512-1+qUtKoh9XZW7j+6LhRMAyOrgSQKenQ4mluTR+cvxXjP1Z54RxZuzstR/H9kgPXQsVB8IW7DMDFUJpzLjvhGSQ==', 'anonymous' ));

}
}
add_action('wp_enqueue_scripts','replace_add_core_jquery_version');

/*!
Regarding the wp_script_data: 
The integrity and crossorigin attributes are used for Subresource Integrity (SRI) checking (https://www.w3.org/TR/SRI/). 
This allows browsers to ensure that resources hosted on third-party servers have not been tampered with. 
Use of SRI is recommended as a best-practice, whenever libraries are loaded from a third-party source. Read more at srihash.org
*/

/*! 
Sources:
https://wordpress.stackexchange.com/questions/257317/update-jquery-version
https://www.paulund.co.uk/dequeue-styles-and-scripts-in-wordpress
The fascinating Instant.Page script is by Alexandre Dieulot (https://instant.page/)
SRI-string adding by: https://stackoverflow.com/questions/44827134/wordpress-script-with-integrity-and-crossorigin (See:cherryaustin's comment) 
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*! 
Custom Scripting to Move CSS and JavaScript from the Head to the Footer
First Step: We add and use here the “preload” attribute value to - in effect - defer our external stylesheets. 
*/

function add_rel_preload($html, $handle, $href, $media) {
if (is_admin())
return $html;
$html = <<<EOT
<link rel='preload' as='style' onload="this.onload=null;this.rel='stylesheet'" id='$handle' href='$href' type='text/css' media='all' />
EOT;
return $html;
}
add_filter( 'style_loader_tag', 'add_rel_preload', 10, 4 );

/*! 
Please read the author's (Bhagwad Park) interesting reasons for adding "rel=preload" - might not work completely on certain clents, 
due to no default support; however, it looks like he's concatenated the following to the one line used above: 
<link rel="preload" href="/path/to/my.css" as="style">
<link rel="stylesheet" href="/path/to/my.css" media="print" onload="this.media='all'">

/*! 
https://www.namehero.com/startup/how-to-inline-and-defer-css-on-wordpress-without-plugins/ 
*/

function hook_css() {
?>
<style>
<!--INSERT GENERATED "ABOVE THE FOLD" CRITICAL PATH CSS-STRING HERE - EITHER IMMEDIATELY BELOW THIS LINE OR BY REPLACING THIS LINE WITH THE GENERATED STRING...!-->
@charset "UTF-8";.entry-content:after,.site-content:after,.site-header:after,h1{clear:both}:root{--global--font-primary:var(--font-headings, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif);--global--font-secondary:var(--font-base, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif);--global--font-size-base:1.25rem;--global--font-size-xs:1rem;--global--font-size-sm:1.125rem;--global--font-size-md:1.25rem;--global--font-size-lg:1.5rem;--global--font-size-xl:2.25rem;--global--font-size-xxl:4rem;--global--font-size-xxxl:5rem;--global--font-size-page-title:var(--global--font-size-xxl);--global--letter-spacing:normal;--global--line-height-body:1.7;--global--line-height-heading:1.3;--global--line-height-page-title:1.1;--heading--font-family:var(--global--font-primary);--heading--font-size-h6:var(--global--font-size-xs);--heading--font-size-h5:var(--global--font-size-sm);--heading--font-size-h4:var(--global--font-size-lg);--heading--font-size-h3:calc(1.25 * var(--global--font-size-lg));--heading--font-size-h2:var(--global--font-size-xl);--heading--font-size-h1:var(--global--font-size-page-title);--heading--letter-spacing-h6:0.05em;--heading--letter-spacing-h5:0.05em;--heading--letter-spacing-h4:var(--global--letter-spacing);--heading--letter-spacing-h3:var(--global--letter-spacing);--heading--letter-spacing-h2:var(--global--letter-spacing);--heading--letter-spacing-h1:var(--global--letter-spacing);--heading--line-height-h6:var(--global--line-height-heading);--heading--line-height-h5:var(--global--line-height-heading);--heading--line-height-h4:var(--global--line-height-heading);--heading--line-height-h3:var(--global--line-height-heading);--heading--line-height-h2:var(--global--line-height-heading);--heading--line-height-h1:var(--global--line-height-page-title);--heading--font-weight:normal;--heading--font-weight-page-title:300;--heading--font-weight-strong:600;--latest-posts--title-font-family:var(--heading--font-family);--latest-posts--title-font-size:var(--heading--font-size-h3);--latest-posts--description-font-family:var(--global--font-secondary);--latest-posts--description-font-size:var(--global--font-size-sm);--list--font-family:var(--global--font-secondary);--definition-term--font-family:var(--global--font-primary);--global--color-black:#000;--global--color-dark-gray:#28303d;--global--color-gray:#39414d;--global--color-light-gray:#f0f0f0;--global--color-green:#d1e4dd;--global--color-blue:#d1dfe4;--global--color-purple:#d1d1e4;--global--color-red:#e4d1d1;--global--color-orange:#e4dad1;--global--color-yellow:#eeeadd;--global--color-white:#fff;--global--color-white-50:rgba(255, 255, 255, 0.5);--global--color-white-90:rgba(255, 255, 255, 0.9);--global--color-primary:var(--global--color-dark-gray);--global--color-secondary:var(--global--color-gray);--global--color-primary-hover:var(--global--color-primary);--global--color-background:var(--global--color-green);--global--color-border:var(--global--color-primary);--global--spacing-unit:20px;--global--spacing-measure:unset;--global--spacing-horizontal:25px;--global--spacing-vertical:30px;--global--elevation:1px 1px 3px 0 rgba(0, 0, 0, 0.2);--form--font-family:var(--global--font-secondary);--form--font-size:var(--global--font-size-sm);--form--line-height:var(--global--line-height-body);--form--color-text:var(--global--color-dark-gray);--form--color-ranged:var(--global--color-secondary);--form--label-weight:500;--form--border-color:var(--global--color-secondary);--form--border-width:3px;--form--border-radius:0;--form--spacing-unit:calc(0.5 * var(--global--spacing-unit));--cover--height:calc(15 * var(--global--spacing-vertical));--cover--color-foreground:var(--global--color-white);--cover--color-background:var(--global--color-black);--button--color-text:var(--global--color-background);--button--color-text-hover:var(--global--color-secondary);--button--color-text-active:var(--global--color-secondary);--button--color-background:var(--global--color-secondary);--button--color-background-active:var(--global--color-background);--button--font-family:var(--global--font-primary);--button--font-size:var(--global--font-size-base);--button--font-weight:500;--button--line-height:1.5;--button--border-width:3px;--button--border-radius:0;--button--padding-vertical:15px;--button--padding-horizontal:calc(2 * var(--button--padding-vertical));--entry-header--color:var(--global--color-primary);--entry-header--color-link:currentColor;--entry-header--color-hover:var(--global--color-primary-hover);--entry-header--color-focus:var(--global--color-secondary);--entry-header--font-size:var(--heading--font-size-h2);--entry-content--font-family:var(--global--font-secondary);--entry-author-bio--font-family:var(--heading--font-family);--entry-author-bio--font-size:var(--heading--font-size-h4);--branding--color-text:var(--global--color-primary);--branding--color-link:var(--global--color-primary);--branding--color-link-hover:var(--global--color-secondary);--branding--title--font-family:var(--global--font-primary);--branding--title--font-size:var(--global--font-size-lg);--branding--title--font-size-mobile:var(--heading--font-size-h4);--branding--title--font-weight:normal;--branding--title--text-transform:uppercase;--branding--description--font-size:var(--global--font-size-sm);--branding--description--font-family:var(--global--font-secondary);--branding--logo--max-width:300px;--branding--logo--max-height:100px;--branding--logo--max-width-mobile:96px;--branding--logo--max-height-mobile:96px;--primary-nav--font-family:var(--global--font-secondary);--primary-nav--font-family-mobile:var(--global--font-primary);--primary-nav--font-size:var(--global--font-size-md);--primary-nav--font-size-sub-menu:var(--global--font-size-xs);--primary-nav--font-size-mobile:var(--global--font-size-sm);--primary-nav--font-size-sub-menu-mobile:var(--global--font-size-sm);--primary-nav--font-size-button:var(--global--font-size-xs);--primary-nav--font-style:normal;--primary-nav--font-style-sub-menu-mobile:normal;--primary-nav--font-weight:normal;--primary-nav--font-weight-button:500;--primary-nav--color-link:var(--global--color-primary);--primary-nav--color-link-hover:var(--global--color-primary-hover);--primary-nav--color-text:var(--global--color-primary);--primary-nav--padding:calc(0.66 * var(--global--spacing-unit));--primary-nav--border-color:var(--global--color-primary);--pagination--color-text:var(--global--color-primary);--pagination--color-link-hover:var(--global--color-primary-hover);--pagination--font-family:var(--global--font-secondary);--pagination--font-size:var(--global--font-size-lg);--pagination--font-weight:normal;--pagination--font-weight-strong:600;--footer--color-text:var(--global--color-primary);--footer--color-link:var(--global--color-primary);--footer--color-link-hover:var(--global--color-primary-hover);--footer--font-family:var(--global--font-primary);--footer--font-size:var(--global--font-size-sm);--pullquote--font-family:var(--global--font-primary);--pullquote--font-size:var(--heading--font-size-h3);--pullquote--font-style:normal;--pullquote--letter-spacing:var(--heading--letter-spacing-h4);--pullquote--line-height:var(--global--line-height-heading);--pullquote--border-width:3px;--pullquote--border-color:var(--global--color-primary);--pullquote--color-foreground:var(--global--color-primary);--pullquote--color-background:var(--global--color-background);--quote--font-family:var(--global--font-secondary);--quote--font-size:var(--global--font-size-md);--quote--font-size-large:var(--global--font-size-xl);--quote--font-style:normal;--quote--font-weight:700;--quote--font-weight-strong:bolder;--quote--font-style-large:normal;--quote--font-style-cite:normal;--quote--line-height:var(--global--line-height-body);--quote--line-height-large:1.35;--separator--border-color:var(--global--color-border);--separator--height:1px;--table--stripes-border-color:var(--global--color-light-gray);--table--stripes-background-color:var(--global--color-light-gray);--table--has-background-text-color:var(--global--color-dark-gray);--widget--line-height-list:1.9;--widget--line-height-title:1.4;--widget--font-weight-title:700;--widget--spacing-menu:calc(0.66 * var(--global--spacing-unit));--global--admin-bar--height:0;--responsive--spacing-horizontal:calc(2 * var(--global--spacing-horizontal) * 0.6);--responsive--aligndefault-width:calc(100vw - var(--responsive--spacing-horizontal));--responsive--alignwide-width:calc(100vw - var(--responsive--spacing-horizontal));--responsive--alignfull-width:100%;--responsive--alignright-margin:var(--global--spacing-horizontal);--responsive--alignleft-margin:var(--global--spacing-horizontal)}@media only screen and (min-width:652px){:root{--global--font-size-xl:2.5rem;--global--font-size-xxl:6rem;--global--font-size-xxxl:9rem;--heading--font-size-h3:2rem;--heading--font-size-h2:3rem}}main{display:block}a{background-color:transparent;text-decoration-thickness:1px;color:var(--wp--style--color--link,var(--global--color-primary));text-underline-offset:3px;text-decoration-skip-ink:all}button{font-family:inherit;font-size:100%;line-height:1.15;margin:0;overflow:visible;text-transform:none;-webkit-appearance:button}.site-footer>.site-info .site-name,.site-title{text-transform:var(--branding--title--text-transform)}button::-moz-focus-inner{border-style:none;padding:0}button:-moz-focusring{outline:ButtonText dotted 1px}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}.entry-content>:not(.alignwide):not(.alignfull):not(.alignleft):not(.alignright):not(.wp-block-separator):not(.woocommerce){max-width:var(--responsive--aligndefault-width);margin-left:auto;margin-right:auto}.site-header,.site-main{padding-top:var(--global--spacing-vertical);padding-bottom:var(--global--spacing-vertical);margin-left:auto;margin-right:auto}.site-header{max-width:var(--responsive--alignwide-width);padding-top:calc(.75 * var(--global--spacing-vertical));padding-bottom:calc(2 * var(--global--spacing-vertical))}.entry-content img,img{max-width:100%}.site-main>*{margin-top:calc(3 * var(--global--spacing-vertical));margin-bottom:calc(3 * var(--global--spacing-vertical))}.site-main>:first-child{margin-top:0}.site-main>:last-child{margin-bottom:0}.entry-content{margin-top:var(--global--spacing-vertical);margin-right:auto;margin-bottom:var(--global--spacing-vertical);margin-left:auto}.entry-content>*,.site-main>article>*{margin-top:calc(.666 * var(--global--spacing-vertical));margin-bottom:calc(.666 * var(--global--spacing-vertical))}@media only screen and (min-width:482px){:root{--responsive--aligndefault-width:min(calc(100vw - 4 * var(--global--spacing-horizontal)), 610px);--responsive--alignwide-width:calc(100vw - 4 * var(--global--spacing-horizontal));--responsive--alignright-margin:calc(0.5 * (100vw - var(--responsive--aligndefault-width)));--responsive--alignleft-margin:calc(0.5 * (100vw - var(--responsive--aligndefault-width)))}.site-header{padding-bottom:calc(3 * var(--global--spacing-vertical))}.entry-content>*,.site-main>article>*{margin-top:var(--global--spacing-vertical);margin-bottom:var(--global--spacing-vertical)}}.entry-content>:first-child,.site-main>article>:first-child{margin-top:0}.entry-content>:last-child,.site-main>article>:last-child{margin-bottom:0}body,h1,html,li,p,ul{padding:0;margin:0;-moz-osx-font-smoothing:grayscale;-webkit-font-smoothing:antialiased}html{-webkit-text-size-adjust:100%;box-sizing:border-box;font-family:var(--global--font-secondary);line-height:var(--global--line-height-body)}*,::after,::before{box-sizing:inherit}body{font-size:var(--global--font-size-base);font-weight:400;color:var(--global--color-primary);text-align:left;background-color:var(--global--color-background)}.entry-content:after,.entry-content:before,.site-content:after,.site-content:before,.site-header:after,.site-header:before{content:"";display:table;table-layout:fixed}::-moz-placeholder{opacity:1}img{border-style:none;display:block;height:auto;vertical-align:middle}.site .button{line-height:var(--button--line-height);color:var(--button--color-text);font-weight:var(--button--font-weight);font-family:var(--button--font-family);font-size:var(--button--font-size);background-color:var(--button--color-background);border-radius:var(--button--border-radius);border:var(--button--border-width)solid var(--button--color-background);text-decoration:none;padding:var(--button--padding-vertical)var(--button--padding-horizontal)}h1{font-family:var(--heading--font-family);font-weight:var(--heading--font-weight);font-size:var(--heading--font-size-h1);letter-spacing:var(--heading--letter-spacing-h1);line-height:var(--heading--line-height-h1)}ul{font-family:var(--list--font-family);margin:0;padding-left:calc(2 * var(--global--spacing-horizontal));list-style-type:disc}p{line-height:var(--wp--typography--line-height,--global--line-height-body)}.aligncenter{clear:both;display:block;float:none;margin-right:auto;margin-left:auto;text-align:center}.site-header{display:flex;align-items:flex-start;flex-wrap:wrap;row-gap:var(--global--spacing-vertical)}@media only screen and (min-width:482px){.site-header{padding-top:calc(var(--global--spacing-vertical)/ .75)}}.site-branding{color:var(--branding--color-text);margin-right:140px}.site-title{color:var(--branding--color-link);font-family:var(--branding--title--font-family);font-size:var(--branding--title--font-size-mobile);letter-spacing:normal;line-height:var(--global--line-height-heading);margin-bottom:calc(var(--global--spacing-vertical)/ 6)}@media only screen and (min-width:482px){.site-branding{margin-right:initial;margin-top:4px}.site-title{font-size:var(--branding--title--font-size)}}.site-footer>.site-info .site-name{font-size:var(--branding--title--font-size)}.site-footer>.site-info .powered-by{margin-top:calc(.5 * var(--global--spacing-vertical))}@media only screen and (min-width:822px){:root{--responsive--aligndefault-width:min(calc(100vw - 8 * var(--global--spacing-horizontal)), 610px);--responsive--alignwide-width:min(calc(100vw - 8 * var(--global--spacing-horizontal)), 1240px)}.site-header{padding-top:calc(2.4 * var(--global--spacing-vertical))}.site-footer>.site-info .powered-by{margin-top:initial;margin-left:auto}}.site-footer>.site-info a,.site-footer>.site-info a:link,.site-footer>.site-info a:visited{color:var(--footer--color-link)}.entry-content{font-family:var(--entry-content--font-family)}.entry-content p{word-wrap:break-word}.menu-button-container{display:none;justify-content:space-between;position:absolute;right:0;padding-top:calc(.5 * var(--global--spacing-vertical));padding-bottom:calc(.25 * var(--global--spacing-vertical))}.menu-button-container #primary-mobile-menu{margin-left:auto;padding:calc(var(--button--padding-vertical) - (.25 * var(--global--spacing-unit))) calc(.5 * var(--button--padding-horizontal))}@media only screen and (max-width:481px){.site-header:not(.has-logo).has-title-and-tagline .site-branding{margin-right:0;max-width:calc(100% - 160px)}.menu-button-container{display:flex}}.menu-button-container .button.button{display:flex;font-size:var(--primary-nav--font-size-button);font-weight:var(--primary-nav--font-weight-button);background-color:transparent;border:none;color:var(--primary-nav--color-link)}.menu-button-container .button.button .dropdown-icon{display:flex;align-items:center}.menu-button-container .button.button .dropdown-icon .svg-icon{margin-left:calc(.25 * var(--global--spacing-unit))}.menu-button-container .button.button .dropdown-icon.open .svg-icon{position:relative;top:-1px}.menu-button-container .button.button .dropdown-icon.close{display:none}.primary-navigation{position:absolute;top:var(--global--admin-bar--height);right:0;color:var(--primary-nav--color-text);font-size:var(--primary-nav--font-size);line-height:1.15;margin-top:0;margin-bottom:0}.primary-navigation>.primary-menu-container{position:fixed;visibility:hidden;opacity:0;top:0;right:0;bottom:0;left:0;padding-top:calc(var(--button--line-height) * var(--primary-nav--font-size-button) + 42px + 5px);padding-left:var(--global--spacing-unit);padding-right:var(--global--spacing-unit);padding-bottom:var(--global--spacing-horizontal);background-color:var(--global--color-background);transform:translateY(var(--global--spacing-vertical))}@media only screen and (max-width:481px){.primary-navigation>.primary-menu-container{height:100vh;z-index:499;overflow-x:hidden;overflow-y:auto;border:2px solid transparent}}@media only screen and (min-width:482px){.primary-navigation{position:relative;margin-left:auto}.primary-navigation>.primary-menu-container{visibility:visible;opacity:1;position:relative;padding:0;background-color:transparent;overflow:initial;transform:none}}.primary-navigation>div>.menu-wrapper{display:flex;justify-content:flex-start;flex-wrap:wrap;list-style:none;margin:0;max-width:none;padding-left:0;position:relative}@media only screen and (max-width:481px){.primary-navigation>div>.menu-wrapper{padding-bottom:100px}}.primary-navigation>div>.menu-wrapper li{display:block;position:relative;width:100%}@media only screen and (min-width:482px){.primary-navigation>div>.menu-wrapper li{margin:0;width:inherit}.primary-navigation .primary-menu-container{margin-right:calc(0px - var(--primary-nav--padding));margin-left:calc(0px - var(--primary-nav--padding))}.primary-navigation .primary-menu-container>ul>.menu-item{display:flex}.primary-navigation .primary-menu-container>ul>.menu-item>a{padding-left:var(--primary-nav--padding);padding-right:var(--primary-nav--padding)}}.primary-navigation a{display:block;font-family:var(--primary-nav--font-family-mobile);font-size:var(--primary-nav--font-size-mobile);font-weight:var(--primary-nav--font-weight);padding:var(--primary-nav--padding)0;text-decoration:none}@media only screen and (min-width:482px){.primary-navigation a{display:block;font-family:var(--primary-nav--font-family);font-size:var(--primary-nav--font-size);font-weight:var(--primary-nav--font-weight)}}.primary-navigation a:link,.primary-navigation a:visited{color:var(--primary-nav--color-link-hover)}.primary-navigation .current-menu-item>a:first-child,.primary-navigation .current_page_item>a:first-child{text-decoration:underline;text-decoration-style:solid}.screen-reader-text{border:0;clip:rect(1px,1px,1px,1px);-webkit-clip-path:inset(50%);clip-path:inset(50%);height:1px;margin:-1px;overflow:hidden;padding:0;position:absolute!important;width:1px;word-wrap:normal!important;word-break:normal}footer *,header *,main *{max-width:var(--global--spacing-measure)}article,body,div,header,html,main,nav{max-width:none;body:not(.page-id-6) .grecaptcha-badge{display:none}}
</style>
<?php
}
add_action('wp_head', 'hook_css');
/*! 
Find the Critical Path CSS-selectors by using the online tool at either: 
https://jonassebastianohlsson.com/criticalpathcssgenerator/
or:
https://purifycss.online/
*/
/*! 
https://www.namehero.com/startup/how-to-inline-and-defer-css-on-wordpress-without-plugins/ 
*/


/*! 
Second Step: We now move all of the javascript (gathered and enqueued into handlers) down to the bottom of our HTML
*/
function remove_head_scripts() {
remove_action('wp_head', 'wp_print_styles');	
remove_action('wp_head', 'wp_print_scripts');
remove_action('wp_head', 'wp_print_head_scripts', 9);
remove_action('wp_head', 'wp_enqueue_scripts', 1);

add_action('wp_footer', 'wp_print_styles');
add_action('wp_footer', 'wp_print_scripts', 5);
add_action('wp_footer', 'wp_enqueue_scripts', 5);
add_action('wp_footer', 'wp_print_head_scripts', 5);
}
add_action( 'wp_enqueue_scripts', 'remove_head_scripts' );

/*! END Custom Scripting to Move JavaScript
https://speedrak.com/blog/how-to-move-javascripts-to-the-footer-in-wordpress/ 
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*! 
Automatically create meta description from the_content 
*/

function create_meta_desc() {
 global $post;
  if (!is_single()) { return; }
  $meta = strip_tags($post->post_content);
  $meta = strip_shortcodes($post->post_content);
  $meta = str_replace(array("\n", "\r", "\t"), ' ', $meta);
  $meta = substr($meta, 0, 125);
  echo "<meta name='description' content='$meta' />";
}
add_action('wp_head', 'create_meta_desc');

/*! 
Source: http://wpsnipp.com/index.php/functions-php/automatically-create-meta-description-from-the_content/ 
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*!
Minify HTML-code on the fly, removing line-breaks and white-spaces...
*/

class FLHM_HTML_Compression
{
protected $flhm_compress_css = true;
protected $flhm_compress_js = true;
protected $flhm_info_comment = true;
protected $flhm_remove_comments = true;
protected $html;
public function __construct($html)
{
if (!empty($html))
{
$this->flhm_parseHTML($html);
}
}
public function __toString()
{
return $this->html;
}
protected function flhm_bottomComment($raw, $compressed)
{
$raw = strlen($raw);
$compressed = strlen($compressed);
$savings = ($raw-$compressed) / $raw * 100;
$savings = round($savings, 2);
return '<!--HTML compressed, size saved '.$savings.'%. From '.$raw.' bytes, now '.$compressed.' bytes-->';
}
protected function flhm_minifyHTML($html)
{
$pattern = '/<(?<script>script).*?<\/script\s*>|<(?<style>style).*?<\/style\s*>|<!(?<comment>--).*?-->|<(?<tag>[\/\w.:-]*)(?:".*?"|\'.*?\'|[^\'">]+)*>|(?<text>((<[^!\/\w.:-])?[^<]*)+)|/si';
preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
$overriding = false;
$raw_tag = false;
$html = '';
foreach ($matches as $token)
{
$tag = (isset($token['tag'])) ? strtolower($token['tag']) : null;
$content = $token[0];
if (is_null($tag))
{
if ( !empty($token['script']) )
{
$strip = $this->flhm_compress_js;
}
else if ( !empty($token['style']) )
{
$strip = $this->flhm_compress_css;
}
else if ($content == '<!--wp-html-compression no compression-->')
{
$overriding = !$overriding; 
continue;
}
else if ($this->flhm_remove_comments)
{
if (!$overriding && $raw_tag != 'textarea')
{
$content = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content);
}
}
}
else
{
if ($tag == 'pre' || $tag == 'textarea')
{
$raw_tag = $tag;
}
else if ($tag == '/pre' || $tag == '/textarea')
{
$raw_tag = false;
}
else
{
if ($raw_tag || $overriding)
{
$strip = false;
}
else
{
$strip = true; 
$content = preg_replace('/(\s+)(\w++(?<!\baction|\balt|\bcontent|\bsrc)="")/', '$1', $content); 
$content = str_replace(' />', '/>', $content);
}
}
} 
if ($strip)
{
$content = $this->flhm_removeWhiteSpace($content);
}
$html .= $content;
} 
return $html;
} 
public function flhm_parseHTML($html)
{
$this->html = $this->flhm_minifyHTML($html);
if ($this->flhm_info_comment)
{
$this->html .= "\n" . $this->flhm_bottomComment($html, $this->html);
}
}
protected function flhm_removeWhiteSpace($str)
{
$str = str_replace("\t", ' ', $str);
$str = str_replace("\n",  '', $str);
$str = str_replace("\r",  '', $str);
while (stristr($str, '  '))
{
7'1$str = str_replace('  ', ' ', $str);
}   
return $str;
}
}
function flhm_wp_html_compression_finish($html)
{
return new FLHM_HTML_Compression($html);
}
function flhm_wp_html_compression_start()
{
ob_start('flhm_wp_html_compression_finish');
}
add_action('get_header', 'flhm_wp_html_compression_start');

/*!
 https://zuziko.com/tutorials/how-to-minify-html-in-wordpress-without-a-plugin/ by David Green (Note: EFFIN' BRILLIANT!!!) 
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
