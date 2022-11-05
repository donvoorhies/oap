<?php
/*!
 * Plugin Name: Don's Optimization Anthology Plugin
 * Plugin URI: https://github.com/donvoorhies/oap
 * Description: An "anthology" of (IMO) some snazzy functions that I've come across over time, and which I earlier usually hardcoded into 'functions.php' to optimize my Wordpress-installs with; for more details regarding this plugin's different functionalites, as for accessing the latest updated version of this plugin - please go visit: https://github.com/donvoorhies/oap
 * Version (Installed): 1.0.7
 * Author:  Various Contributors and sources | Compiled and assembled by Don W.Voorhies (See the referenced URLs regarding the specific contributing-credits)...
 * Author URI: https://donvoorhies.github.io/oap/
 * License: Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License (extended with additional conditions)
 */

$CSS_ATF_string='/*(REMOVE THIS STRING BY PASTING THE GENERATED ABOVE-THE-FOLD CSS HERE IN BETWEEN THE APOSTROPHES)*/';

$GA4_string='/*(REMOVE THIS STRING BY PASTING GOOGLE ANALYTICS v.4 MEASUREMENT ID (NOTE: "MEASUREMENT ID"!) HERE IN BETWEEN THE APOSTROPHES)*/';

$GTM_string='/*(REMOVE THIS STRING BY PASTING GOOGLE TAG MANAGER WORKSPACE ID HERE IN BETWEEN THE APOSTROPHES)*/';

