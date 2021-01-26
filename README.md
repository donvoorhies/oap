# OAP
"Don's Optimization Anthology Plugin" (abbrev.: OAP)

An "anthology" of (IMO) some snazzy Wordpress-functions that I've come across over time, which I use to optimize my Wordpress-installations.

The following functions this plugin quietly performs are (as of v.1.0.0 [RC-1], 01/25/2021):
- Pingbacks and Self-Pingbacks disabled
- Removed (unnecessary) code and -functions in the header
- Making the RECAPTCHA- and WPCF7-code show and function exclusively on the contact page
- Links commonly-used jQuery-libs (in Wordpress) to a CDN
- Adds Alexandre Dieulot’s fascinating “instant.page”-functionality
- Adds SRI hashes and parameters on relevant URI’s (for obvious security reasons; if and where possible)
- Removing versions + params (scopes) on URI's
- Sharpen resized image files (jpeg)
- Deferring/async js-files
- Embedding "Above The Fold" Critical Path CSS, "defering" CSS-files and moving js from head to the footer
- Minifying all HTML-used, by stripping line-breaks and unnecessary white-spaces


Installation:

You can install this plugin via the WordPress admin panel.

1. Download the latest zip of this repo (the latest version is always the (only) one which is available).
2. In your WordPress admin panel, navigate to Plugins->"Add New"
3. Click "Upload Plugin"
4. Upload the zip file that you downloaded.
5. Activate from from the admin-panel

Configuration:

This plugin will work right out of the box - almost...

For now, you're going to have get hold of the "Above The Fold" Critical Path CSS, and insert the generated string into the code in file, OAP_plugin.php.
You can find the "Above The Fold" Critical Path CSS-string by using Jonas Sebastian Ohlsson's generator at: 

https://jonassebastianohlsson.com/criticalpathcssgenerator/

Follow the instructions here, and copy and paste the generated string into the file (OAP_plugin.php; line 236 - or thereabouts) between the marked "style"-tags (look for these, along with the identifiying comment:
"/* INSERT GENERATED "ABOVE THE FOLD" CRITICAL PATH CSS-STRING HERE - EITHER UNDER THIS LINE OR BY REPLACING THIS LINE WITH THE GENERATED STRING...! */
");
you can use your webhost's editor to perform this operation. 

Planned/"Wish-list":

Future versions of this plugin might have implemented provisions to ease this process by inserting the generated "Above The Fold" Critical Path CSS-string via the admin-panel.

Credits:

This Wordpress-plugin is compiled and effectuated by various pieces of code that herald fom various sources/authors.
These various sources are credited within this plugin's source-code, and must NOT be removed. 
This plugin's collecton of code used herein is collected, "compiled" and assembled by this author.


Changelog:
1.0.0 [RC-1]: January 25th, 2021  
- Pingbacks and Self-Pingbacks disabled

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

NOTE: PLEASE HAVE IN MIND THAT THIS PLUGIN CURRENTLY IS A RELEASE CANDIDATE (RC) - FOR BETTER OR WORSE!
