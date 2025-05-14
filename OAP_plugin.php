<?php
/**
 * @package OAP
 */
/**
* Plugin Name: Don's Optimization Anthology Plugin
 * Plugin URI: https://github.com/donvoorhies/oap
 * Description: An "anthology" of (IMO) some snazzy functions that I've come across over time, and which I earlier usually hardcoded into 'functions.php' to optimize my Wordpress-installs with; for more details regarding this plugin's different functionalites, as for accessing the latest updated version of this plugin - please go visit: https://github.com/donvoorhies/oap
 * Version (Installed): 2.0.0
 * Author:  Various Contributors and sources | Compiled and assembled by Don W.Voorhies (See the referenced URLs regarding the specific contributing-credits) - assisted by the Autonomous DEV...
 * Author URI: https://donvoorhies.github.io/oap/
 * License: Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License (extended with additional conditions)
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// --- Core Optimization Hooks --- //

// Remove emoji scripts/styles
add_action('init', function() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
});

// Disable embeds
add_action('init', function() {
    remove_action('rest_api_init', 'wp_oembed_register_route');
    remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
});

// Remove query strings from static resources
add_filter('script_loader_src', function($src) {
    $parts = explode('?', $src);
    return $parts[0];
}, 15);
add_filter('style_loader_src', function($src) {
    $parts = explode('?', $src);
    return $parts[0];
}, 15);

// Remove WordPress version from head
remove_action('wp_head', 'wp_generator');

// --- End of prototype --- // 

// --- Above-the-fold Optimization (prototype) --- //

// 1. Inline critical CSS for above-the-fold content
add_action('wp_head', function() {
    // Users can filter 'wpso_critical_css' to inject their own critical CSS
    $critical_css = apply_filters('wpso_critical_css', '/* Add your critical CSS here! */');
    if ($critical_css) {
        echo "<style id='wpso-critical-css'>" . $critical_css . "</style>\n";
    }
}, 1); // Priority 1: very early in <head>

// 2. Defer non-essential JS (except jQuery/core)
add_filter('script_loader_tag', function($tag, $handle) {
    $skip = [
        'jquery', 'jquery-core', 'jquery-migrate', 'wp-polyfill', 'wp-hooks', 'wp-i18n',
        // Add more handles here if needed
    ];
    if (in_array($handle, $skip)) return $tag;
    // Only add defer if not already present and is a JS file
    if (strpos($tag, ' defer') === false && strpos($tag, '<script') !== false) {
        $tag = str_replace('<script ', '<script defer ', $tag);
    }
    return $tag;
}, 10, 2);

// --- End above-the-fold optimization prototype --- // 

// --- User-requested Speed & Security Optimizations (prototype) --- //

// 1. Remove Google Fonts (for most themes/plugins)
add_action('wp_enqueue_scripts', function() {
    $opts = get_option('wpso_options');
    if (!empty($opts['allow_google_fonts'])) return; // Allow if checked
    global $wp_styles, $wp_scripts;
    foreach ($wp_styles->registered as $handle => $style) {
        if (isset($style->src) && strpos($style->src, 'fonts.googleapis.com') !== false) {
            wp_dequeue_style($handle);
            wp_deregister_style($handle);
        }
    }
    foreach ($wp_scripts->registered as $handle => $script) {
        if (isset($script->src) && strpos($script->src, 'fonts.googleapis.com') !== false) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
    }
}, 100);

// 2. Prevent WordPress from testing SSL capability on xmlrpc.php?rsd
add_filter('site_status_tests', function($tests) {
    if (isset($tests['direct']['ssl_support'])) {
        unset($tests['direct']['ssl_support']);
    }
    return $tests;
});

// 3. Remove version info from head and feeds (already in head, now for feeds)
add_filter('the_generator', '__return_empty_string');

// 4. Remove unnecessary header info
remove_action('wp_head', 'rsd_link'); // Really Simple Discovery
remove_action('wp_head', 'wlwmanifest_link'); // Windows Live Writer
remove_action('wp_head', 'wp_shortlink_wp_head'); // Shortlink
remove_action('wp_head', 'rest_output_link_wp_head'); // REST API link
remove_action('wp_head', 'feed_links_extra', 3); // Extra feed links
remove_action('wp_head', 'feed_links', 2); // Main feed links
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10); // Prev/next post links

