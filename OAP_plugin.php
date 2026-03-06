<?php
/**
 * Plugin Name: OAP (Optimization Anthology Plugin)
 * Plugin URI:  https://github.com/donvoorhies/oap
 * Description: WordPress optimization anthology plugin focused on non-overlapping optimizations; designed to run alongside critical-path-css-v2.
 * Version:     2.0.1
 * Author:      Don Voorhies
 * License:     CC BY-NC-SA 4.0
 * Text Domain: wpso
 *
 * OAP (Optimization Anthology Plugin)
 *
 * This plugin provides a collection of WordPress performance and security optimizations.
 *
 * @package OAP
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die('Silence is golden.');
}

// Define constants for cache directory paths and URLs.
if (!defined('WPSO_CACHE_DIR_NAME')) {
    define('WPSO_CACHE_DIR_NAME', 'wpso-cache'); // Cache directory name
}
if (!defined('WPSO_CACHE_DIR')) {
    define('WPSO_CACHE_DIR', WP_CONTENT_DIR . '/' . WPSO_CACHE_DIR_NAME . '/');
}
if (!defined('WPSO_CACHE_URL')) {
    define('WPSO_CACHE_URL', content_url('/' . WPSO_CACHE_DIR_NAME . '/'));
}
if (!defined('WPSO_PLUGIN_VERSION')) {
    define('WPSO_PLUGIN_VERSION', '2.0.2');
}

/**
 * Retrieves the plugin's options using a static cache for performance.
 *
 * Ensures options are loaded only once per request.
 *
 * @since 2.0.0
 * @return array The array of plugin options, or an empty array if not set.
 */
function wpso_get_options() {
    static $wpso_options = null;
    if (null === $wpso_options) {
        $wpso_options = get_option('wpso_options', []);
    }
    return $wpso_options;
}

// --- Admin Area Setup: Menu, Settings, Fields --- //

/**
 * Registers the admin menu page for the plugin.
 *
 * @since 1.0.0
 */
function wpso_admin_menu_setup() {
    add_options_page(
        __('WP Speed Optimizer Settings', 'wpso'),
        __('WP Speed Optimizer', 'wpso'),
        'manage_options',
        'wpso-settings',
        'wpso_render_settings_page'
    );
}
add_action('admin_menu', 'wpso_admin_menu_setup');

/**
 * Registers plugin settings and settings fields.
 *
 * @since 1.0.0
 */
function wpso_admin_init_setup() {
    register_setting('wpso_settings', 'wpso_options', [
        'sanitize_callback' => 'wpso_sanitize_options',
    ]);

    add_settings_section('wpso_main', __('Speedy Settings', 'wpso'), null, 'wpso-settings');

    // Field definitions with descriptions for clarity, especially experimental ones.
    $fields = [
        ['id' => 'minify', 'title' => __('Minify CSS & JS', 'wpso'), 'callback' => 'wpso_field_minify', 'description' => __('Basic minification for CSS & JS. (Prototype)', 'wpso')],
        ['id' => 'combine', 'title' => __('Combine CSS & JS', 'wpso'), 'callback' => 'wpso_field_combine', 'description' => __('Combines multiple CSS/JS files. Requires file caching to be effective. (Prototype)', 'wpso')],
        ['id' => 'heartbeat', 'title' => __('Control Heartbeat API', 'wpso'), 'callback' => 'wpso_field_heartbeat', 'description' => __('Control the WordPress Heartbeat API in the admin area. (Prototype)', 'wpso')],
        ['id' => 'perpage', 'title' => __('Disable Assets Per Page', 'wpso'), 'callback' => 'wpso_field_perpage', 'description' => __('Feature coming soon.', 'wpso')],
        ['id' => 'minify_html', 'title' => __('Minify HTML Output', 'wpso'), 'callback' => 'wpso_field_minify_html', 'description' => __('<strong>Warning (Experimental):</strong> Aggressively removes whitespace from HTML. Can break complex HTML or inline scripts. Test carefully.', 'wpso'), 'is_experimental' => true],
    ];

    foreach ($fields as $field) {
        add_settings_field($field['id'], $field['title'], $field['callback'], 'wpso-settings', 'wpso_main', ['description' => $field['description'] ?? '', 'is_experimental' => $field['is_experimental'] ?? false]);
    }
}
add_action('admin_init', 'wpso_admin_init_setup');

/**
 * Sanitizes plugin options upon saving.
 *
 * @since 1.5.0 (Refactored in 2.0.0)
 * @param array $input The input options array from the settings page.
 * @return array The sanitized options array.
 */
