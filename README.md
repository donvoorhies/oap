# OAP
"Don's Optimization Anthology Plugin" (abbrev.: OAP)
An "anthology" of (IMO) some snazzy Wordpress-functions that I've across over time, which I use to optimize my Wordpress-installations.

The following funcions this plugin quietly performs are (for now, as more functions might be added):
- Checks if Server-side compression is switched on
- Links commonly-used jQuery-libs to a CDN
- Removing versions + params on URI's
- Sharpen resized image files
- Deferring/async js-files
- Embedding "Above The Fold" Critical Path CSS,  defering CSS-files () and moving js from head to to footer
- Minifying all HTML-used, by stripping line-breaks and unnecessary white-spaces 

Installation
You can install this plugin via the WordPress admin panel.

1. Download the latest zip of this repo.
2. In your WordPress admin panel, navigate to Plugins->Add New
3. Click Upload Plugin
4. Upload the zip file that you downloaded.
5. Configuration

This plugin will work right out of the box - almost...
For now, you're going to have get the "Above The Fold" Critical Path CSS, and insert the generated string into the code in file.
You can find the "Above The Fold" Critical Path CSS-string by using Jonas Sebastian Ohlsson's generator at https://jonassebastianohlsson.com/criticalpathcssgenerator/
Follow the instructions here, and copy and paste the generated string into the file between the marked "style"-tags (look for these, along with the identifiying comment);
you can use your webhost's editor to perform this operation. 

Planned/"Wish-list"
Future versions of this plugin might have implemented provisions to ease this process by inserting the generated "Above The Fold" Critical Path CSS-string via the admin-panel.

Credits
This Wordpress-plugin is compiled and effectuated by various pieces of code that herald fom various sources/authors.
These various sources are credited within this plugin's source-code, and must NOT be removed. 
This plugin's collecton of code used herein is collected, "compiled" and assembled by this author.


Changelog
1.0.0ß: January 7th, 2021
"Don's Optimizaion Anthology Plugin (OAP)" generated and initialized

License
Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International (https://creativecommons.org/licenses/by-nc-sa/4.0/)