// 5. Disable pingback and trackback notifications
add_filter('xmlrpc_methods', function($methods) {
    unset($methods['pingback.ping'], $methods['pingback.extensions.getPingbacks']);
    return $methods;
});
add_filter('wp_headers', function($headers) {
    unset($headers['X-Pingback']);
    return $headers;
});
add_action('pre_ping', function(&$links) {
    $links = array();
});

// 6. Disable self-pingbacks
add_action('pre_ping', function(&$links) {
    foreach ($links as $l => $link) {
        if (0 === strpos($link, get_option('home'))) {
            unset($links[$l]);
        }
    }
});

// 7. Remove extra CSS from Recent Comments widget
add_action('widgets_init', function() {
    global $wp_widget_factory;
    if (isset($wp_widget_factory->widgets['WP_Widget_Recent_Comments'])) {
        remove_action('wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style'));
    }
});

// 8. Remove wp-version number params from scripts and styles (scoped)
add_filter('script_loader_src', function($src) {
    $src = remove_query_arg('ver', $src);
    return $src;
}, 9999);
add_filter('style_loader_src', function($src) {
    $src = remove_query_arg('ver', $src);
    return $src;
}, 9999);

// --- End user-requested optimizations prototype --- // 

// --- Settings Page & Options Scaffold (prototype) --- //

add_action('admin_menu', function() {
    add_options_page(
        'WP Speed Optimizer',
        'WP Speed Optimizer',
        'manage_options',
        'wpso-settings',
        'wpso_render_settings_page'
    );
});

add_action('admin_init', function() {
    register_setting('wpso_settings', 'wpso_options');
    add_settings_section('wpso_main', 'Speedy Settings', null, 'wpso-settings');
    // Add fields for each feature
    add_settings_field('minify', 'Minify CSS & JS', 'wpso_field_minify', 'wpso-settings', 'wpso_main');
    add_settings_field('combine', 'Combine CSS & JS', 'wpso_field_combine', 'wpso-settings', 'wpso_main');
    add_settings_field('preconnect', 'Preconnect Origins', 'wpso_field_preconnect', 'wpso-settings', 'wpso_main');
    add_settings_field('preload', 'Preload Critical Assets', 'wpso_field_preload', 'wpso-settings', 'wpso_main');
    add_settings_field('heartbeat', 'Control Heartbeat API', 'wpso_field_heartbeat', 'wpso-settings', 'wpso_main');
    add_settings_field('perpage', 'Disable Assets Per Page', 'wpso_field_perpage', 'wpso-settings', 'wpso_main');
    add_settings_field('critical_css', 'Global Critical CSS', 'wpso_field_critical_css', 'wpso-settings', 'wpso_main');
    add_settings_field('move_css_footer', 'Move CSS to Footer', 'wpso_field_move_css_footer', 'wpso-settings', 'wpso_main');
    add_settings_field('minify_html', 'Minify HTML Output', 'wpso_field_minify_html', 'wpso-settings', 'wpso_main');
    add_settings_field('allow_google_fonts', 'Allow Google Fonts', 'wpso_field_allow_google_fonts', 'wpso-settings', 'wpso_main');
    add_settings_field('move_js_footer', 'Move JavaScript to Footer', 'wpso_field_move_js_footer', 'wpso-settings', 'wpso_main');
});