function wpso_sanitize_options($input) {
    $sanitized_input = [];
    $checkboxes = ['minify', 'combine', 'minify_html'];
    foreach ($checkboxes as $cb) {
        $sanitized_input[$cb] = isset($input[$cb]) && $input[$cb] == 1 ? 1 : 0;
    }

    if (isset($input['heartbeat'])) { $sanitized_input['heartbeat'] = in_array($input['heartbeat'], ['normal', 'throttle', 'disable'], true) ? $input['heartbeat'] : 'normal'; }

    // wpso_reset_options_cache(); // Consider if explicit reset of static options cache is needed here.
    return $sanitized_input;
}

/**
 * Detects Google Fonts enqueued on the site.
 * @since 1.0.0
 * @return array List of Google Fonts found, with handle, type, and src.
 */
function wpso_detect_google_fonts() {
    global $wp_styles, $wp_scripts; $fonts = [];
    if (isset($wp_styles->registered)) { foreach ($wp_styles->registered as $h => $s) { if (isset($s->src) && strpos($s->src, 'fonts.googleapis.com')!==false) $fonts[]=['handle'=>$h,'type'=>'style','src'=>$s->src];}}
    if (isset($wp_scripts->registered)) { foreach ($wp_scripts->registered as $h => $s) { if (isset($s->src) && strpos($s->src, 'fonts.googleapis.com')!==false) $fonts[]=['handle'=>$h,'type'=>'script','src'=>$s->src];}}
    return $fonts;
}

/**
 * Renders the main settings page for the plugin.
 * @since 1.0.0
 */
function wpso_render_settings_page() {
    echo '<div class="wrap"><h1>' . esc_html__('WP Speed Optimizer // prototype', 'wpso') . '</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('wpso_settings');
    
    $google_fonts = wpso_detect_google_fonts();
    if (!empty($google_fonts)) {
        echo '<div style="background:#fffbe5;border:1px solid #ffe066;padding:10px;margin-bottom:15px;"><strong>' . esc_html__('Detected Google Fonts:', 'wpso') . '</strong><ul style="margin:0 0 0 20px;">';
        foreach ($google_fonts as $f) { echo '<li>' . esc_html($f['handle']) . ' <span style="color:#888">(' . esc_html($f['type']) . ')</span> &rarr; <code>' . esc_html($f['src']) . '</code></li>'; }
        echo '</ul></div>';
    } else {
        echo '<div style="background:#eaffea;border:1px solid #b2f2bb;padding:10px;margin-bottom:15px;">' . esc_html__('No Google Fonts detected.', 'wpso') . '</div>';
    }
    echo '<div style="background:#ffffe0;border:1px solid #ffd700;padding:10px;margin-bottom:15px;"><strong>' . esc_html__('Important:', 'wpso') . '</strong> ' . esc_html__('Some features are experimental. Always test thoroughly after enabling optimizations.', 'wpso') . '</div>';
    echo '<p>' . esc_html__('Toggle and configure speed optimizations.', 'wpso') . '</p>';
    echo '<h2>' . esc_html__('Global Optimizations', 'wpso') . '</h2>';
    do_settings_sections('wpso-settings');
    submit_button();
    echo '</form></div>';
}

// Standard field renderers with improved readability and descriptions via $args
function wpso_field_minify($args) { $opts = wpso_get_options(); echo '<input type="checkbox" name="wpso_options[minify]" value="1" '.checked(1, $opts['minify'] ?? 0, false).' /> '.esc_html__('Minify CSS & JS.', 'wpso').'<p><small>'.esc_html($args['description']).'</small></p>'; }
function wpso_field_combine($args) { $opts = wpso_get_options(); echo '<input type="checkbox" name="wpso_options[combine]" value="1" '.checked(1, $opts['combine'] ?? 0, false).' /> '.esc_html__('Combine CSS & JS.', 'wpso').'<p><small>'.esc_html($args['description']).'</small></p>'; }
function wpso_field_heartbeat($args) { $opts = wpso_get_options(); echo '<select name="wpso_options[heartbeat]"><option value="normal" '.selected($opts['heartbeat'] ?? 'normal', 'normal', false).'>'.esc_html__('Normal', 'wpso').'</option><option value="throttle" '.selected($opts['heartbeat'] ?? 'normal', 'throttle', false).'>'.esc_html__('Throttle (60s)', 'wpso').'</option><option value="disable" '.selected($opts['heartbeat'] ?? 'normal', 'disable', false).'>'.esc_html__('Disable in Admin', 'wpso').'</option></select><p><small>'.esc_html($args['description']).'</small></p>'; }
function wpso_field_perpage($args) { echo '<em>'.esc_html__('Coming soon: UI for per-page asset disabling!', 'wpso').'</em><p><small>'.esc_html($args['description']).'</small></p>'; }
function wpso_field_minify_html($args) { $opts = wpso_get_options(); echo '<input type="checkbox" name="wpso_options[minify_html]" value="1" '.checked(1, $opts['minify_html'] ?? 0, false).' /> '.esc_html__('Minify HTML output.', 'wpso').'<p><small>'.wp_kses_post($args['description']).'</small></p>'; }