if(!is_admin()){
/*! Quick-fix by encapsulating all of the functions in a conditional statement so the back-end content-editing functionalites won't get crippled (as a seen as a "white-page-of-death" as experienced after the Wordpress 6.0-update), when running the admin side code (i.e.: back-end); the switching off here of this plugin ONLY effects the back-end!
*/
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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*! 
Prevents WordPress from testing ssl capability on domain.com/xmlrpc.php?rsd #Speed-optimization 
*/
remove_filter('atom_service_url','atom_service_url_filter');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*! 
Remove version info from head and feeds #Security/Hardening 
*/
function complete_version_removal() {
    return '';
}
add_filter('the_generator', 'complete_version_removal');

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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/* Disbable Self-Pingbacks*/
function wpsites_disable_self_pingbacks( &$links ) {
 foreach ( $links as $l => $link )
 if ( 0 === strpos( $link, get_option( 'home' ) ) )
 unset($links[$l]);
}
add_action( 'pre_ping', 'wpsites_disable_self_pingbacks' );

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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*!
Adds the "data-instant-intensity"-parameter with the value="viewport" to the body-tag; used in connction with the Instant.Page-script by Alexandre Dieulot - TO BE ONLY ACTIVATED IF PAGES WITH A QUERY STRING (a “?”) IN THEIR URL ARE USED 
*/
/*
add_action("wp_footer", "your_theme_adding_extra_attributes"); 

function your_theme_adding_extra_attributes(){
    ?>
    <script>
        let body = document.getElementsByTagName("body");
        body[0].setAttribute("data-instant-intensity", "viewport"); 
</script>
<?php }
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


wp_register_script('jquery-core','https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js',array(),'3.6.1',true);
wp_script_add_data('jquery-core', array( 'module','integrity','crossorigin' ) , array( 'sha512-aVKKRRi/Q/YV+4mjoKBsE4x3H+BkegoM/em46NNlCqNTmUYADjBbeNefNxYV7giUp0VxICtqdrbqU7iVaeZNXA==', 'anonymous' ) );

//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
/*! 
Although, jQuery-Migrate is no longer included/necessary from Wordpress v5.5, I added it anyway, if one should need it for whatever reason!
Uncomment and enqueue, if this should otherwise be the case...
*/ 
/*
wp_enqueue_script('jquery-migrate','"'https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.4.0/jquery-migrate.min.js',array(),'3.4.0',true);
wp_script_add_data( 'jquery-migrate', array( 'module','integrity','crossorigin' ) , array( 'sha512-QDsjSX1mStBIAnNXx31dyvw4wVdHjonOwrkaIhpiIlzqGUCdsI62MwQtHpJF+Npy2SmSlGSROoNWQCOFpqbsOg==', 'anonymous' ) );
*/

wp_enqueue_script('instantpage','https://cdnjs.cloudflare.com/ajax/libs/instant.page/5.1.1/instantpage.min.js',array(),'5.1.1',true);
wp_script_add_data( 'instantpage', array( 'module','integrity','crossorigin' ) , array( 'sha512-caMAESeG5mlQ2CY/HMaLloKyz46yN2IlqBxXsoNOZusid57lNW6jRQeoR1JIC86YWwE1nEylOkc914tDHhUqWA==', 'anonymous' ));

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
*/

function hook_css() {
global $CSS_ATF_string;
echo '<style>'.$CSS_ATF_string.'</style>';
}
add_action('wp_head', 'hook_css');
/*! 
Find the Critical Path CSS-selectors by using the online tool at either: 
https://jonassebastianohlsson.com/criticalpathcssgenerator/
or:
https://purifycss.online/
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
/*! https://kinsta.com/blog/defer-parsing-of-javascript/#functions*/

add_action('wp_print_styles', 'my_deregister_styles', 100);

function defer_parsing_of_js( $url ) {
    if ( is_user_logged_in() ) return $url; //don't break WP Admin
    if ( FALSE === strpos( $url, '.js' ) ) return $url;
    if ( strpos( $url, 'jquery.min.js' ) ) return $url;
    return str_replace( ' src', ' defer src', $url );
}
add_filter( 'script_loader_tag', 'defer_parsing_of_js', 10 );	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

add_action('wp_print_styles', 'my_deregister_styles', 100);

function my_deregister_styles() {
/*!
Insert here the used style-handles in the "wp_deregister_style"-function as shown in the exaplle below; 
use the script on this page for this:https://wpbeaches.com/show-all-loaded-scripts-and-styles-on-a-page-in-wordpress/

EXAMPLE - only the visualization purposes and help:
wp_deregister_style('twenty-twenty-one-style');
wp_deregister_style('twenty-twenty-one-print-style');
wp_deregister_style('wp-block-library');
wp_deregister_style('mihdan-lite-youtube-embed');
wp_deregister_style('wp-dark-mode-frontend');
wp_deregister_style('wp-block-library-theme');
wp_deregister_style('global-styles');
*/
}

function my_GA4_1_js() {
global $GA4_string;
echo '<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id='.$GA4_string.'"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag(\'js\', new Date());
  gtag(\'config\', \''.$GA4_string.'\');
</script>';}
// Add hook for front-end <head></head>
add_action( 'wp_head', 'my_GA4_1_js' );
}


/**
add_action( 'wp_head', 'my_GTM_1_js' );
function my_GTM_1_js(){
global $GTM_string;
echo '<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
\'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,\'script\',\'dataLayer\',\''.$GTM_string.'\');</script>
<!-- End Google Tag Manager -->';
// Add hook for front-end <head></head>
}

add_action('wp_body_open', 'add_code_on_body_open');
function add_code_on_body_open() {
global $GTM_string;
    echo '<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.$GTM_string.'"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->';
}
*/

}

/*! 
Sources:
Remove Google Fonts:
https://stackoverflow.com/questions/29134113/how-to-remove-or-dequeue-google-fonts-in-wordpress-twentyfifteen/45633445#45633445
https://stackoverflow.com/users/839434/payter 


Prevents WordPress from testing ssl capability on domain.com/xmlrpc.php?rsd #Speed-optimization: 
https://wordpress.stackexchange.com/revisions/1769/5 

 
Remove version info from head and feeds #Security/Hardening: 
https://wordpress.stackexchange.com/questions/1567/best-collection-of-code-for-your-functions-php-file
https://wordpress.stackexchange.com/users/472/derek-perkins 


Remove unnecessary header info: 
https://bhoover.com/remove-unnecessary-code-from-your-wordpress-blog-header/


Disabling pingback and trackback notifications:
https://wordpress.stackexchange.com/questions/190346/disabling-pingback-and-trackback-notifications 
 

Disbable Self-Pingbacks:
Source: Brian Jackson: "How To Speed-up wordpress" (PDF)- kinsta.com


Remove extra CSS that the 'Recent Comments' widget injects:
https://wordpress.stackexchange.com/revisions/3816/5 */
(Orignally by: Andrew Ryno)


Remove wp-version number params (scopes) from scripts and styles:
https://artisansweb.net/remove-version-css-js-wordpress/ 

 
Remove WPCF7-code, Google ReCaptcha-code and -badge everywhere sitewide, apart from the page(s) using contact-form-7: 
https://wordpress.org/support/topic/recaptcha-v3-script-is-being-added-to-all-pages/#post-10983560


Custom Scripting to Move CSS and JavaScript from the Head to the Footer:
https://www.namehero.com/startup/how-to-inline-and-defer-css-on-wordpress-without-plugins/


Moving all of the javascript (gathered and enqueued into handlers) down to the bottom of the HTML:
(Use )
https://speedrak.com/blog/how-to-move-javascripts-to-the-footer-in-wordpress/ 


Automatically create meta description from the_content: 
http://wpsnipp.com/index.php/functions-php/automatically-create-meta-description-from-the_content/ 
  
   
Minify HTML-code on the fly, removing line-breaks and white-spaces...
https://zuziko.com/tutorials/how-to-minify-html-in-wordpress-without-a-plugin/ by David Green (Note: EFFIN' BRILLIANT!!!) 

Defering of Javascript:
https://kinsta.com/blog/defer-parsing-of-javascript/#functions

Code used regarding JS-lib's et al:
https://wordpress.stackexchange.com/questions/257317/update-jquery-version
https://www.paulund.co.uk/dequeue-styles-and-scripts-in-wordpress

The Instant.Page script by Alexandre Dieulot:
https://instant.page/

SRI-string adding and usage inspired by: 
https://stackoverflow.com/questions/44827134/wordpress-script-with-integrity-and-crossorigin (See:cherryaustin's comment) 

Google Analytics and Google Tag Manager Code:
https://analytics.google.com/
https://tagmanager.google.com/ 


For development-purposes:
https://wpbeaches.com/show-all-loaded-scripts-and-styles-on-a-page-in-wordpress/

https://www.webperftools.com/blog/how-to-remove-unused-css-in-wordpress/

All other code - forged together by Don W. Voorhies 

(This plugin is made with recycled electrons - No bytes were harmed at any time!)
*/
