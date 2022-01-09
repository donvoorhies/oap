<?php
/*!
 * Plugin Name: Don's Optimization Anthology Plugin
 * Plugin URI: https://github.com/donvoorhies/oap
 * Description: An "anthology" of (IMO) some snazzy functions that I've come across over time, and which I earlier usually hardcoded into 'functions.php' to optimize my Wordpress-installs with; for more details regarding this plugin's different functionalites, as for accessing the latest updated version of this plugin - please go visit: https://github.com/donvoorhies/oap
 * Version (Installed): 1.0.2
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

	
wp_register_script('jquery-core',"https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js",array(),'3.6.0',true);
wp_script_add_data('jquery-core', array( 'module','integrity','crossorigin' ) , array( 'sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==', 'anonymous' ) );
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
$str = str_replace('  ', ' ', $str);
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
