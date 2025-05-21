<?php
/**
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
        ['id' => 'preconnect', 'title' => __('Preconnect Origins', 'wpso'), 'callback' => 'wpso_field_preconnect', 'description' => __('Enter domains to preconnect to, comma-separated (e.g., https://fonts.gstatic.com).', 'wpso')],
        ['id' => 'preload', 'title' => __('Preload Critical Assets', 'wpso'), 'callback' => 'wpso_field_preload', 'description' => __('Enter full paths or URLs to assets to preload, comma-separated. Use with care.', 'wpso')],
        ['id' => 'heartbeat', 'title' => __('Control Heartbeat API', 'wpso'), 'callback' => 'wpso_field_heartbeat', 'description' => __('Control the WordPress Heartbeat API in the admin area. (Prototype)', 'wpso')],
        ['id' => 'perpage', 'title' => __('Disable Assets Per Page', 'wpso'), 'callback' => 'wpso_field_perpage', 'description' => __('Feature coming soon.', 'wpso')],
        ['id' => 'critical_css', 'title' => __('Global Critical CSS', 'wpso'), 'callback' => 'wpso_field_critical_css', 'description' => __('This CSS will be inlined on all pages unless overridden by per-page critical CSS.', 'wpso')],
        ['id' => 'critical_css_autogenerate', 'title' => __('Auto-generate Critical CSS', 'wpso'), 'callback' => 'wpso_field_critical_css_autogenerate', 'description' => __('Enable auto-generation of critical CSS for posts/pages on save (requires server-side tool).', 'wpso')],
        ['id' => 'move_css_footer', 'title' => __('Move CSS to Footer', 'wpso'), 'callback' => 'wpso_field_move_css_footer', 'description' => __('<strong>Warning (Experimental):</strong> Moves most CSS files to the footer. Can cause Flash of Unstyled Content (FOUC) and break styles. Test thoroughly. Use with Critical CSS.', 'wpso'), 'is_experimental' => true],
        ['id' => 'minify_html', 'title' => __('Minify HTML Output', 'wpso'), 'callback' => 'wpso_field_minify_html', 'description' => __('<strong>Warning (Experimental):</strong> Aggressively removes whitespace from HTML. Can break complex HTML or inline scripts. Test carefully.', 'wpso'), 'is_experimental' => true],
        ['id' => 'move_js_footer', 'title' => __('Move JavaScript to Footer', 'wpso'), 'callback' => 'wpso_field_move_js_footer', 'description' => __('<strong>Warning (Experimental):</strong> Moves most JavaScript to the footer. Can break plugins/themes if scripts expect to be in <code>&lt;head&gt;</code>. Essential scripts (like jQuery) are typically excluded. Test extensively.', 'wpso'), 'is_experimental' => true],
        ['id' => 'allow_google_fonts', 'title' => __('Allow Google Fonts', 'wpso'), 'callback' => 'wpso_field_allow_google_fonts', 'description' => __('Uncheck to attempt removal of enqueued Google Fonts for privacy/speed.', 'wpso')],
    ];

    foreach ($fields as $field) {
        add_settings_field($field['id'], $field['title'], $field['callback'], 'wpso-settings', 'wpso_main', ['description' => $field['description'] ?? '', 'is_experimental' => $field['is_experimental'] ?? false]);
    }
}
add_action('admin_init', 'wpso_admin_init_setup');

/**
 * Sanitizes plugin options upon saving.
 *
 * Clears critical tool status transient to force a re-check.
 *
 * @since 1.5.0 (Refactored in 2.0.0)
 * @param array $input The input options array from the settings page.
 * @return array The sanitized options array.
 */
