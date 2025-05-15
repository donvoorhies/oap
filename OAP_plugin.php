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

// --- Register admin menu --- //
add_action('admin_menu', function() {
    add_options_page(
        'WP Speed Optimizer',
        'WP Speed Optimizer',
        'manage_options',
        'wpso-settings',
        'wpso_render_settings_page'
    );
});

// --- Register admin settings --- //
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

// --- Google Fonts Detection Helper (always available) --- //
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

// --- Settings Page Render and Field Renderers --- //
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
     // Add warning about experimental features
    echo '<div style="background:#ffffe0;border:1px solid #ffd700;padding:10px;margin-bottom:15px;">';
    echo '<strong>Important:</strong> Some features are experimental and may cause conflicts with themes or other plugins (like mail). If you encounter issues, try disabling features one by one, starting with "Minify HTML Output", "Combine CSS & JS", and "Move JavaScript to Footer". The Critical CSS Auto-generation requires server access to run external commands and may cause errors if not supported.';
    echo '</div>';
    echo '<p>Toggle and configure speed optimizations. <em>Most features are production-grade, but some are still marked as prototype.</em></p>';
    echo '<h2>Global Optimizations</h2>';
    do_settings_sections('wpso-settings');
    submit_button();
    echo '</form></div>';
}

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

// --- Register frontend optimizations --- //
add_action('wp', function() {
    if (!is_admin() && !is_customize_preview()) {
        wpso_register_optimizations();
    }
});