function wpso_render_settings_page() {
    echo '<div class="wrap"><h1>WP Speed Optimizer // prototype</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('wpso_settings');
    // List detected Google Fonts
    $google_fonts = wpso_detect_google_fonts();
    if (!empty($google_fonts)) {
        echo '<div style="background:#fffbe5;border:1px solid #ffe066;padding:10px;margin-bottom:15px;">';
        echo '<strong>Detected Google Fonts:</strong><ul style="margin:0 0 0 20px;">';
        foreach ($google_fonts as $font) {
            echo '<li>' . esc_html($font['handle']) . ' <span style="color:#888">(' . esc_html($font['type']) . ')</span> &rarr; <code>' . esc_html($font['src']) . '</code></li>';
        }
        echo '</ul></div>';
    } else {
        echo '<div style="background:#eaffea;border:1px solid #b2f2bb;padding:10px;margin-bottom:15px;">No Google Fonts detected in registered styles/scripts.</div>';
    }
    echo '<p>Toggle and configure speed optimizations. <em>Most features are production-grade, but some are still marked as prototype.</em></p>';
    echo '<h2>Global Optimizations</h2>';
    do_settings_sections('wpso-settings');
    submit_button();
    echo '</form></div>';
}

// --- Field Renderers (minimal, playful) --- //
function wpso_field_minify() {
    $opts = get_option('wpso_options');
    echo '<input type="checkbox" name="wpso_options[minify]" value="1" '.checked(1, $opts['minify'] ?? 0, false).' /> Minify all CSS & JS (prototype)';
}
function wpso_field_combine() {
    $opts = get_option('wpso_options');
    echo '<input type="checkbox" name="wpso_options[combine]" value="1" '.checked(1, $opts['combine'] ?? 0, false).' /> Combine all CSS & JS (prototype)';
}
function wpso_field_preconnect() {
    $opts = get_option('wpso_options');
    echo '<input type="text" name="wpso_options[preconnect]" value="'.esc_attr($opts['preconnect'] ?? '').'" size="50" placeholder="e.g. https://fonts.gstatic.com, https://cdn.example.com" />';
}
function wpso_field_preload() {
    $opts = get_option('wpso_options');
    echo '<input type="text" name="wpso_options[preload]" value="'.esc_attr($opts['preload'] ?? '').'" size="50" placeholder="e.g. /wp-content/themes/yourtheme/hero.jpg, /wp-content/plugins/plugin/font.woff2" />';
}
function wpso_field_heartbeat() {
    $opts = get_option('wpso_options');
    echo '<select name="wpso_options[heartbeat]">';
    echo '<option value="normal" '.selected($opts['heartbeat'] ?? '', 'normal', false).'>Normal</option>';
    echo '<option value="throttle" '.selected($opts['heartbeat'] ?? '', 'throttle', false).'>Throttle</option>';
    echo '<option value="disable" '.selected($opts['heartbeat'] ?? '', 'disable', false).'>Disable</option>';
    echo '</select> (prototype)';
}
function wpso_field_perpage() {
    echo '<em>Coming soon: UI for per-page asset disabling!</em>';
}
function wpso_field_critical_css() {
    $opts = get_option('wpso_options');
    echo '<textarea name="wpso_options[critical_css]" rows="6" cols="60" placeholder="Paste your above-the-fold CSS here">'.esc_textarea($opts['critical_css'] ?? '').'</textarea>';
    echo '<br><small>This CSS will be inlined on all pages unless a per-page override is set.</small>';
}
function wpso_field_move_css_footer() {
    $opts = get_option('wpso_options');
    echo '<input type="checkbox" name="wpso_options[move_css_footer]" value="1" '.checked(1, $opts['move_css_footer'] ?? 0, false).' /> Move all CSS files to the footer (experimental, may cause FOUC)';
}
function wpso_field_minify_html() {
    $opts = get_option('wpso_options');
    echo '<input type="checkbox" name="wpso_options[minify_html]" value="1" '.checked(1, $opts['minify_html'] ?? 0, false).' /> Minify HTML output (removes whitespace and line-breaks, experimental)';
}
function wpso_field_allow_google_fonts() {
    $opts = get_option('wpso_options');
    echo '<input type="checkbox" name="wpso_options[allow_google_fonts]" value="1" '.checked(1, $opts['allow_google_fonts'] ?? 0, false).' /> Allow Google Fonts (uncheck to block/remove for privacy & speed)';
}
function wpso_field_move_js_footer() {
    $opts = get_option('wpso_options');
    echo '<input type="checkbox" name="wpso_options[move_js_footer]" value="1" '.checked(1, $opts['move_js_footer'] ?? 0, false).' /> Move all JavaScript files to the footer (experimental, may break some plugins/themes)';
}
// --- End settings page scaffold --- //