function wpso_sanitize_options($input) {
    $sanitized_input = [];
    $checkboxes = ['minify', 'combine', 'move_css_footer', 'minify_html', 'allow_google_fonts', 'move_js_footer', 'critical_css_autogenerate'];
    foreach ($checkboxes as $cb) {
        $sanitized_input[$cb] = isset($input[$cb]) && $input[$cb] == 1 ? 1 : 0;
    }

    if (isset($input['preconnect'])) { $sanitized_input['preconnect'] = sanitize_text_field($input['preconnect']); }
    if (isset($input['preload'])) { $sanitized_input['preload'] = sanitize_text_field($input['preload']); } // Assuming comma-separated list of URLs/paths
    if (isset($input['heartbeat'])) { $sanitized_input['heartbeat'] = in_array($input['heartbeat'], ['normal', 'throttle', 'disable'], true) ? $input['heartbeat'] : 'normal'; }
    
    if (isset($input['critical_css'])) {
        $sanitized_input['critical_css'] = wp_kses_post(trim($input['critical_css']));
    }

    delete_transient('wpso_critical_tool_status');
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

/**
 * Renders the checkbox field for critical CSS auto-generation.
 * @since 2.0.0
 */
function wpso_field_critical_css_autogenerate() {
    $opts = wpso_get_options();
    echo '<input type="checkbox" name="wpso_options[critical_css_autogenerate]" value="1" ' . checked(1, $opts['critical_css_autogenerate'] ?? 0, false) . ' /> ';
    echo esc_html__('Enable auto-generation of critical CSS on save.', 'wpso');
    
    $critical_status = _wpso_get_critical_command_status();
    $status_message = '';
    switch ($critical_status['status']) {
        case 'available': $status_message = '<span style="color:green;">' . sprintf(esc_html__('Tool available: %s', 'wpso'), '<code>' . esc_html($critical_status['path']) . '</code>') . '</span>'; break;
        case 'no_command': $status_message = '<span style="color:red;">' . esc_html__('Tool (\'critical\') not found.', 'wpso') . '</span>'; break;
        case 'no_shell_exec': $status_message = '<span style="color:red;">' . esc_html__('PHP functions `shell_exec` or `exec` are disabled.', 'wpso') . '</span>'; break;
        default: $status_message = '<span style="color:orange;">' . esc_html__('Tool status unknown.', 'wpso') . '</span>'; break;
    }
    echo '<p><small>' . esc_html__('Status:', 'wpso') . ' ' . $status_message . '</small></p>'; // $status_message contains HTML, so not escaped here.
}

// Standard field renderers with improved readability and descriptions via $args
function wpso_field_minify($args) { $opts = wpso_get_options(); echo '<input type="checkbox" name="wpso_options[minify]" value="1" '.checked(1, $opts['minify'] ?? 0, false).' /> '.esc_html__('Minify CSS & JS.', 'wpso').'<p><small>'.esc_html($args['description']).'</small></p>'; }
function wpso_field_combine($args) { $opts = wpso_get_options(); echo '<input type="checkbox" name="wpso_options[combine]" value="1" '.checked(1, $opts['combine'] ?? 0, false).' /> '.esc_html__('Combine CSS & JS.', 'wpso').'<p><small>'.esc_html($args['description']).'</small></p>'; }
function wpso_field_preconnect($args) { $opts = wpso_get_options(); echo '<input type="text" name="wpso_options[preconnect]" value="'.esc_attr($opts['preconnect'] ?? '').'" size="50" placeholder="e.g. https://fonts.gstatic.com, https://cdn.example.com" /><p><small>'.esc_html($args['description']).'</small></p>'; }
function wpso_field_preload($args) { $opts = wpso_get_options(); echo '<input type="text" name="wpso_options[preload]" value="'.esc_attr($opts['preload'] ?? '').'" size="50" placeholder="e.g. /wp-content/themes/yourtheme/hero.jpg" /><p><small>'.esc_html($args['description']).'</small></p>'; }
function wpso_field_heartbeat($args) { $opts = wpso_get_options(); echo '<select name="wpso_options[heartbeat]"><option value="normal" '.selected($opts['heartbeat'] ?? 'normal', 'normal', false).'>'.esc_html__('Normal', 'wpso').'</option><option value="throttle" '.selected($opts['heartbeat'] ?? 'normal', 'throttle', false).'>'.esc_html__('Throttle (60s)', 'wpso').'</option><option value="disable" '.selected($opts['heartbeat'] ?? 'normal', 'disable', false).'>'.esc_html__('Disable in Admin', 'wpso').'</option></select><p><small>'.esc_html($args['description']).'</small></p>'; }
function wpso_field_perpage($args) { echo '<em>'.esc_html__('Coming soon: UI for per-page asset disabling!', 'wpso').'</em><p><small>'.esc_html($args['description']).'</small></p>'; }
function wpso_field_critical_css($args) { $opts = wpso_get_options(); echo '<textarea name="wpso_options[critical_css]" rows="6" cols="60" placeholder="'.esc_attr__('Paste your above-the-fold CSS here', 'wpso').'">'.esc_textarea($opts['critical_css'] ?? '').'</textarea><p><small>'.esc_html($args['description']).'</small></p>'; }
function wpso_field_move_css_footer($args) { $opts = wpso_get_options(); echo '<input type="checkbox" name="wpso_options[move_css_footer]" value="1" '.checked(1, $opts['move_css_footer'] ?? 0, false).' /> '.esc_html__('Move most CSS files to the footer.', 'wpso').'<p><small>'.wp_kses_post($args['description']).'</small></p>'; }
function wpso_field_minify_html($args) { $opts = wpso_get_options(); echo '<input type="checkbox" name="wpso_options[minify_html]" value="1" '.checked(1, $opts['minify_html'] ?? 0, false).' /> '.esc_html__('Minify HTML output.', 'wpso').'<p><small>'.wp_kses_post($args['description']).'</small></p>'; }
function wpso_field_allow_google_fonts($args) { $opts = wpso_get_options(); echo '<input type="checkbox" name="wpso_options[allow_google_fonts]" value="1" '.checked(1, $opts['allow_google_fonts'] ?? 1, false).' /> '.esc_html__('Allow Google Fonts.', 'wpso').'<p><small>'.esc_html($args['description']).'</small></p>'; }
function wpso_field_move_js_footer($args) { $opts = wpso_get_options(); echo '<input type="checkbox" name="wpso_options[move_js_footer]" value="1" '.checked(1, $opts['move_js_footer'] ?? 0, false).' /> '.esc_html__('Move most JavaScript files to the footer.', 'wpso').'<p><small>'.wp_kses_post($args['description']).'</small></p>'; }


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

// (Helper functions: _wpso_remove_version_query_arg, wpso_setup_cache_dir, _wpso_generate_cache_key, _wpso_get_critical_command_status, wpso_critical_css_admin_notice, wpso_dismiss_admin_notice_handler - remain as before, ensure PHPDocs added if missing)
/**
 * Removes version query arguments from script and style URLs.
 * @since 1.2.0
 * @param string $src The source URL.
 * @return string The URL without the 'ver' query argument.
 */
function _wpso_remove_version_query_arg($src) { if (strpos($src, 'ver=') !== false) { $src = remove_query_arg('ver', $src); } return $src; }

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
function _wpso_generate_cache_key($assets_data, $type = 'css') { $key_string = ''; foreach ($assets_data as $h => $d) { $key_string.=$h.($d['ver']??'0'); if(isset($d['path'])&&file_exists($d['path'])){$key_string.=filemtime($d['path']);}} return md5($key_string.$type.get_bloginfo('version'));}

/**
 * Checks and caches the status of the 'critical' command-line tool.
 * @since 2.1.0
 * @return array Status array with 'status', 'path', and 'message'.
 */
function _wpso_get_critical_command_status() { $status=get_transient('wpso_critical_tool_status'); if(false===$status){ if(!function_exists('shell_exec')||!function_exists('exec')||in_array('shell_exec',array_map('trim',explode(',',ini_get('disable_functions'))))||in_array('exec',array_map('trim',explode(',',ini_get('disable_functions'))))){$status=['status'=>'no_shell_exec','path'=>'','message'=>'PHP functions shell_exec or exec are disabled.'];}else{$path=shell_exec('command -v critical 2>/dev/null'); if(!empty($path)){$status=['status'=>'available','path'=>trim($path),'message'=>'Critical tool found at: '.trim($path)];}else{$status=['status'=>'no_command','path'=>'','message'=>'The "critical" command was not found in the server\'s PATH.'];}}set_transient('wpso_critical_tool_status',$status,DAY_IN_SECONDS);} return $status;}

/**
 * Displays an admin notice if the critical CSS auto-generation tool is not available.
 * @since 2.1.0
 */
add_action('admin_notices','wpso_critical_css_admin_notice'); function wpso_critical_css_admin_notice(){ if(!current_user_can('manage_options'))return; $user_id=get_current_user_id(); $status=_wpso_get_critical_command_status(); $dismiss_key='wpso_critical_status_notice_dismissed_'.substr(md5($status['status']),0,10); if(get_user_meta($user_id,$dismiss_key,true))return; if($status['status']==='available')return; $message='<strong>WP Speed Optimizer:</strong> Critical CSS auto-generation feature '; if($status['status']==='no_shell_exec'){$message.='is unavailable because PHP functions <code>shell_exec</code> or <code>exec</code> are disabled on your server.';}elseif($status['status']==='no_command'){$message.='may not work because the "critical" command-line tool could not be found in your server\'s PATH.';}else{return;} $message.=' Please consult the plugin documentation or your hosting provider for assistance if you wish to use this feature.'; $dismiss_url=wp_nonce_url(add_query_arg(['wpso_dismiss_notice'=>'critical_status','notice_id'=>$dismiss_key]),'wpso_dismiss_notice_action','_wpso_dismiss_nonce'); echo '<div class="notice notice-warning is-dismissible"><p>'.wp_kses_post($message).'</p><p><a href="'.esc_url($dismiss_url).'">'.__('Dismiss this notice','wpso').'</a></p></div>';}

/**
 * Handles the dismissal of the critical CSS status admin notice.
 * @since 2.1.0
 */
add_action('admin_init','wpso_dismiss_admin_notice_handler'); function wpso_dismiss_admin_notice_handler(){ if(isset($_GET['wpso_dismiss_notice'],$_GET['_wpso_dismiss_nonce'],$_GET['notice_id'])){if(!wp_verify_nonce(sanitize_key($_GET['_wpso_dismiss_nonce']),'wpso_dismiss_notice_action')){wp_die(__('Security check failed.','wpso'));} $user_id=get_current_user_id(); $notice_id=sanitize_key($_GET['notice_id']); if($user_id){$expected_dismiss_key='wpso_critical_status_notice_dismissed_'.substr(md5(_wpso_get_critical_command_status()['status']),0,10); if($notice_id===$expected_dismiss_key){update_user_meta($user_id,$notice_id,true);}} wp_redirect(remove_query_arg(['wpso_dismiss_notice','_wpso_dismiss_nonce','notice_id'])); exit;}}}


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

    // --- Core Optimizations (Emoji, oEmbed, Version Strings, Generator Tag) ---
    // These are generally considered safe and foundational.
    add_action('init', function() { remove_action('wp_head', 'print_emoji_detection_script', 7); remove_action('wp_print_styles', 'print_emoji_styles'); remove_action('admin_print_scripts', 'print_emoji_detection_script'); remove_action('admin_print_styles', 'print_emoji_styles'); });
    add_action('init', function() { remove_action('rest_api_init', 'wp_oembed_register_route'); remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10); remove_action('wp_head', 'wp_oembed_add_discovery_links'); remove_action('wp_head', 'wp_oembed_add_host_js'); remove_filter('embed_handler_html', '__return_false'); add_filter('embed_oembed_discover', '__return_false'); remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10); });
    add_filter('script_loader_src', '_wpso_remove_version_query_arg', 15); 
    add_filter('style_loader_src', '_wpso_remove_version_query_arg', 15); 
    remove_action('wp_head', 'wp_generator');
    
    // --- Defer Non-Essential JavaScript ---
    // Uses a filterable list of handles to skip.
    add_filter('script_loader_tag', function($tag, $handle) use ($opts) { 
        $skip_defer = ['jquery', 'jquery-core', 'jquery-migrate', 'wp-polyfill', 'wp-hooks', 'wp-i18n', 'contact-form-7', 'wpcf7-recaptcha', 'easy-wp-smtp-script']; 
        $skip_defer = apply_filters('wpso_defer_skip_handles', $skip_defer); 
        if (in_array($handle, $skip_defer, true)) return $tag; 
        if (strpos($tag, ' defer') === false && preg_match('/<script[^>]*src=["\'][^"\']*\.js["\'][^>]*>/', $tag)) { 
            $tag = str_replace(' async', '', $tag); // Remove async if present, as defer is preferred with ordered execution
            $tag = str_replace('<script ', '<script defer ', $tag); 
        } 
        return $tag; 
    }, 10, 2); // Default priority 10, 2 arguments.
    
    // --- Google Fonts Removal (if option selected) ---
    add_action('wp_enqueue_scripts', function() use ($opts) { 
        if (!empty($opts['allow_google_fonts'])) return; 
        global $wp_styles, $wp_scripts; 
        $fonts_to_remove = wpso_detect_google_fonts(); 
        foreach ($fonts_to_remove as $font) { 
            if ($font['type'] === 'style') { wp_dequeue_style($font['handle']); wp_deregister_style($font['handle']); } 
            elseif ($font['type'] === 'script') { wp_dequeue_script($font['handle']); wp_deregister_script($font['handle']); } 
        } 
    }, 100); // Late enough to catch most enqueues.
    
    // --- Other General Header/Feed Cleanup ---
    add_filter('the_generator', '__return_empty_string'); 
    remove_action('wp_head', 'rsd_link'); remove_action('wp_head', 'wlwmanifest_link'); remove_action('wp_head', 'wp_shortlink_wp_head'); remove_action('wp_head', 'rest_output_link_wp_head'); remove_action('wp_head', 'feed_links_extra', 3); remove_action('wp_head', 'feed_links', 2); remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
    add_filter('xmlrpc_methods', function($methods) { unset($methods['pingback.ping'], $methods['pingback.extensions.getPingbacks']); return $methods; }); 
    add_filter('wp_headers', function($headers) { unset($headers['X-Pingback']); return $headers; }); 
    add_action('pre_ping', function(&$links) { $links = array(); }); // Disable pingbacks globally
    add_action('pre_ping', function(&$links) { foreach ($links as $l => $link) { if (0 === strpos($link, get_option('home'))) unset($links[$l]); } }); // Disable self-pingbacks
    add_action('widgets_init', function() { global $wp_widget_factory; if (isset($wp_widget_factory->widgets['WP_Widget_Recent_Comments']) && method_exists($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style')) { remove_action('wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style')); } });

    // --- Minify & Combine CSS/JS with Caching ---
    // This is a complex operation, hooked late to process enqueued assets.
    add_action('wp_enqueue_scripts', function() use ($opts) {
        if (is_admin() || (empty($opts['minify']) && empty($opts['combine']))) { return; } // Only run if options are set and not in admin

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
        if (!$done_js_combine && $wp_scripts && !empty($wp_scripts->queue)) {
            $skip_js = ['admin-bar', 'dashicons', 'wp-admin', 'jquery', 'jquery-core', 'jquery-migrate']; 
            $skip_handles_js = apply_filters('wpso_combine_minify_skip_handles_js', $skip_js);
            $assets_data_js = []; $handles_to_combine_js = [];
            foreach ($wp_scripts->queue as $handle) {
                if (in_array($handle, $skip_handles_js, true)) continue;
                if (isset($wp_scripts->registered['admin-bar']->deps) && is_array($wp_scripts->registered['admin-bar']->deps) && in_array($handle, $wp_scripts->registered['admin-bar']->deps, true)) continue;
                $script_obj = $wp_scripts->registered[$handle] ?? null; if (!$script_obj || !$script_obj->src || strpos($script_obj->src, '.js') === false) continue; if (!empty($script_obj->extra['group']) && $script_obj->extra['group'] === 1) continue;
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

    // Preconnect & Preload
    add_action('wp_head', function() use ($opts) { if(!empty($opts['preconnect'])){$origins=array_map('trim',explode(',',$opts['preconnect'])); foreach($origins as $o){if(filter_var($o,FILTER_VALIDATE_URL))echo '<link rel="preconnect" href="'.esc_url($o).'" crossorigin />'."\n";}} if(!empty($opts['preload'])){$assets=array_map('trim',explode(',',$opts['preload'])); foreach($assets as $asset_url){if($asset_url){$as='auto'; $path=wp_parse_url($asset_url,PHP_URL_PATH); $ext=''; if($path){$ext=strtolower(pathinfo($path,PATHINFO_EXTENSION));} if($ext){switch($ext){case 'js':$as='script';break; case 'css':$as='style';break; case 'woff':case 'woff2':case 'ttf':case 'otf':case 'eot':case 'webfont':$as='font';break; case 'jpg':case 'jpeg':case 'png':case 'gif':case 'webp':case 'svg':case 'avif':$as='image';break;}} $crossorigin=($as==='font')?' crossorigin':''; echo '<link rel="preload" href="'.esc_url($asset_url).'" as="'.esc_attr($as).'"'.$crossorigin.' />'."\n";}}} }, 2);
    
    // Heartbeat
    add_filter('heartbeat_settings', function($settings) use ($opts) { if(!is_admin())return $settings; $hb_setting=$opts['heartbeat']??'normal'; if($hb_setting==='throttle')$settings['interval']=60; elseif($hb_setting==='disable')$settings['interval']=600; return $settings;});
    add_action('init', function() use ($opts) { if(is_admin()&&!empty($opts['heartbeat'])&&$opts['heartbeat']==='disable')wp_deregister_script('heartbeat');});
    
    // Critical CSS
    add_action('add_meta_boxes',function(){$screens=['post','page']; $screens=apply_filters('wpso_critical_css_metabox_screens',$screens); foreach($screens as $s){add_meta_box('wpso_critical_css','WP Speed Optimizer: Critical CSS','wpso_perpage_critical_css_metabox',$s,'side','default');}});
    add_action('save_post',function($post_id){if(!isset($_POST['wpso_critical_css_nonce'])||!wp_verify_nonce($_POST['wpso_critical_css_nonce'],'wpso_save_critical_css'))return; if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)return; if(!current_user_can(($_POST['post_type']==='page'?'edit_page':'edit_post'),$post_id))return; if(isset($_POST['wpso_critical_css'])){$css=wp_kses_post(trim($_POST['wpso_critical_css'])); update_post_meta($post_id,'_wpso_critical_css',$css);}else{delete_post_meta($post_id,'_wpso_critical_css');}},10,1);
    add_action('wp_head',function() use ($opts){$out_css=''; if(is_singular()){$pid=get_queried_object_id(); $page_css=get_post_meta($pid,'_wpso_critical_css',true); if($page_css)$out_css=$page_css;} if(empty($out_css)&&!empty($opts['critical_css']))$out_css=$opts['critical_css']; $out_css=apply_filters('wpso_critical_css',$out_css); if($out_css)echo "<style id='wpso-critical-css'>\n".$out_css."\n</style>\n";},1); // Priority 1 for critical CSS
    
    // Auto-generate Critical CSS (Timeout: 30000ms = 30s)
    add_action('save_post',function($post_id) use ($opts){if(empty($opts['critical_css_autogenerate']))return; if(wp_is_post_revision($post_id)||get_post_status($post_id)!=='publish'||get_post_meta($post_id,'_wpso_critical_css',true))return; $crit_stat=_wpso_get_critical_command_status(); if($crit_stat['status']!=='available'){error_log('WPSO: CritCSS auto-gen skip: '.$crit_stat['message']);return;} $cmd_path=$crit_stat['path']; $url=get_permalink($post_id); if(!$url||is_wp_error($url)||!filter_var($url,FILTER_VALIDATE_URL))return; $out_file=sys_get_temp_dir().'/wpso_critical_'.$post_id.'_'.wp_generate_password(12,false).'.css'; $cmd=escapeshellcmd($cmd_path).' --minify --width 1300 --height 900 '.escapeshellarg($url).' --extract --inline false --timeout 30000 --output '.escapeshellarg($out_file).' 2>&1'; $output=[]; $ret=0; @exec($cmd,$output,$ret); if($ret===0&&file_exists($out_file)){$css=file_get_contents($out_file); if($css){$css=wp_kses_post(trim($css));update_post_meta($post_id,'_wpso_critical_css',$css);} @unlink($out_file);}else{error_log("WPSO CritCSS Fail.\nURL:".$url."\nCMD:".$cmd."\nRC:".$ret."\nOutput:".implode("\n",$output));}},20,1); // Priority 20, after manual save.

    // --- Experimental Feature: Move CSS to Footer ---
    if(!empty($opts['move_css_footer'])){
        add_action('wp_enqueue_scripts',function()use($opts){
            if(is_admin())return; global $wp_styles; if(!($wp_styles instanceof WP_Styles)){error_log('WPSO: WP_Styles N/A for move CSS.');return;}
            $skip=['admin-bar','dashicons','wpso-critical-css']; // Critical CSS must stay in head
            $skip=apply_filters('wpso_move_css_footer_skip_handles',$skip);
            foreach($wp_styles->queue as $h){
                if(in_array($h,$skip,true))continue;
                if(isset($wp_styles->groups[$h])&&$wp_styles->groups[$h]===1)continue; // Already in footer
                if($wp_styles->get_data($h,'group')===1)continue; // Already in footer by add_data
                $wp_styles->add_data($h,'group',1);
            }
        },1001); // Priority 1001: After most styles are enqueued.
    }

    // --- Experimental Feature: Minify HTML Output ---
    if(!empty($opts['minify_html'])){
        add_action('template_redirect',function()use($opts){
            if(is_admin()||is_customize_preview()||defined('XMLRPC_REQUEST')||defined('REST_REQUEST')||defined('DOING_AJAX')||defined('DOING_CRON')||is_feed()||is_robots()||is_embed()||is_404()||is_search())return;
            $handlers=ob_list_handlers(); if(in_array('wpso_minify_html_buffer',$handlers,true))return;
            if(ob_get_level()>0&&!empty($handlers)){error_log('WPSO: HTML minify OB conflict warning. Active: '.implode(',',$handlers));}
            ob_start('wpso_minify_html_buffer');
        },0); // Priority 0: Very early on template_redirect.
    }

    // --- Experimental Feature: Move JavaScript to Footer ---
    if(!empty($opts['move_js_footer'])){
        add_action('wp_enqueue_scripts',function()use($opts){
            if(is_admin())return; global $wp_scripts; if(!($wp_scripts instanceof WP_Scripts)){error_log('WPSO: WP_Scripts N/A for move JS.');return;}
            $skip=['jquery','jquery-core','jquery-migrate','wp-polyfill','wp-hooks','wp-i18n','wp-api-fetch','wp-url','contact-form-7','woocommerce']; // Default essential scripts
            $skip=apply_filters('wpso_move_js_footer_skip_handles',$skip);
            foreach($wp_scripts->queue as $h){
                if(in_array($h,$skip,true))continue;
                if($wp_scripts->get_data($h,'group')===1)continue; // Already in footer
                if(isset($wp_scripts->registered[$h])&&$wp_scripts->registered[$h]->src){$wp_scripts->add_data($h,'group',1);}
            }
        },1002); // Priority 1002: After most scripts, and after move_css_footer.
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

/*! 
Sources (Past & previous):
... (rest of the comment block) ...
*/
