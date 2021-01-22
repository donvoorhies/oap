<?php
/*!
 * Plugin Name: Don's Optimization Anthology Plugin
 * Plugin URI: https://github.com/donvoorhies/oap
 * Description: An "anthology" of (IMO) some snazzy functions that I've come across over time, and which I usually hardcode into 'functions.php' to optimize my Wordpress-installs (Including: removing some (unecessary) code and -functions in the header, making the RECAPTCHA- and WPCF7-code function exclusively on the contact page, linking common jQuery-libs to a CDN, removing versions + params (scopes) on URI's, sharpen resized image files, deferring/async js, defering CSS-files and moving js from head to to footer, minimize the used HTML-code)
 * Version: 1.0.0 [RC-1]
 * Author:  Various Contributors nd sources | Compiled and assembled by Don W.Voorhies (See referenced URL for specific contributing-credits)...
 * Author URI: https://donvoorhies.github.io/oap/
 * License: Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License (extended with additional conditions)
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
https://wordpress.stackexchange.com/questions/1567/best-collection-of-code-for-your-functions-php-file
By: https://wordpress.stackexchange.com/users/472/derek-perkins 
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
Remove Google Fonts 
(If you want to use Google Fonts and/or your theme breaks, either comment out - or remove - the following block of code)
*/
add_filter( 'style_loader_src', function($href){
if(strpos($href, "//fonts.googleapis.com/") === false) {
return $href;
}
return false;
});
/*! 
https://stackoverflow.com/questions/29134113/how-to-remove-or-dequeue-google-fonts-in-wordpress-twentyfifteen/45633445#45633445
By: https://stackoverflow.com/users/839434/payter 
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
//remove_action( 'wp_head', 'adjacent_posts_rel_link' );         // for WordPress < 3.0
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' ); // for WordPress >= 3.0
}

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
To find the handler-names, I used the code at: //https://cameronjonesweb.com.au/blog/how-to-find-out-the-handle-for-enqueued-wordpress-scripts/ 
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*! 
Add/queue-up your needed external jQuery-scripts here!
*/
function replace_core_jquery_version(){
if	(!is_admin()){
	
wp_deregister_script('jquery-core');
wp_deregister_script('jquery-migrate');
	
wp_register_script('jquery-core',"https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js",array(),'3.5.1',true);
wp_script_add_data('jquery-core', array( 'integrity', 'crossorigin' ) , array( 'sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==', 'anonymous' ) );

/*! 
Although, jQuery-Migrate is no longer included/necessary from Wordpress v5.5, I added it anyway; weighing in at 4kB it hardly dents the load-times!
*/ 
wp_enqueue_script('jquery-migrate',"https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.3.2/jquery-migrate.min.js",array(),'3.3.2',true);
wp_script_add_data( 'jquery-migrate', array( 'integrity', 'crossorigin' ) , array( 'sha512-3fMsI1vtU2e/tVxZORSEeuMhXnT9By80xlmXlsOku7hNwZSHJjwcOBpmy+uu+fyWwGCLkMvdVbHkeoXdAzBv+w==', 'anonymous' ) );

wp_enqueue_script('instantpage',"https://cdnjs.cloudflare.com/ajax/libs/instant.page/5.1.0/instantpage.min.js",array(),'5.1.0',true);
wp_script_add_data( 'instantpage', array( 'integrity', 'crossorigin' ) , array( 'sha512-1+qUtKoh9XZW7j+6LhRMAyOrgSQKenQ4mluTR+cvxXjP1Z54RxZuzstR/H9kgPXQsVB8IW7DMDFUJpzLjvhGSQ==', 'anonymous' ));
}
}
add_action('wp_enqueue_scripts','replace_core_jquery_version');
/*! 
https://wordpress.stackexchange.com/questions/257317/update-jquery-version
https://www.paulund.co.uk/dequeue-styles-and-scripts-in-wordpress
The fascinating Instant.Page script is by Alexandre Dieulot (https://instant.page/)
SRI-string adding by: https://stackoverflow.com/questions/44827134/wordpress-script-with-integrity-and-crossorigin (See:cherryaustin's comment) 
*/

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
Sharpen scaled image-files - NOTE: JPG-compression is here set to 82% 
*/
function ajx_sharpen_resized_files($resized_file){
$image=wp_load_image($resized_file);
if (!is_resource($image))
return new WP_Error('error_loading_image',$image,$file);

$size=@getimagesize($resized_file);
if ( !$size )
return new WP_Error('invalid_image', __('Could not read image size'), $file);
list($orig_w, $orig_h, $orig_type)=$size;

switch($orig_type){
case IMAGETYPE_JPEG:
$matrix = array(
array(-1,-1,-1),
array(-1,16,-1),
array(-1,-1,-1),
);

$divisor=array_sum(array_map('array_sum',$matrix));
$offset=0; 
imageconvolution($image,$matrix,$divisor,$offset);
imagejpeg($image, $resized_file,apply_filters('jpeg_quality',82,'edit_image'));
break;
case IMAGETYPE_PNG:
return $resized_file;
case IMAGETYPE_GIF:
return $resized_file;
}
return $resized_file;
}   
add_filter('image_make_intermediate_size','ajx_sharpen_resized_files',820);
/*! 
https://wordpress.stackexchange.com/revisions/35526/2
https://www.keycdn.com/support/optimus/optimize-jpeg-quality-in-wordpress 
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
*/

function hook_css() {
?>
<style>
<!--INSERT GENERATED "ABOVE THE FOLD" CRITICAL PATH CSS-STRING HERE - EITHER IMMEDIATELY BELOW THIS LINE OR BY REPLACING THIS LINE WITH THE GENERATED STRING...!-->

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
remove_action('wp_head', 'wp_print_scripts');
remove_action('wp_head', 'wp_print_head_scripts', 9);
remove_action('wp_head', 'wp_enqueue_scripts', 1);

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
Minify Code on the fly 
*/
class FLHM_HTML_Compression
{
protected $flhm_compress_css = true;
protected $flhm_compress_js = true;
protected $flhm_info_comment = false;
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
