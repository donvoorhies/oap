# OAP
"Don's Optimization Anthology Plugin" (abbrev.: OAP)

An "anthology" of (IMO) some snazzy Wordpress-functions that I've across over time, which I use to optimize my Wordpress-installations.

The following functions this plugin quietly performs are (for now, as more functions might be added):
- Checks if Server-side compression is switched on
- Links commonly-used jQuery-libs to a CDN
- Removing versions + params on URI's
- Sharpen resized image files
- Deferring/async js-files
- Embedding "Above The Fold" Critical Path CSS,  defering CSS-files () and moving js from head to the footer
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

Follow the instructions here, and copy and paste the generated string into the file (OAP_plugin.php; line 158 - or thereabouts) between the marked "style"-tags (look for these, along with the identifiying comment:
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
