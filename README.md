# OAP
"Don's Optimization Anthology Plugin" (abbrev.: OAP)

An "anthology" of (IMO) some snazzy Wordpress function-codes that I've come across over time, which I (earlier) used to optimize Wordpress-installations with.

The following functions, which this plugin quietly performs, are (as of v.2.0.0, 14/05/2025):
* Removes emoji scripts/styles
* Disables embeds
* Strips query strings from static resources
* Removes WordPress version from head
* Inlines above-the-fold critical CSS (filter: `wpso_critical_css`)
* Defers non-essential JS for faster first paint
* Removes wp-version number params from scripts and styles
* Removes Google Fonts (for most themes/plugins)
* Prevents SSL test on xmlrpc.php
* Removes version info from feeds
* Removes unnecessary header info (RSD, wlwmanifest, shortlink, REST, feeds, adjacent posts)
* Disables pingback and trackback notifications
* Disables self-pingbacks
* Removes extra CSS from Recent Comments widget
* Minifies CSS & JS (production-grade)
* Combines CSS & JS (production-grade)
* Adds <preconnect> for user-specified origins
* Adds <preload> for user-specified assets
* Heartbeat API control (normal/throttle/disable)
* Minimalist settings page for toggling features
* Global and per-page critical CSS UI
* Auto-generates critical CSS using Node.js 'critical' if available (prototype/experimental)
* Optionally moves all CSS files to the footer (experimental, may cause FOUC)
* Optionally minifies HTML output (experimental)
* Toggle to allow or block Google Fonts for privacy & speed
* Optionally moves all JavaScript files to the footer (experimental, may break some plugins/themes)


Installation:

You can install this plugin via the WordPress admin panel.

1. Download the latest zip of this repo (the latest version is always the (only) one which is available).
2. In your WordPress admin panel, navigate to Plugins->"Add New"
3. Click "Upload Plugin"
4. Upload the zip file that you downloaded.
5. Activate from from the admin-panel

Configuration:
Go to “Settings > WP Speed Optimizer” for the configuration of the plugin and it's optional functionalities.

N.B.: Use a tool-page as https://w3speedup.com/tools/critical-css-generator/ (or similar) to generate the critical CSS to insert in the setting's field for this setting.


Credits:

This Wordpress-plugin is compiled and effectuated by various pieces of code that herald fom various sources/authors.
These various sources are credited within this plugin's source-code, and must NOT be removed. 
This plugin's collecton of code used herein is collected, "compiled" and assembled by this author (with some assistance from the "Autonomous Dev" - THANKS!).


Changelog:
2.0.0 : May 14th, 2025

* Critical CSS UI (global) & Above-the-fold optimization: critical CSS inlining and JS defer - both reinstated

* User-requested: Google Fonts removal, SSL test prevention, feed version removal, header cleanup, pingback/trackback/self-pingback disabling, Recent Comments CSS removal, and version param cleanup 

* Minify/combine CSS & JS, preconnect, preload, Heartbeat API control, and settings page (all prototype) 

* Production-grade minify/combine 

* and auto-generation via Node.js 'critical' (prototype/experimental) 

* Added experimental option to move CSS files from header to footer 

* Added HTML minification (removes whitespace and line-breaks) 

* Added toggle to allow or block Google Fonts 

* Added option to move JavaScript files from header to footer 

1.0.8 : May 5th, 2023
- Removed all code pertaining to the CSS "Above The Fold" and the CSS-defering (for now)
- Updated CDN-URLS and their respective SRI-hashes to updated jQuery-libraries 
- Code cleaned up, refactored and (slightly) restructured

1.0.7 : November 5th, 2022

Updated CDN-URLS and their respective SRI-hashes to updated jQuery-libraries and corrected a few minor typos in the comments 

1.0.6 : August 14th, 2022

Added code to defer loading of javascript 

1.0.5 : June 25th, 2022

Google Analytics v.4.0 (GA4)-support and Google Tag Manager-support now implemented and activated also as an inserted varible-string (although Google Tag Manager is optional/commented out) 


1.0.4 : June 25th, 2022