// --- Frontend Optimization Registration --- //

/**
 * Main registration function for frontend optimizations.
 *
 * Hooks various optimization actions if not in admin or customize preview.
 * @since 1.0.0
 */
add_action('wp', function() {
    if (!is_admin() && !is_customize_preview()) {
        wpso_register_optimizations();
    }
});

// (Helper functions: _wpso_remove_version_query_arg, wpso_setup_cache_dir, _wpso_generate_cache_key - remain as before, ensure PHPDocs added if missing)
/**
 * Removes version query arguments from script and style URLs.
 * @since 1.2.0
 * @param string $src The source URL.
 * @return string The URL without the 'ver' query argument.
 */
function _wpso_remove_version_query_arg($src) {
    if (!is_string($src) || $src === '') {
        return $src;
    }
    $src = remove_query_arg(['ver', 'version', 'wpver', 'wpv'], $src);
    return $src;
}

/**
 * Sets up the cache directory, ensuring it exists and is writable.
 * Creates an index.html and .htaccess file for security.
 * @since 2.0.0
 * @return bool True if cache directory is set up and writable, false otherwise.
 */
function wpso_setup_cache_dir() { $cache_dir = WPSO_CACHE_DIR; if (!is_dir($cache_dir)) { if (!wp_mkdir_p($cache_dir)) { error_log('WP Speed Optimizer: Cache directory could not be created: ' . $cache_dir); return false; } } if (!is_writable($cache_dir)) { error_log('WP Speed Optimizer: Cache directory is not writable: ' . $cache_dir); return false; } if (!file_exists($cache_dir.'index.html')) { @file_put_contents($cache_dir.'index.html', '<!-- Silence is golden -->'); } if (strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'Apache')!==false || strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'LiteSpeed')!==false) { $ht_content="<Files *.php>\ndeny from all\n</Files>\nOptions -Indexes\n"; $ht_file=$cache_dir.'.htaccess'; if(!file_exists($ht_file)||@file_get_contents($ht_file)!==$ht_content){@file_put_contents($ht_file,$ht_content);}} return true; }

/**
 * Generates a cache key for combined assets.
 * @since 2.0.0
 * @param array  $assets_data Array of asset data (handle, version, path).
 * @param string $type        Type of asset ('css' or 'js').
 * @return string MD5 hash representing the cache key.
 */
function _wpso_generate_cache_key($assets_data, $type = 'css') { $key_string = ''; foreach ($assets_data as $h => $d) { $key_string.=$h.($d['ver']??'0'); if(isset($d['path'])&&file_exists($d['path'])){$key_string.=filemtime($d['path']);}} return md5($key_string.$type.get_bloginfo('version').WPSO_PLUGIN_VERSION);}

/**
 * Main function to register and apply frontend optimizations.
 *
 * This function is hooked into 'wp' and checks various plugin options
 * to apply corresponding optimizations.
 *
 * @since 1.0.0 (Heavily refactored in 2.0.0+)
 */