// --- Improved Minify & Combine CSS/JS (production-grade) --- //
add_action('wp_enqueue_scripts', function() {
    $opts = get_option('wpso_options');
    if (!is_admin() && (!empty($opts['minify']) || !empty($opts['combine']))) {
        global $wp_styles, $wp_scripts;
        static $done = false;
        if ($done) return; // Prevent double run
        $done = true;
        // --- Combine CSS --- //
        if (!empty($opts['combine'])) {
            $css = '';
            $seen = [];
            foreach ($wp_styles->queue as $handle) {
                $src = $wp_styles->registered[$handle]->src ?? '';
                if ($src && strpos($src, '.css') !== false) {
                    $src = wpso_resolve_url($src);
                    if ($src && !isset($seen[$src])) {
                        $css .= @file_get_contents($src);
                        $seen[$src] = true;
                    }
                    wp_dequeue_style($handle);
                }
            }
            if ($css) {
                if (!empty($opts['minify'])) $css = wpso_minify_css($css);
                add_action('wp_head', function() use ($css) {
                    echo '<style id="wpso-combined-css">'.$css.'</style><!-- combined // production -->';
                }, 99);
            }
        } elseif (!empty($opts['minify'])) {
            // Minify each enqueued CSS
            foreach ($wp_styles->queue as $handle) {
                $src = $wp_styles->registered[$handle]->src ?? '';
                if ($src && strpos($src, '.css') !== false) {
                    $src = wpso_resolve_url($src);
                    if ($src) {
                        $css = @file_get_contents($src);
                        $css = wpso_minify_css($css);
                        add_action('wp_head', function() use ($css, $handle) {
                            echo '<style id="wpso-minified-css-'.$handle.'">'.$css.'</style><!-- minified // production -->';
                        }, 99);
                        wp_dequeue_style($handle);
                    }
                }
            }
        }
        // --- Combine JS --- //
        if (!empty($opts['combine'])) {
            $js = '';
            $seen = [];
            foreach ($wp_scripts->queue as $handle) {
                $src = $wp_scripts->registered[$handle]->src ?? '';
                if ($src && strpos($src, '.js') !== false) {
                    $src = wpso_resolve_url($src);
                    if ($src && !isset($seen[$src])) {
                        $js .= @file_get_contents($src)."\n";
                        $seen[$src] = true;
                    }
                    wp_dequeue_script($handle);
                }
            }
            if ($js) {
                if (!empty($opts['minify'])) $js = wpso_minify_js($js);
                add_action('wp_footer', function() use ($js) {
                    echo '<script id="wpso-combined-js">'.$js.'</script><!-- combined // production -->';
                }, 99);
            }
        } elseif (!empty($opts['minify'])) {
            // Minify each enqueued JS
            foreach ($wp_scripts->queue as $handle) {
                $src = $wp_scripts->registered[$handle]->src ?? '';
                if ($src && strpos($src, '.js') !== false) {
                    $src = wpso_resolve_url($src);
                    if ($src) {
                        $js = @file_get_contents($src);
                        $js = wpso_minify_js($js);
                        add_action('wp_footer', function() use ($js, $handle) {
                            echo '<script id="wpso-minified-js-'.$handle.'">'.$js.'</script><!-- minified // production -->';
                        }, 99);
                        wp_dequeue_script($handle);
                    }
                }
            }
        }
    }
}, 999);

// --- Helper: Resolve relative URLs to absolute file paths (production-grade) --- //
function wpso_resolve_url($src) {
    if (strpos($src, '//') === 0) $src = 'https:' . $src;
    if (strpos($src, 'http') === 0) {
        // Try to convert to local path if possible
        $home_url = home_url();
        $site_url = site_url();
        $content_url = content_url();
        $abs_path = ABSPATH;
        if (strpos($src, $content_url) === 0) {
            $rel = substr($src, strlen($content_url));
            $file = WP_CONTENT_DIR . $rel;
            if (file_exists($file)) return $file;
        }
        // Fallback: try to fetch remote (not ideal for production)
        return $src;
    }
    // Already a file path
    return $src;
}