function wpso_register_optimizations() {
    // --- Core Optimization Hooks --- //

    // Remove emoji scripts/styles
    add_action('init', function() {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
    });

    // Disable embeds
    add_action('init', function() {
        remove_action('rest_api_init', 'wp_oembed_register_route');
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_filter('embed_handler_html', '__return_false'); // Disable embed handling
        add_filter('embed_oembed_discover', '__return_false'); // Disable oEmbed discovery
        remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
    });

    // Remove query strings from static resources (Lower priority to potentially avoid conflicts)
    add_filter('script_loader_src', function($src) {
        if (strpos($src, 'ver=') !== false) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }, 100); // Increased priority
    add_filter('style_loader_src', function($src) {
         if (strpos($src, 'ver=') !== false) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }, 100); // Increased priority

    // Remove WordPress version from head (Already done below for feeds, keeping this for belt-and-suspenders)
    remove_action('wp_head', 'wp_generator');

    // --- End of prototype --- //

    // --- Above-the-fold Optimization (prototype) --- //

    // 1. Inline critical CSS for above-the-fold content
    add_action('wp_head', function() {
        // Users can filter 'wpso_critical_css' to inject their own critical CSS
        // The actual critical CSS is handled by the per-page/global logic below
    }, 1); // Priority 1: very early in <head>

    // 2. Defer non-essential JS (except core, jquery, and known form/mail handlers)
    add_filter('script_loader_tag', function($tag, $handle) {
        // List handles to skip deferring
        $skip = [
            'jquery', 'jquery-core', 'jquery-migrate', 'wp-polyfill', 'wp-hooks', 'wp-i18n',
            'contact-form-7', 'wpcf7-recaptcha', // Added Contact Form 7 scripts
            'easy-wp-smtp-script', // Potential Easy WP SMTP script handle (verify if needed)
            // Add more handles here if needed via filter
        ];
         $skip = apply_filters('wpso_defer_skip_handles', $skip);

        if (in_array($handle, $skip, true)) {
            return $tag;
        }

        // Only add defer if not already present and is a JS file tag
        if (strpos($tag, ' defer') === false && preg_match('/<script[^>]*src=["\'][^"\']*\.js["\'][^>]*>/', $tag)) {
            // Ensure async is not present as defer and async can conflict
            $tag = str_replace(' async', '', $tag);
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
        $fonts_to_remove = wpso_detect_google_fonts(); // Use the existing detector
        foreach ($fonts_to_remove as $font) {
             if ($font['type'] === 'style') {
                wp_dequeue_style($font['handle']);
                wp_deregister_style($font['handle']);
            } elseif ($font['type'] === 'script') {
                 wp_dequeue_script($font['handle']);
                wp_deregister_script($font['handle']);
            }
        }
    }, 100);

    // 2. Prevent WordPress from testing SSL capability on xmlrpc.php?rsd
    // Removed this filter - less likely to cause mail issues, and removing core tests might hide actual server problems.

    // 3. Remove version info from head and feeds
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
            // Check if the method exists before trying to remove it
            if (method_exists($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style')) {
                remove_action('wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style'));
            }
        }
    });

    // 8. Remove wp-version number params from scripts and styles (scoped)
    // This was already done earlier with increased priority, keeping this as a fallback with lower priority
    add_filter('script_loader_src', function($src) {
        if (strpos($src, 'ver=') !== false) {
             $src = remove_query_arg('ver', $src);
        }
        return $src;
    }, 9999);
    add_filter('style_loader_src', function($src) {
         if (strpos($src, 'ver=') !== false) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }, 9999);


    // --- End user-requested optimizations prototype --- //

    // --- Improved Minify & Combine CSS/JS (production-grade) --- //
    add_action('wp_enqueue_scripts', function() {
        $opts = get_option('wpso_options');
        // Only run on the frontend and if minify or combine is enabled
        if (!is_admin() && (!empty($opts['minify']) || !empty($opts['combine']))) {
            global $wp_styles, $wp_scripts;
            static $done = false;
            if ($done) return; // Prevent double run
            $done = true;

            // Skip admin bar styles/scripts and known problematic handles
            $skip_handles = ['admin-bar', 'dashicons', 'wp-admin',
                             'contact-form-7', 'wpcf7-recaptcha', // Added Contact Form 7 scripts
                             'easy-wp-smtp-script' // Potential Easy WP SMTP script handle
                            ];
             $skip_handles = apply_filters('wpso_combine_minify_skip_handles', $skip_handles);


            // --- Process CSS --- //
            $css_to_process = [];
            $seen_css_src = [];

            // Identify CSS to process
             foreach ($wp_styles->queue as $handle) {
                // Skip handles in the skip list
                if (in_array($handle, $skip_handles, true) || in_array($handle, $wp_styles->registered['admin-bar']->deps ?? [], true)) {
                    continue;
                }
                $style_obj = $wp_styles->registered[$handle] ?? null;
                $src = $style_obj->src ?? '';
                 if ($src && strpos($src, '.css') !== false && !isset($seen_css_src[$src])) {
                    $css_to_process[$handle] = $style_obj;
                    $seen_css_src[$src] = true; // Mark source as seen
                }
             }

            if (!empty($opts['combine'])) {
                // --- Combine CSS --- //
                $combined_css = '';
                foreach ($css_to_process as $handle => $style_obj) {
                    $src = wpso_resolve_url($style_obj->src);
                    if ($src) {
                        // Use wp_remote_get for potentially better handling of remote URLs
                        $response = wp_remote_get($src);
                        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                             $combined_css .= wp_remote_retrieve_body($response);
                        } else {
                            // Fallback to file_get_contents if wp_remote_get fails or is remote
                             $content = @file_get_contents($src);
                             if ($content !== false) {
                                 $combined_css .= $content;
                             } else {
                                 // Log or handle error if fetching fails
                             }
                        }
                    }
                     wp_dequeue_style($handle); // Dequeue original style
                }

                if ($combined_css) {
                    if (!empty($opts['minify'])) $combined_css = wpso_minify_css($combined_css);
                    add_action('wp_head', function() use ($combined_css) {
                        echo '<style id="wpso-combined-css">'.$combined_css.'</style>';
                    }, 99);
                }
            } elseif (!empty($opts['minify'])) {
                // --- Minify Individual CSS --- //
                foreach ($css_to_process as $handle => $style_obj) {
                    $src = wpso_resolve_url($style_obj->src);
                    if ($src) {
                        $css = '';
                         $response = wp_remote_get($src);
                         if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                             $css = wp_remote_retrieve_body($response);
                         } else {
                             $css = @file_get_contents($src);
                         }

                        if ($css !== false) {
                             $css = wpso_minify_css($css);
                             add_action('wp_head', function() use ($css, $handle) {
                                echo '<style id="wpso-minified-css-'.$handle.'">'.$css.'</style>';
                            }, 99);
                            wp_dequeue_style($handle); // Dequeue original style
                        }
                    }
                }
            }

            // --- Process JS --- //
            $js_to_process = [];
            $seen_js_src = [];

            // Identify JS to process
            foreach ($wp_scripts->queue as $handle) {
                // Skip handles in the skip list
                 if (in_array($handle, $skip_handles, true) || in_array($handle, $wp_scripts->registered['admin-bar']->deps ?? [], true)) {
                    continue;
                }
                $script_obj = $wp_scripts->registered[$handle] ?? null;
                $src = $script_obj->src ?? '';
                 if ($src && strpos($src, '.js') !== false && !isset($seen_js_src[$src])) {
                    $js_to_process[$handle] = $script_obj;
                    $seen_js_src[$src] = true; // Mark source as seen
                }
            }

            if (!empty($opts['combine'])) {
                // --- Combine JS --- //
                $combined_js = '';
                foreach ($js_to_process as $handle => $script_obj) {
                    $src = wpso_resolve_url($script_obj->src);
                    if ($src) {
                         $response = wp_remote_get($src);
                         if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                             $combined_js .= wp_remote_retrieve_body($response) . "\n";
                         } else {
                              $content = @file_get_contents($src);
                             if ($content !== false) {
                                 $combined_js .= $content . "\n";
                             } else {
                                 // Log or handle error if fetching fails
                             }
                         }
                    }
                    wp_dequeue_script($handle); // Dequeue original script
                }

                if ($combined_js) {
                    if (!empty($opts['minify'])) $combined_js = wpso_minify_js($combined_js);
                    add_action('wp_footer', function() use ($combined_js) {
                        echo '<script id="wpso-combined-js">'.$combined_js.'</script>';
                    }, 99);
                }
            } elseif (!empty($opts['minify'])) {
                 // --- Minify Individual JS --- //
                foreach ($js_to_process as $handle => $script_obj) {
                    $src = wpso_resolve_url($script_obj->src);
                    if ($src) {
                        $js = '';
                         $response = wp_remote_get($src);
                         if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                             $js = wp_remote_retrieve_body($response);
                         } else {
                              $js = @file_get_contents($src);
                         }

                        if ($js !== false) {
                            $js = wpso_minify_js($js);
                            add_action('wp_footer', function() use ($js, $handle) {
                                echo '<script id="wpso-minified-js-'.$handle.'">'.$js.'</script>';
                            }, 99);
                            wp_dequeue_script($handle); // Dequeue original script
                        }
                    }
                }
            }
        }
    }, 999); // High priority to run after most scripts/styles are enqueued

    // --- Helper: Resolve relative URLs to absolute file paths (production-grade) --- //
    function wpso_resolve_url($src) {
        // If it looks like a local path already, return it
        if (strpos($src, ABSPATH) === 0 || strpos($src, WP_CONTENT_DIR) === 0) {
            return $src;
        }

        // Ensure scheme for remote URLs
        if (strpos($src, '//') === 0) $src = (is_ssl() ? 'https:' : 'http:') . $src;

        // If it's a remote URL, return it. Using wp_remote_get is preferred over file_get_contents for remote.
        if (strpos($src, 'http://') === 0 || strpos($src, 'https://') === 0) {
             return $src;
        }

        // Attempt to convert relative paths to absolute file paths
        $site_url = site_url();
        $content_url = content_url();
        $plugins_url = plugins_url();
        $theme_root_url = get_theme_root_uri();

        if (strpos($src, $content_url) === 0) {
            $rel = substr($src, strlen($content_url));
            $file = WP_CONTENT_DIR . $rel;
            if (file_exists($file)) return $file;
        } elseif (strpos($src, $plugins_url) === 0) {
             $rel = substr($src, strlen($plugins_url));
            $file = WP_PLUGIN_DIR . $rel;
            if (file_exists($file)) return $file;
        } elseif (strpos($src, $theme_root_url) === 0) {
             // This is tricky as theme_root_uri might be relative or absolute
             // A more robust way is to check against get_template_directory_uri() and get_stylesheet_directory_uri()
             $template_dir_uri = get_template_directory_uri();
             $stylesheet_dir_uri = get_stylesheet_directory_uri();

             if (strpos($src, $template_dir_uri) === 0) {
                 $rel = substr($src, strlen($template_dir_uri));
                 $file = get_template_directory() . $rel;
                 if (file_exists($file)) return $file;
             } elseif (strpos($src, $stylesheet_dir_uri) === 0) {
                 $rel = substr($src, strlen($stylesheet_dir_uri));
                 $file = get_stylesheet_directory() . $rel;
                 if (file_exists($file)) return $file;
             }
        } elseif (strpos($src, $site_url) === 0) {
            // Catch any other site URLs, try to map to ABSPATH
             $rel = substr($src, strlen($site_url));
             // Add leading slash if missing
             if (strpos($rel, '/') !== 0) $rel = '/' . $rel;
             $file = ABSPATH . $rel;
             // Basic check to prevent directory traversal
             if (strpos($file, '..') === false && file_exists($file)) {
                 return $file;
             }
        }


        // If all attempts to resolve to a local file path fail, return the original source
        // This means we couldn't confirm it's a local file or it doesn't exist at the mapped path.
        // The combine/minify logic will then attempt wp_remote_get.
        return $src;
    }


    // --- Simple Minifiers (prototype) --- //
    function wpso_minify_css($css) {
        // Preserve comments that start with ! (often used for licensing)
        $css = preg_replace('/\/\*(?!\!).*?\*\//s', '', $css); // Remove non-important comments
        $css = preg_replace('/\s+/', ' ', $css); // Collapse whitespace
        $css = preg_replace('/\s*([{}|:;,])\s+/', '$1', $css); // Remove space around punctuation
        $css = preg_replace('/;}/', '}', $css); // Remove trailing semicolons in blocks
        $css = trim($css); // Trim leading/trailing whitespace
        return $css;
    }
    function wpso_minify_js($js) {
        // This is a very basic JS minifier. For production, a dedicated library is recommended.
        // It primarily removes comments and collapses whitespace.
        $js = preg_replace('/\/\*(?!\!).*?\*\//s', '', $js); // Remove multi-line comments (excluding important ones)
        $js = preg_replace('/\/\/.*$/m', '', $js); // Remove single-line comments
        $js = preg_replace('/\s+/', ' ', $js); // Collapse whitespace
         $js = trim($js); // Trim leading/trailing whitespace
        return $js;
    }
    // --- End minify/combine prototype --- //

    // --- Preconnect & Preload (prototype) --- //
    add_action('wp_head', function() {
        $opts = get_option('wpso_options');
        // Preconnect
        if (!empty($opts['preconnect'])) {
            $origins = array_map('trim', explode(',', $opts['preconnect']));
            foreach ($origins as $origin) {
                // Basic validation
                if (filter_var($origin, FILTER_VALIDATE_URL)) {
                    echo '<link rel="preconnect" href="'.esc_url($origin).'" crossorigin />'."\n";
                }
            }
        }
        // Preload
        if (!empty($opts['preload'])) {
            $assets = array_map('trim', explode(',', $opts['preload']));
            foreach ($assets as $asset) {
                if ($asset) {
                    // Determine 'as' attribute
                    $as = 'auto';
                    if (preg_match('/\.(woff2?|ttf|otf|eot|svg)$/i', $asset)) $as = 'font';
                    elseif (preg_match('/\.js$/i', $asset)) $as = 'script';
                    elseif (preg_match('/\.css$/i', $asset)) $as = 'style';
                    elseif (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $asset)) $as = 'image';

                    // Add crossorigin for fonts unless they are from the same origin (usually not the case for preloaded fonts)
                    $crossorigin = ($as === 'font') ? ' crossorigin' : '';

                    echo '<link rel="preload" href="'.esc_url($asset).'" as="'.$as.'"'.$crossorigin.' />'."\n";
                }
            }
        }
    }, 2); // Early in <head>
    // --- End preconnect/preload prototype --- //

    // --- Heartbeat API Control (prototype) --- //
    add_filter('heartbeat_settings', function($settings) {
        $opts = get_option('wpso_options');
        // Only apply settings in the admin area
        if (!is_admin()) {
            // If disabling on frontend is intended, handle it here specifically,
            // but heartbeat primarily affects the admin dashboard and post editor.
            // For now, restrict control to admin.
            return $settings;
        }

        $heartbeat_setting = $opts['heartbeat'] ?? 'normal';

        if ($heartbeat_setting === 'normal') {
            return $settings;
        } elseif ($heartbeat_setting === 'throttle') {
            $settings['interval'] = 60; // 1 request per minute
        } elseif ($heartbeat_setting === 'disable') {
            // Setting a very high interval effectively disables it, but wp_deregister_script is more direct.
            $settings['interval'] = 600; // 1 request per 10 minutes (effectively disabled for most use cases)
        }
        return $settings;
    });

     // Deregister heartbeat script entirely if disabled option is chosen
    add_action('init', function() {
        $opts = get_option('wpso_options');
         // Apply only in admin if the disable option is chosen
        if (is_admin() && !empty($opts['heartbeat']) && $opts['heartbeat'] === 'disable') {
            wp_deregister_script('heartbeat');
        }
         // If you wanted to disable heartbeat on the frontend too (less common), add logic here:
         // if (!is_admin() && !empty($opts['heartbeat']) && $opts['heartbeat'] === 'disable') {
         //     wp_deregister_script('heartbeat');
         // }

    });
    // --- End Heartbeat API control prototype --- //

    // --- Critical CSS UI (global + per-page) --- //
    add_action('add_meta_boxes', function() {
        // Add metabox for posts and pages by default, can be filtered
        $screens = ['post', 'page'];
        $screens = apply_filters('wpso_critical_css_metabox_screens', $screens);
        foreach ($screens as $screen) {
             add_meta_box('wpso_critical_css', 'WP Speed Optimizer: Critical CSS', 'wpso_perpage_critical_css_metabox', $screen, 'side', 'default');
        }
    });
    function wpso_perpage_critical_css_metabox($post) {
        $css = get_post_meta($post->ID, '_wpso_critical_css', true) ?: '';
        // Use nonce for security
        wp_nonce_field('wpso_save_critical_css', 'wpso_critical_css_nonce');
        echo '<textarea name="wpso_critical_css" rows="6" style="width:100%" placeholder="Paste critical CSS for this page only">'.esc_textarea($css).'</textarea>';
        echo '<br><small>Overrides global critical CSS for this page.</small>';
    }
    add_action('save_post', function($post_id) {
        // Check if our nonce is valid
        if (!isset($_POST['wpso_critical_css_nonce']) || !wp_verify_nonce($_POST['wpso_critical_css_nonce'], 'wpso_save_critical_css')) {
            return $post_id;
        }

        // If this is an auto save routine, no need to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // Check the user's permissions.
        if ('page' === $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return $post_id;
            }
        }

        // Sanitize and save the data.
        if (isset($_POST['wpso_critical_css'])) {
            $css = wp_kses_post($_POST['wpso_critical_css']); // Sanitize
            update_post_meta($post_id, '_wpso_critical_css', $css);
        } else {
             // If the field was not set, remove the meta key
             delete_post_meta($post_id, '_wpso_critical_css');
        }
    });

     // Output critical CSS in head
    add_action('wp_head', function() {
        $critical_css = '';
        // Check for per-page critical CSS first on singular views
        if (is_singular()) {
            $post_id = get_queried_object_id();
            $per_page_css = get_post_meta($post_id, '_wpso_critical_css', true);
            if ($per_page_css) {
                $critical_css = $per_page_css;
            }
        }

        // If no per-page CSS, try global CSS
        if (empty($critical_css)) {
             $opts = get_option('wpso_options');
            if (!empty($opts['critical_css'])) {
                $critical_css = $opts['critical_css'];
            }
        }

        // Apply filter to allow other sources to inject critical CSS
        $critical_css = apply_filters('wpso_critical_css', $critical_css);

        // Output the critical CSS if available
        if ($critical_css) {
            echo "<style id='wpso-critical-css'>\n" . $critical_css . "\n</style>\n";
        }
    }, 1); // Priority 1 to be very early

    // --- Auto-generate Critical CSS (Node.js 'critical' integration, prototype/experimental) --- //
    add_action('save_post', function($post_id) {
        // Only for public posts/pages and if the critical CSS field is empty for this post
        if (wp_is_post_revision($post_id) || get_post_status($post_id) !== 'publish') return;

        // Only auto-generate if there's no manual critical CSS set for this post
        $manual_css = get_post_meta($post_id, '_wpso_critical_css', true);
        if (!empty($manual_css)) return;

        // *** IMPORTANT CORRECTION ***
        // Check if shell_exec and exec are available and not disabled
        if (!function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
             // shell_exec is disabled. Cannot run critical.
             // Optionally add an admin notice here, but not during save_post to avoid issues.
            return;
        }
         if (!function_exists('exec') || in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
             // exec is disabled. Cannot run critical.
            return;
        }


        $url = get_permalink($post_id);
        // Ensure it's a valid public URL
        if (!$url || is_wp_error($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return;
        }


        $output_file = sys_get_temp_dir() . '/wpso_critical_' . $post_id . '.css';
        $cmd = 'command -v critical'; // Check if 'critical' command exists
        $has_critical = trim(shell_exec($cmd));

        if ($has_critical) {
            // Try to generate critical CSS
            // Added quotes around URLs and paths to handle spaces or special characters safely
            $cmd = 'critical --minify --width 1300 --height 900 ' . escapeshellarg($url) . ' --extract --inline false --timeout 30000 --output ' . escapeshellarg($output_file) . ' 2>&1'; // Redirect stderr to stdout for error capture
            $output = [];
            $ret = 0;
            @exec($cmd, $output, $ret);

            if ($ret === 0 && file_exists($output_file)) {
                $css = file_get_contents($output_file);
                if ($css !== false && !empty($css)) {
                    update_post_meta($post_id, '_wpso_critical_css', $css);
                }
                @unlink($output_file); // Clean up the temporary file
            } else {
                 // Log the error output from the command
                 error_log('WP Speed Optimizer Critical CSS Generation Failed for URL: ' . $url . ' Command: ' . $cmd . ' Return Code: ' . $ret . ' Output: ' . implode("\n", $output));
            }
        } else {
             // Log that the 'critical' command was not found
             error_log('WP Speed Optimizer: Node.js Critical CSS tool not found on the server path. Critical CSS auto-generation disabled.');
             // You might want to set a transient or option to prevent checking this on every save if it's not found.
        }
    }, 20); // Run after default save

    // --- Move CSS to Footer (experimental/advanced) --- //
    add_action('wp_enqueue_scripts', function() {
        $opts = get_option('wpso_options');
        if (!empty($opts['move_css_footer']) && !is_admin()) {
            global $wp_styles;
             // Handles to exclude from moving to footer
            $skip_handles = ['admin-bar', 'dashicons', 'wp-admin', 'wpso-critical-css'];
             $skip_handles = apply_filters('wpso_move_css_footer_skip_handles', $skip_handles);

            foreach ($wp_styles->queue as $handle) {
                // Don't move handles in the skip list
                if (in_array($handle, $skip_handles, true)) continue;
                $wp_styles->add_data($handle, 'group', 1); // Group 1 outputs in footer
            }
        }
    }, 1001); // Run with high priority

    // --- HTML Minification (experimental) --- //
    add_action('template_redirect', function() {
        $opts = get_option('wpso_options');
        if (!empty($opts['minify_html']) && !is_admin()) {
             // Check if an output buffer is already active (less likely to conflict if we are the first or only one)
             // This is a basic check, not foolproof against complex OB interactions.
            if (ob_get_level() === 0 || !in_array('wpso_minify_html', ob_list_handlers())) {
                ob_start('wpso_minify_html');
            } else {
                 // Another output buffer is active. Decide how to handle.
                 // For now, skip minification to avoid conflicts.
                 // A more advanced approach would chain buffers or use a different hook/method.
                 error_log('WP Speed Optimizer: HTML minification skipped due to existing output buffer.');
            }
        }
    }, -1); // Early priority to be one of the first buffer handlers

    function wpso_minify_html($html) {
        // Ensure we only process non-empty strings
        if (empty($html) || !is_string($html)) {
            return $html;
        }
        // Check if it's not a feed or other non-html content
        if (is_feed() || is_robots() || is_archive() || is_search() || is_404()) {
             return $html; // Don't minify non-standard HTML output
        }
         if (defined('REST_REQUEST') && REST_REQUEST) {
             return $html; // Don't minify REST API responses
         }
         if (defined('DOING_AJAX') && DOING_AJAX) {
             return $html; // Don't minify AJAX responses
         }
         // Add more checks for specific pages or content types if needed

        // Basic HTML Minification (removes whitespace between tags, line breaks, and extra spaces)
        // This regex is aggressive and might break some complex HTML/JS embedded in HTML.
        $html = preg_replace('/>\s+</', '><', $html);
        $html = preg_replace('/\s{2,}/', ' ', $html);
        $html = str_replace(["\r", "\n", "\t"], '', $html);
        return $html;
    }

    // --- Move JS to Footer (experimental/advanced) --- //
    add_action('wp_enqueue_scripts', function() {
        $opts = get_option('wpso_options');
        if (!empty($opts['move_js_footer']) && !is_admin()) {
            global $wp_scripts;
             // Handles to exclude from moving to footer
            $skip = [
                'jquery', 'jquery-core', 'jquery-migrate', 'wp-polyfill', 'wp-hooks', 'wp-i18n',
                'contact-form-7', 'wpcf7-recaptcha', // Added Contact Form 7 scripts
                'easy-wp-smtp-script', // Potential Easy WP SMTP script handle (verify if needed)
                // Add more handles here if needed via filter
            ];
             $skip = apply_filters('wpso_move_js_footer_skip_handles', $skip);


            foreach ($wp_scripts->queue as $handle) {
                // Don't move handles in the skip list or those already in footer group (shouldn't happen if this runs first)
                if (in_array($handle, $skip, true) || (isset($wp_scripts->registered[$handle]->extra['group']) && $wp_scripts->registered[$handle]->extra['group'] === 1)) continue;

                 // Ensure the handle exists and has a source before trying to add_data
                if (isset($wp_scripts->registered[$handle]) && $wp_scripts->registered[$handle]->src) {
                     $wp_scripts->add_data($handle, 'group', 1); // Group 1 outputs in footer
                }
            }
        }
    }, 1002); // Run with very high priority
}

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