function wpso_register_optimizations() {
    $opts = wpso_get_options();

    add_action('wp_head', 'wpso_output_push_prompt_contrast_css', 99);

    // --- Core Optimizations (Emoji, oEmbed, Version Strings, Generator Tag) ---
    // These are generally considered safe and foundational.
    add_action('init', function() { remove_action('rest_api_init', 'wp_oembed_register_route'); remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10); remove_action('wp_head', 'wp_oembed_add_discovery_links'); remove_action('wp_head', 'wp_oembed_add_host_js'); remove_filter('embed_handler_html', '__return_false'); add_filter('embed_oembed_discover', '__return_false'); remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10); });
    add_filter('script_loader_src', '_wpso_remove_version_query_arg', 15); 
    add_filter('style_loader_src', '_wpso_remove_version_query_arg', 15); 
    remove_action('wp_head', 'wp_generator');
    
    // --- Other General Header/Feed Cleanup ---
    add_filter('the_generator', '__return_empty_string'); 
    add_filter('https_local_ssl_verify', '__return_false');
    remove_action('wp_head', 'rsd_link'); remove_action('wp_head', 'wlwmanifest_link'); remove_action('wp_head', 'wp_shortlink_wp_head'); remove_action('wp_head', 'rest_output_link_wp_head'); remove_action('wp_head', 'feed_links_extra', 3); remove_action('wp_head', 'feed_links', 2); remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
    add_filter('xmlrpc_methods', function($methods) { unset($methods['pingback.ping'], $methods['pingback.extensions.getPingbacks']); return $methods; }); 
    add_filter('pings_open', '__return_false', 20, 2);
    add_filter('wp_headers', function($headers) { unset($headers['X-Pingback']); return $headers; }); 
    add_action('pre_ping', function(&$links) { $links = array(); }); // Disable pingbacks globally
    add_action('pre_ping', function(&$links) { foreach ($links as $l => $link) { if (0 === strpos($link, get_option('home'))) unset($links[$l]); } }); // Disable self-pingbacks
    add_action('widgets_init', function() { global $wp_widget_factory; if (isset($wp_widget_factory->widgets['WP_Widget_Recent_Comments']) && method_exists($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style')) { remove_action('wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style')); } });

    // --- Minify & Combine CSS/JS with Caching ---
    // This is a complex operation, hooked late to process enqueued assets.
    add_action('wp_enqueue_scripts', function() use ($opts) {
        if (is_admin() || empty($opts['combine'])) { return; } // Combine pipeline is disabled unless explicitly enabled in settings

        global $wp_styles, $wp_scripts; 
        static $done_css_combine = false; // Renamed for clarity
        static $done_js_combine = false;  // Renamed for clarity
        
        $cache_dir_ok = wpso_setup_cache_dir(); 

        // --- Process CSS --- //
        if (!$done_css_combine && $wp_styles && !empty($wp_styles->queue)) {
            $skip_css = ['admin-bar', 'dashicons', 'wp-admin']; // Base CSS handles to skip
            $skip_handles_css = apply_filters('wpso_combine_minify_skip_handles_css', $skip_css);
            
            $assets_data_css = []; $handles_to_combine_css = [];
            foreach ($wp_styles->queue as $handle) {
                if (in_array($handle, $skip_handles_css, true)) continue;
                if (isset($wp_styles->registered['admin-bar']->deps) && is_array($wp_styles->registered['admin-bar']->deps) && in_array($handle, $wp_styles->registered['admin-bar']->deps, true)) continue;
                $style_obj = $wp_styles->registered[$handle] ?? null; if (!$style_obj || !$style_obj->src || strpos($style_obj->src, '.css') === false) continue;
                if (wpso_should_skip_css_combine($style_obj->src, $handle)) continue;
                $resolved_path = wpso_resolve_url($style_obj->src);
                $assets_data_css[$handle] = ['ver' => $style_obj->ver, 'path' => (filter_var($resolved_path, FILTER_VALIDATE_URL) ? null : $resolved_path), 'src' => $style_obj->src];
                $handles_to_combine_css[] = $handle;
            }

            if (!empty($handles_to_combine_css)) {
                $cache_key_css = _wpso_generate_cache_key($assets_data_css, 'css'); 
                $cache_file_name_css = 'wpso-combined-' . $cache_key_css . '.css';
                $cache_file_path_css = WPSO_CACHE_DIR . $cache_file_name_css; 
                $cache_file_url_css  = WPSO_CACHE_URL . $cache_file_name_css;

                if ($cache_dir_ok && file_exists($cache_file_path_css)) { // Cache Hit
                    wp_enqueue_style('wpso-cached-styles-' . $cache_key_css, $cache_file_url_css, [], null);
                    foreach ($handles_to_combine_css as $h) wp_dequeue_style($h);
                } else { // Cache Miss
                    $combined_css_content = ''; $fetch_failed_css = false;
                    foreach ($handles_to_combine_css as $h) {
                        $s_obj = $wp_styles->registered[$h]; $content = _wpso_fetch_asset_content(wpso_resolve_url($s_obj->src));
                        if ($content) { $combined_css_content .= $content . "\n"; } else { $fetch_failed_css = true; error_log("WPSO: Failed CSS fetch: {$h}, src: {$s_obj->src}"); continue; }
                    }
                    if ($combined_css_content && !$fetch_failed_css) {
                        if (!empty($opts['minify'])) $combined_css_content = wpso_minify_css($combined_css_content);
                        if ($cache_dir_ok && @file_put_contents($cache_file_path_css, $combined_css_content)) {
                            wp_enqueue_style('wpso-cached-styles-' . $cache_key_css, $cache_file_url_css, [], null); foreach ($handles_to_combine_css as $h) wp_dequeue_style($h);
                        } else { if (!$cache_dir_ok) error_log('WPSO: CSS cache dir issue.'); else error_log('WPSO: Failed CSS cache write: '.$cache_file_path_css); add_action('wp_head', function() use ($combined_css_content) { echo '<style id="wpso-combined-css-fallback">'.$combined_css_content.'</style>'; }, 99); foreach ($handles_to_combine_css as $h) wp_dequeue_style($h); }
                    } elseif ($combined_css_content && $fetch_failed_css) { add_action('wp_head', function() use ($combined_css_content) { echo '<style id="wpso-combined-css-partial-fallback">'.$combined_css_content.'</style>'; }, 99); /* Potentially dequeue only successful ones */ }
                }
            } $done_css_combine = true;
        }

        // --- Process JS --- //
        $enable_js_combine = (bool) apply_filters('wpso_enable_js_combine', false, $opts);
        if (!$done_js_combine && $enable_js_combine && $wp_scripts && !empty($wp_scripts->queue)) {
            $skip_js = ['admin-bar', 'dashicons', 'wp-admin', 'jquery', 'jquery-core', 'jquery-migrate']; 
            $skip_handles_js = apply_filters('wpso_combine_minify_skip_handles_js', $skip_js);
            $assets_data_js = []; $handles_to_combine_js = [];
            foreach ($wp_scripts->queue as $handle) {
                if (in_array($handle, $skip_handles_js, true)) continue;
                if (isset($wp_scripts->registered['admin-bar']->deps) && is_array($wp_scripts->registered['admin-bar']->deps) && in_array($handle, $wp_scripts->registered['admin-bar']->deps, true)) continue;
                $script_obj = $wp_scripts->registered[$handle] ?? null; if (!$script_obj || !$script_obj->src || strpos($script_obj->src, '.js') === false) continue;
                $script_group = (int) ($script_obj->extra['group'] ?? 0);
                if ($script_group !== 1) continue;
                if (($wp_scripts->get_data($handle, 'type') ?? '') === 'module') continue;
                $script_deps = is_array($script_obj->deps ?? null) ? $script_obj->deps : [];
                if (array_intersect($script_deps, ['jquery', 'jquery-core', 'jquery-migrate'])) continue;
                $has_inline_payload = !empty($script_obj->extra['before']) || !empty($script_obj->extra['after']) || !empty($script_obj->extra['data']);
                if ($has_inline_payload) continue;
                if (wpso_should_skip_js_combine($script_obj->src, $handle)) continue;
                $resolved_path = wpso_resolve_url($script_obj->src);
                $assets_data_js[$handle] = ['ver' => $script_obj->ver, 'path' => (filter_var($resolved_path, FILTER_VALIDATE_URL) ? null : $resolved_path), 'src' => $script_obj->src];
                $handles_to_combine_js[] = $handle;
            }
            if (!empty($handles_to_combine_js)) {
                $cache_key_js = _wpso_generate_cache_key($assets_data_js, 'js'); $cache_file_name_js = 'wpso-combined-' . $cache_key_js . '.js';
                $cache_file_path_js = WPSO_CACHE_DIR . $cache_file_name_js; $cache_file_url_js  = WPSO_CACHE_URL . $cache_file_name_js;
                if ($cache_dir_ok && file_exists($cache_file_path_js)) { // Cache Hit
                    wp_enqueue_script('wpso-cached-scripts-' . $cache_key_js, $cache_file_url_js, [], null, true);
                    foreach ($handles_to_combine_js as $h) wp_dequeue_script($h);
                } else { // Cache Miss
                    $combined_js_content = ''; $fetch_failed_js = false;
                    foreach ($handles_to_combine_js as $h) {
                        $s_obj = $wp_scripts->registered[$h]; $content = _wpso_fetch_asset_content(wpso_resolve_url($s_obj->src));
                        if ($content) { $combined_js_content .= rtrim($content, ';') . ";\n"; } else { $fetch_failed_js = true; error_log("WPSO: Failed JS fetch: {$h}, src: {$s_obj->src}"); continue; }
                    }
                    if ($combined_js_content && !$fetch_failed_js) {
                        if (!empty($opts['minify'])) $combined_js_content = wpso_minify_js($combined_js_content);
                        if ($cache_dir_ok && @file_put_contents($cache_file_path_js, $combined_js_content)) {
                            wp_enqueue_script('wpso-cached-scripts-' . $cache_key_js, $cache_file_url_js, [], null, true); foreach ($handles_to_combine_js as $h) wp_dequeue_script($h);
                        } else { if (!$cache_dir_ok) error_log('WPSO: JS cache dir issue.'); else error_log('WPSO: Failed JS cache write: '.$cache_file_path_js); add_action('wp_footer', function() use ($combined_js_content) { echo '<script id="wpso-combined-js-fallback">'.$combined_js_content.'</script>'; }, 99); foreach ($handles_to_combine_js as $h) wp_dequeue_script($h); }
                    } elseif ($combined_js_content && $fetch_failed_js) { add_action('wp_footer', function() use ($combined_js_content) { echo '<script id="wpso-combined-js-partial-fallback">'.$combined_js_content.'</script>'; }, 99); /* Potentially dequeue only successful ones */ }
                }
            } $done_js_combine = true;
        }
    }, 999); // Priority 999: Runs after most scripts are enqueued.

    // Heartbeat
    add_filter('heartbeat_settings', function($settings) use ($opts) { if(!is_admin())return $settings; $hb_setting=$opts['heartbeat']??'normal'; if($hb_setting==='throttle')$settings['interval']=60; elseif($hb_setting==='disable')$settings['interval']=600; return $settings;});
    add_action('init', function() use ($opts) { if(is_admin()&&!empty($opts['heartbeat'])&&$opts['heartbeat']==='disable')wp_deregister_script('heartbeat');});

    // --- Experimental Feature: Minify HTML Output ---
    $enable_html_minify = (bool) apply_filters('wpso_enable_html_minify', false, $opts);
    if(!empty($opts['minify_html']) && $enable_html_minify){
        add_action('template_redirect',function()use($opts){
            if(is_admin()||is_customize_preview()||defined('XMLRPC_REQUEST')||defined('REST_REQUEST')||defined('DOING_AJAX')||defined('DOING_CRON')||is_feed()||is_robots()||is_embed()||is_404()||is_search())return;
            $handlers=ob_list_handlers(); if(in_array('wpso_minify_html_buffer',$handlers,true))return;
            if(ob_get_level()>0&&!empty($handlers)){error_log('WPSO: HTML minify OB conflict warning. Active: '.implode(',',$handlers));}
            ob_start('wpso_minify_html_buffer');
        },0); // Priority 0: Very early on template_redirect.
    }

} // End of wpso_register_optimizations


// --- Helper Functions (Globally Scoped) ---

/**
 * Fetches content of an asset (local or remote).
 * @since 2.0.0
 * @param string $resolved_src Resolved URL or file path of the asset.
 * @return string Asset content, or empty string on failure.
 */
function _wpso_fetch_asset_content($resolved_src) { if (!$resolved_src) return ''; if (filter_var($resolved_src, FILTER_VALIDATE_URL)) { $response = wp_remote_get($resolved_src); if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) { return wp_remote_retrieve_body($response); } else { error_log('WP Speed Optimizer: Failed to fetch remote asset: ' . $resolved_src . ' - ' . (is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response))); return ''; } } elseif (is_string($resolved_src) && file_exists($resolved_src) && is_readable($resolved_src)) { $content = @file_get_contents($resolved_src); if ($content !== false) { return $content; } else { error_log('WP Speed Optimizer: Failed to read local asset: ' . $resolved_src); return ''; } } error_log('WP Speed Optimizer: Invalid asset source for _wpso_fetch_asset_content: ' . print_r($resolved_src, true)); return ''; }

/**
 * Resolves a URL to an absolute file path if local, or validates it if remote.
 * @since 2.0.0
 * @param string $src Asset source URL/path.
 * @return string Resolved file path, validated URL, or empty string.
 */
function wpso_resolve_url($src) { if(empty($src)||!is_string($src))return ''; if(strpos($src,'http://')===0||strpos($src,'https://')===0){return filter_var($src,FILTER_VALIDATE_URL)?$src:'';} if(strpos($src,'//')===0){$full_url=(is_ssl()?'https:':'http:').$src; return filter_var($full_url,FILTER_VALIDATE_URL)?$full_url:'';} $file_path=''; $norm_src=wp_normalize_path($src); if(strpos($norm_src,wp_normalize_path(ABSPATH))===0&&file_exists($norm_src)){return $norm_src;} $site_url=wp_normalize_path(site_url('/')); $content_url=wp_normalize_path(content_url()); $plugins_url=wp_normalize_path(plugins_url()); $tpl_uri=wp_normalize_path(get_template_directory_uri()); $st_uri=wp_normalize_path(get_stylesheet_directory_uri()); if(strpos($norm_src,$content_url)===0){$file_path=wp_normalize_path(WP_CONTENT_DIR).str_replace($content_url,'',$norm_src);}elseif(strpos($norm_src,$plugins_url)===0){$file_path=wp_normalize_path(WP_PLUGIN_DIR).str_replace($plugins_url,'',$norm_src);}elseif($st_uri!==$tpl_uri&&strpos($norm_src,$st_uri)===0){$file_path=wp_normalize_path(get_stylesheet_directory()).str_replace($st_uri,'',$norm_src);}elseif(strpos($norm_src,$tpl_uri)===0){$file_path=wp_normalize_path(get_template_directory()).str_replace($tpl_uri,'',$norm_src);}elseif(strpos($norm_src,'/')===0){$file_path=wp_normalize_path(ABSPATH).ltrim($norm_src,'/');}elseif(strpos($norm_src,$site_url)===0){$rel_path=str_replace($site_url,'',$norm_src); $file_path=wp_normalize_path(ABSPATH).ltrim($rel_path,'/');} if($file_path){$norm_file_path=wp_normalize_path($file_path); if(file_exists($norm_file_path)&&strpos(wp_normalize_path(realpath($norm_file_path)),wp_normalize_path(ABSPATH))===0){return $norm_file_path;}} return filter_var($src,FILTER_VALIDATE_URL)?$src:'';}

/**
 * Returns true if a script should not be JS-combined.
 *
 * Some third-party scripts (e.g., Cloudflare Turnstile/challenge scripts)
 * depend on being loaded as a standalone script tag and can fail when bundled.
 *
 * @since 2.0.1
 * @param string $src    Script source URL.
 * @param string $handle Script handle.
 * @return bool
 */
function wpso_should_skip_js_combine($src, $handle = '') {
    $haystack = strtolower((string) $handle . ' ' . (string) $src);

    $patterns = [
        'challenges.cloudflare.com',
        'turnstile',
        'cf-chl',
        'recaptcha',
        'hcaptcha',
        'onesignal',
        'webpush',
        'push-notification',
        'notification',
    ];

    $patterns = apply_filters('wpso_skip_js_combine_patterns', $patterns, $src, $handle);

    foreach ($patterns as $pattern) {
        if (strpos($haystack, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Returns true if a stylesheet should not be CSS-combined.
 *
 * @since 2.0.2
 * @param string $src    Style source URL.
 * @param string $handle Style handle.
 * @return bool
 */
function wpso_should_skip_css_combine($src, $handle = '') {
    $haystack = strtolower((string) $handle . ' ' . (string) $src);

    $patterns = [
        'onesignal',
        'webpush',
        'push-notification',
        'notification',
        'recaptcha',
        'hcaptcha',
    ];

    $patterns = apply_filters('wpso_skip_css_combine_patterns', $patterns, $src, $handle);

    foreach ($patterns as $pattern) {
        if (strpos($haystack, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Basic CSS minifier.
 * @since 1.0.0 (Refined in 2.0.0+)
 * @param string $css CSS content.
 * @return string Minified CSS content.
 */
function wpso_minify_css($css) { $css=preg_replace('/\/\*(?![\!]).*?\*\//s','',$css); $css=preg_replace('/\s\s+/',' ',$css); $css=preg_replace('/\s*([{};,>+~])\s*/','$1',$css); $css=preg_replace('/\s+:/',':',$css); $css=preg_replace('/:\s+(?!(?:data:|url\(|alpha\(|progid:DXImageTransform))/i',':',$css); $css=preg_replace('/;\s*}/','}',$css); $css=preg_replace('/[^{}]+\{\s*\}/','',$css); $css=preg_replace('/(:|\s)0(?:px|em|%|in|cm|mm|pc|pt|ex|ch|rem|vw|vh|vmin|vmax)\b/i','${1}0',$css); $css=preg_replace('/#([0-9a-fA-F])\1([0-9a-fA-F])\2([0-9a-fA-F])\3\b/i','#$1$2$3',$css); return trim($css);}

/**
 * Basic JavaScript minifier.
 * @since 1.0.0 (Refined in 2.0.0+)
 * @param string $js JavaScript content.
 * @return string Minified JavaScript content.
 */
function wpso_minify_js($js) { $js=preg_replace('/\/\*(?![\!])(?:[^*]|\*(?!\/))*?\*\//s','',$js); $js=preg_replace('/(?<![:\'"`\\\])\/\/.*$/m','',$js); $js=preg_replace('/[ \t\r\n]+/',' ',$js); $js=preg_replace('/\s*([=,;])\s*/','$1',$js); return trim($js);}

/**
 * HTML minification buffer callback.
 *
 * Preserves content of textarea, pre, script, and style tags.
 * Removes comments (except IE conditionals and preserved tags).
 * Collapses whitespace.
 *
 * @since 1.8.0 (Refined in 2.0.0+)
 * @param string $html HTML content from output buffer.
 * @return string Minified HTML content.
 */
function wpso_minify_html_buffer($html) {
    if(empty($html)||!is_string($html))return $html;
    // Conditional checks for when not to minify
    if(is_admin()||is_feed()||is_robots()||is_customize_preview()||is_embed()||is_404()||is_search()||(defined('REST_REQUEST')&&REST_REQUEST)||(defined('XMLRPC_REQUEST')&&XMLRPC_REQUEST)||(defined('DOING_AJAX')&&DOING_AJAX)||(defined('DOING_CRON')&&DOING_CRON))return $html;
    
    // Check Content-Type header
    $headers=headers_list(); $is_html=false;
    foreach($headers as $h){if(stripos($h,'Content-Type:')===0&&stripos($h,'text/html')!==false){$is_html=true;break;}}
    if(!$is_html&&!preg_match('/<html\b[^>]*>/i',substr($html,0,1000)))return $html; // Basic check if headers not sent/available

    // Preserve content of specific tags
    $preserved_tags_content=[];
    if(preg_match_all('#<(textarea|pre|script|style)(.*?)>(.*?)</\1>#is',$html,$matches,PREG_SET_ORDER)){
        foreach($matches as $i=>$m){
            // Using a more unique placeholder with md5 hash of content
            $placeholder='<!--WPSO_PRESERVED_TAG_'.$i.'_'.md5($m[0]).'-->';
            $preserved_tags_content[$placeholder]=$m[0];
            $html=str_replace($m[0],$placeholder,$html);
        }
    }
    
    // Minification steps:
    $html=preg_replace('/>\s+</','><',$html); // Remove whitespace between tags
    $html=preg_replace('/^\s+|\s+$/m','',$html); // Trim leading/trailing whitespace from lines
    $html=preg_replace('/\s{2,}/',' ',$html); // Collapse multiple spaces into one
    $html=str_replace(["\r","\n","\t"],' ',$html); // Replace newlines and tabs with a single space
    $html=preg_replace('/\s{2,}/',' ',$html); // Collapse multiple spaces again after newline replacement
    // Remove HTML comments, preserving IE conditionals and our placeholders
    $html=preg_replace('/<!--(?!\s*(?:if lte IE|WPSO_PRESERVED_TAG_))[^\[>](?:(?!-->).)*-->/sU','',$html);

    // Restore preserved tags
    if(!empty($preserved_tags_content)){
        $html=str_replace(array_keys($preserved_tags_content),array_values($preserved_tags_content),$html);
    }
    
    return trim($html);
}

/**
 * Outputs lightweight contrast fixes for common push-notification prompts.
 *
 * @since 2.0.2
 * @return void
 */
function wpso_output_push_prompt_contrast_css() {
    echo '<style id="wpso-push-prompt-contrast">';
    echo '.pn-wrapper,';
    echo '.onesignal-slidedown-container,';
    echo '.onesignal-popover-container,';
    echo '#onesignal-slidedown-container,';
    echo '#onesignal-popover-container,';
    echo '.webpushr-prompt-wrapper,';
    echo '.pushnotification-prompt,';
    echo '[class*="push-notification" i],';
    echo '[id*="push-notification" i],';
    echo '[class*="notification-prompt" i],';
    echo '[id*="notification-prompt" i]';
    echo '{color:#111 !important;}';

    echo '.pn-wrapper{background:#fff !important;color:#111 !important;}';
    echo '.pn-wrapper *{color:#111 !important;}';

    echo '.pn-wrapper button,';
    echo '.onesignal-slidedown-container button,';
    echo '.onesignal-popover-container button,';
    echo '#onesignal-slidedown-container button,';
    echo '#onesignal-popover-container button,';
    echo '.webpushr-prompt-wrapper button,';
    echo '.pushnotification-prompt button,';
    echo '[class*="push-notification" i] button,';
    echo '[id*="push-notification" i] button,';
    echo '[class*="notification-prompt" i] button,';
    echo '[id*="notification-prompt" i] button';
    echo '{color:#111 !important;}';

    echo '.onesignal-slidedown-container .onesignal-popover-button.primary,';
    echo '.onesignal-popover-container .onesignal-popover-button.primary,';
    echo '#onesignal-slidedown-container .onesignal-popover-button.primary,';
    echo '#onesignal-popover-container .onesignal-popover-button.primary';
    echo '{color:#fff !important;background-color:#005fb8 !important;}';

    echo '</style>';
}

/*! 
Sources (Past & previous):
... (rest of the comment block) ...
*/