// --- Simple Minifiers (prototype) --- //
function wpso_minify_css($css) {
    $css = preg_replace('!\s+!', ' ', $css);
    $css = preg_replace('/\s*([{}|:;,])\s+/', '$1', $css);
    $css = preg_replace('/;}/', '}', $css);
    $css = preg_replace('/\/\*.*?\*\//', '', $css);
    return trim($css);
}
function wpso_minify_js($js) {
    $js = preg_replace('/\/\*.*?\*\//s', '', $js); // Remove comments
    $js = preg_replace('/\s+/', ' ', $js);
    return trim($js);
}
// --- End minify/combine prototype --- //

// --- Preconnect & Preload (prototype) --- //
add_action('wp_head', function() {
    $opts = get_option('wpso_options');
    // Preconnect
    if (!empty($opts['preconnect'])) {
        $origins = array_map('trim', explode(',', $opts['preconnect']));
        foreach ($origins as $origin) {
            if ($origin) {
                echo '<link rel="preconnect" href="'.esc_url($origin).'" crossorigin />\n';
            }
        }
    }
    // Preload
    if (!empty($opts['preload'])) {
        $assets = array_map('trim', explode(',', $opts['preload']));
        foreach ($assets as $asset) {
            if ($asset) {
                $as = 'auto';
                if (preg_match('/\.woff2?$/', $asset)) $as = 'font';
                elseif (preg_match('/\.js$/', $asset)) $as = 'script';
                elseif (preg_match('/\.css$/', $asset)) $as = 'style';
                elseif (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/', $asset)) $as = 'image';
                echo '<link rel="preload" href="'.esc_url($asset).'" as="'.$as.'" crossorigin />\n';
            }
        }
    }
}, 2); // Early in <head>
// --- End preconnect/preload prototype --- //

// --- Heartbeat API Control (prototype) --- //
add_filter('heartbeat_settings', function($settings) {
    $opts = get_option('wpso_options');
    if (!is_admin()) return $settings;
    if (empty($opts['heartbeat']) || $opts['heartbeat'] === 'normal') return $settings;
    if ($opts['heartbeat'] === 'throttle') {
        $settings['interval'] = 60; // 1 request per minute
    } elseif ($opts['heartbeat'] === 'disable') {
        $settings['interval'] = 600; // Effectively disables
    }
    return $settings;
});
add_action('init', function() {
    $opts = get_option('wpso_options');
    if (!empty($opts['heartbeat']) && $opts['heartbeat'] === 'disable') {
        wp_deregister_script('heartbeat');
    }
});
// --- End Heartbeat API control prototype --- //

// --- Critical CSS UI (global + per-page) --- //
add_action('add_meta_boxes', function() {
    add_meta_box('wpso_critical_css', 'WP Speed Optimizer: Critical CSS', 'wpso_perpage_critical_css_metabox', null, 'side', 'default');
});
function wpso_perpage_critical_css_metabox($post) {
    $css = get_post_meta($post->ID, '_wpso_critical_css', true) ?: '';
    echo '<textarea name="wpso_critical_css" rows="6" style="width:100%" placeholder="Paste critical CSS for this page only">'.esc_textarea($css).'</textarea>';
    echo '<br><small>Overrides global critical CSS for this page.</small>';
}
add_action('save_post', function($post_id) {
    if (isset($_POST['wpso_critical_css'])) {
        update_post_meta($post_id, '_wpso_critical_css', wp_kses_post($_POST['wpso_critical_css']));
    }
});
add_action('wp_head', function() {
    if (is_singular()) {
        $css = get_post_meta(get_queried_object_id(), '_wpso_critical_css', true);
        if ($css) {
            echo "<style id='wpso-critical-css'>" . $css . "</style>\n";
            return;
        }
    }
    $opts = get_option('wpso_options');
    if (!empty($opts['critical_css'])) {
        echo "<style id='wpso-critical-css'>" . $opts['critical_css'] . "</style>\n";
    }
}, 1);

// --- Auto-generate Critical CSS (Node.js 'critical' integration, prototype/experimental) --- //
add_action('save_post', function($post_id) {
    // Only for public posts/pages
    if (wp_is_post_revision($post_id) || get_post_status($post_id) !== 'publish') return;
    $url = get_permalink($post_id);
    $output_file = sys_get_temp_dir() . '/wpso_critical_' . $post_id . '.css';
    $cmd = 'command -v critical';
    $has_critical = trim(shell_exec($cmd));
    if ($has_critical) {
        // Try to generate critical CSS
        $cmd = 'critical --minify --width 1300 --height 900 ' . escapeshellarg($url) . ' --extract --inline false --timeout 30000 --output ' . escapeshellarg($output_file);
        @exec($cmd, $out, $ret);
        if ($ret === 0 && file_exists($output_file)) {
            $css = file_get_contents($output_file);
            if ($css) {
                update_post_meta($post_id, '_wpso_critical_css', $css);
            }
            @unlink($output_file);
        }
    }
}, 20);

// --- Move CSS to Footer (experimental/advanced) --- //
add_action('wp_enqueue_scripts', function() {
    $opts = get_option('wpso_options');
    if (!empty($opts['move_css_footer']) && !is_admin()) {
        global $wp_styles;
        foreach ($wp_styles->queue as $handle) {
            // Don't move critical/above-the-fold CSS (handled inline)
            if ($handle === 'wpso-critical-css') continue;
            $wp_styles->add_data($handle, 'group', 1);
        }
    }
}, 1001);

// --- HTML Minification (experimental) --- //
add_action('template_redirect', function() {
    $opts = get_option('wpso_options');
    if (!empty($opts['minify_html']) && !is_admin()) {
        ob_start('wpso_minify_html');
    }
});
function wpso_minify_html($html) {
    // Remove whitespace between tags, line breaks, and extra spaces
    $html = preg_replace('/>\s+</', '><', $html);
    $html = preg_replace('/\s{2,}/', ' ', $html);
    $html = str_replace(["\r", "\n", "\t"], '', $html);
    return $html;
}

// --- Google Fonts Detection Helper --- //
function wpso_detect_google_fonts() {
    global $wp_styles, $wp_scripts;
    $fonts = [];
    if (isset($wp_styles->registered)) {
        foreach ($wp_styles->registered as $handle => $style) {
            if (isset($style->src) && strpos($style->src, 'fonts.googleapis.com') !== false) {
                $fonts[] = ['handle' => $handle, 'type' => 'style', 'src' => $style->src];
            }
        }
    }
    if (isset($wp_scripts->registered)) {
        foreach ($wp_scripts->registered as $handle => $script) {
            if (isset($script->src) && strpos($script->src, 'fonts.googleapis.com') !== false) {
                $fonts[] = ['handle' => $handle, 'type' => 'script', 'src' => $script->src];
            }
        }
    }
    return $fonts;
}

// --- Move JS to Footer (experimental/advanced) --- //
add_action('wp_enqueue_scripts', function() {
    $opts = get_option('wpso_options');
    if (!empty($opts['move_js_footer']) && !is_admin()) {
        global $wp_scripts;
        $skip = [
            'jquery', 'jquery-core', 'jquery-migrate', 'wp-polyfill', 'wp-hooks', 'wp-i18n',
            // Add more handles here if needed for safety
        ];
        foreach ($wp_scripts->queue as $handle) {
            if (in_array($handle, $skip)) continue;
            $wp_scripts->add_data($handle, 'group', 1);
        }
    }
}, 1002); 

/*! 
Sources (Past & previous):
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
https://wordpress.stackexchange.com/revisions/3816/5 
(Originally by: Andrew Ryno)


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


For development-purposes:
https://wpbeaches.com/show-all-loaded-scripts-and-styles-on-a-page-in-wordpress/

https://www.webperftools.com/blog/how-to-remove-unused-css-in-wordpress/

All other code - forged together by Don W. Voorhies 

(This plugin is made with recycled electrons - No bytes were harmed at any time!)
*/