Moved the "CSS Above The Fold" up in the code and the generated CSS is now inserted as a varible in the code 


1.0.3 : May 15th, 2022  
1. Missing "data-instant-intensity"-parameter with the value="viewport" added to activate the "instant.page"-script (Sorry, I forgot to add this initially!)
2. Script to find loaded scripts and styles (Source: wpbeaches.com) added - commented out but can used to find which scripts and style are used - solely here for optimization-purposes


1.0.2 : November 15th, 2021  
Automatic meta-description creator from post-content now added during the Summer of '21 

1.0.1 : May 1st, 2021  
Miniscule optimization update: The registering and enquing of the core jQuery-lib is now updated to use v3.6.0 (the URI and the SRI has been updated). 

1.0.0 : March 30th, 2021  
No major changes implemented (actually none), and now elevated to a fully-fledged, official release. 

1.0.0 [RC-2]: March 9th, 2021  
1. Code cleaned up, refactored and restructured (actually: "Grouped and reordered")
2. Some earlier experimental-/trial-code performed prior to RC-2 now removed 
3. JPEG-sharpening code removed
4. Although not required/necessary in Wordpress v5.5.X, I've included jQuery Migrate 3.3.2 from cdnjs.com (albeit commented out), if needed due to elderly plugins, themes etc. 

1.0.0 [RC-1]: January 25th, 2021  
1. Pingbacks and Self-Pingbacks disabled

1.0.5ß: January 19th, 2021
1. Removed redundant and/or commented out code, which wasn't used, obsolete or troublesome, as previously announced
2. Refactored the code's comments and removed typos, missing words(!) and bad grammar
3. Corrected the wording for the plugin's description seen on the Wordpress-installation's plugins-page.
4. Getting things ready so the plugin can now evolve to "Release Candidate" (RC)-status
 
1.0.4ß: January 18th, 2021
1. Matthew Horne's async-/defer-script related to adding defer/async to javascript-URIs has been commented out, soon to be removed
2. Added code which removes all RECAPTCHA- and WPFC7-code from ALL pages except on the "Contact"-page, where it's needed
3. Added "graphics" to seperate the various code blocks and corrected some discovered typos along the way
4. Changed code that removes parameters/versions/scopes from scripts and styles has replaced by another more effective and solid one that renders the same function
  
1.0.3ß: January 18th, 2021
1. Additional code added, code removing version info from head and feeds and code that prevents WordPress from testing ssl capability on domain.com/xmlrpc.php?rsd 
2. Additonal/more specifically, detailed author-references added (where possibile)
3. PHP 8 bug-warning regarding the code, which checks for/enables GZIP output compression - currently throws an error... 

1.0.2ß: January 17th, 2021
1. Hunted down and removed/fixed some optimization bugs and minor bottle-necks
2. Added comments referencing were the a newly-added code was found/heralds from
3. Cleaned up/tweaked a little bit/commented on Matthew Horne's async-/defer-script
4. Commented out strings of code which either isn't used currently (due the used theme/plugins) - or not necessary at all
5. Added cherryaustin's code-solution in adding SRI-strings and parameters to URI's, due to safety-concerns and to prevent potential safety issues
6. Changed the code to inject the "instant.page"-script URI (For further details: https://instant.page) by using Wordpress "wp_register_script"- and "wp_enqueue_script"-functions, instead.

1.0.1ß: January 16th, 2021
Added code to inject the "instant.page"-script URI (For further details: https://instant.page)

1.0.0ß: January 7th, 2021
"Don's Optimizaion Anthology Plugin (OAP)" generated and initialized

License:

Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International (https://creativecommons.org/licenses/by-nc-sa/4.0/)

Furthermore, the Creative Commons License shown above regarding the template(s) is here extended by the following limitions pertaining to warranty and liabiltiy, in which also the following also applies:

THIS SOFTWARE (I.E.: THIS PLUGIN) IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESSED OR IMPLIED, INCLUDING - BUT NOT LIMITED TO - THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.

IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.</p>

By obtaining and using the here-provided software, the user - implicitly - acknowledges and accepts the terms and conditions stated above regarding the aforementioned software.
