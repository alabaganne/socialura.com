=== Media Sync ===
Contributors: erolsk8, simonkane
Donate link: https://mediasyncplugin.com/?utm_source=readme&utm_medium=base_plugin&utm_campaign=donate_link
Tags: media, uploads, import, ftp, server
Requires at least: 5.3
Tested up to: 6.8
Requires PHP: 7.1
Stable tag: 1.4.8
License: GPLv2+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simple plugin to scan "uploads" directory and bring those files into Media Library.

== Description ==

This plugin allows you to examine all files within the `uploads` directory to determine which ones are present in the Media Library and which ones are just sitting there unused. You can then choose the files you want to import into the database, thereby including them in the Media Library.

Moreover, you can utilize FTP to upload files directly to the `uploads` directory and subsequently add these files to the Media Library avoiding any file size limitations.


= Disclaimers =

1.  "1 file first"
    Please try to import only one file first - to see if it works as you expected.

2.  "All at once"
    This plugin is designed for scanning, selecting, and importing **all files at once**. However, based on your server's configuration, memory, and timeout challenges may arise with extensive file quantities. To mitigate this, a newly revamped [pro version](https://mediasyncplugin.com/?utm_source=readme&utm_medium=base_plugin&utm_campaign=aao) employs incremental directory scans to effectively tackle these issues.

3.  "Your setup is unique"
    Please be aware that every WordPress installation is unique, and there may be instances where this plugin does not function as expected. Should this occur, we recommend enabling the debugging feature in the plugin's settings to identify the issue. After investigating, kindly provide a detailed description of your findings in the Support section (or [here](https://users.freemius.com/store/6428/support) if you're using pro version). The more comprehensive the details, the higher the likelihood of resolving the problem effectively.


= Ignored files =
- various hidden files (.DS_Store, .htaccess),
- WP generated thumbnails (files ending with for example -100x100.jpg),
- WP generated scaled images (files ending with -scaled),
- optimized .webp versions of original images (.jpg.webp),
- retina thumbnails (-100x100@2x.jpg).

These can be modified and enhanced using the new advanced filters available in the [pro version](https://mediasyncplugin.com/?utm_source=readme&utm_medium=base_plugin&utm_campaign=df).

= Media Sync Pro features =
- **Revised incremental scan**: Allows scanning and importing unlimited number of files.
- **Quick single directory rescan**: Easily rescan one directory to find new files or apply a different filter without reloading the whole page.
- **Advanced filters**: Find any file by customizing all default filters, search for a specific file type (images, videos, etc.), skip by tailor-made rules, or enter any custom pattern.
- **Schedule automatic imports**: Select a desired interval and let the plugin automatically import any new files it finds.
- **Import logs**: View the history of manual or scheduled imports.
- **Limit plugin access**: Limit plugin access to a specific role.

Get [pro version here](https://mediasyncplugin.com/?utm_source=readme&utm_medium=base_plugin&utm_campaign=pfl).

== Installation ==

1. Upload `media-sync` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Initial Page
2. Example of selecting files for import
3. Import to Media Library in progress
4. Import completed

== Frequently Asked Questions ==

= Stuck at importing / spins endlessly =

Please try to increase `max_execution_time` (and/or `memory_limit`) in `php.ini` on server (as described [here](https://thimpress.com/knowledge-base/how-to-increase-maximum-execution-time-for-wordpress-site/)).

= Files not showing up in Media Library =

Please make sure "Dry Run" option is NOT checked. This is a safety mechanism to make sure you know what you're doing, so be careful, try it first with just one file.

= Doesn't work =

Please first try to turn on debugging in Settings -> Media Sync and check [Network tab in Chrome DevTools](https://www.section.io/blog/chrome-developer-tools-tutorial-network/) to see what is going on in the background. Then report actual errors since it's hard to help without knowing the error which is causing the problem.


== Changelog ==

= 1.4.8 =
* Tested for WordPress 6.8

= 1.4.7 =
* Replace loading of external script for purchasing Pro version with simple external link
* Tested for WordPress 6.7

= 1.4.6 =
* Minor maintenance adjustment to prepare promo banner for upcoming redesign
* Tested for WordPress 6.6.1

= 1.4.5 =
* Fix DOM IDs when scanning directories or files with special non-latin characters
* Bring back promo banner without remote fetch

= 1.4.4 =
* Decode `post_title` and `guid` in attachment data
* Tested for WordPress 6.5.5

= 1.4.3 =
* Disable fetching promo banner from remote origin in hopes of resolving 404 errors
* Load plugin only on admin pages
* Tested for WordPress 6.5.4
* Get proper error message from `wp_insert_attachment` function

= 1.4.2 =
* Fix enabling or disabling debugging option
* Fix various issues with paths on Windows servers
* Remove "Scan directory" option to simplify plugin maintenance and compatibility with Windows servers
* Tested for WordPress 6.5.3

= 1.4.1 =
* Fix deprecated warnings with PHP 8.1
* Increase required versions to WordPress 5.3 and PHP 7.1

= 1.4.0 =
* Add correct plugin version when loading assets and updated readme.txt.

= 1.3.3 =
* Fix detecting files that already in database when "Scan directory" option is used. Issue started in previous version (1.3.2).

= 1.3.2 =
* Fix handling file names with even rarer special characters that might have resulted in duplicate imports.

= 1.3.1 =
* Ignore optimized .webp versions of original images (e.g. .jpg.webp) and retina thumbnails (e.g. -100x100@2x.jpg) by default. It's still possible to customize it with `media_sync_filter_is_scan_object_ignored` hook.

= 1.3.0 =
* New option to set custom Batch Size. This might help with errors due to server limits (e.g. max_execution_time).

= 1.2.9 =
* Fix selecting multiple files without clicking on checkbox (e.g. shift-select).
* Minor code cleanup.
* Update supported WordPress version to 6.2.

= 1.2.8 =
* Create a custom `_msc` record in `wp_postmeta` table to be able to differentiate files imported using this plugin. Could be useful to clean up the database later on.

= 1.2.7 =
* Extend plugin access to "Editor" and "Author" roles.

= 1.2.6 =
* If debugging is turned on, AJAX requests for import are now `html` which is rendered directly in UI.

= 1.2.5 =
* New filter hook (`media_sync_filter_before_update_metadata`) which can be used to customize how metadata is updated or to run additional actions after file import.

= 1.2.4 =
* Handle files with quotes (apostrophes) or other special characters in the file name.
* Continue importing other selected files if there was an error with some of the files.
* Show errors in UI instead of alert.

= 1.2.3 =
* Better error handling and fallback when finding mime type

= 1.2.2 =
* Fix meta data errors caused by invalid mime types

= 1.2.1 =
* Much better error handling while importing

= 1.2.0 =
* Optimized directory scanning to use less memory
* New option to turn on debugging for this plugin
* Changed parameters passed to `media_sync_filter_is_scan_object_ignored` hook function
* Now requires PHP 5.5

= 1.1.8 =
* Fix handling big ("-scaled.jpg") images [introduced in WordPress 5.3](https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/). These files will now be skipped and won't be imported multiple times.
* Add handy "Re-scan" button.

= 1.1.7 =
* Fix issues when importing files containing special characters

= 1.1.6 =
* Slight improvements with error handling in JavaScript

= 1.1.5 =
* Always convert backslashes (`\`) to forward slashes (`/`) to fix various issues when using Windows Server.

= 1.1.4 =
* Important backslash (`\`) vs forward slash (`/`) fix for use on Windows Server.

= 1.1.3 =
* New option to set "Scan directory" in settings which will allow checking only certain sub directory.
* New hook function `media_sync_filter_is_scan_object_ignored` which can be used to overwrite which files are ignored by default or to just skip additional files.

= 1.1.2 =
* Fix Smart File Time on Windows server

= 1.1.1 =
* Reduce the maximum number of items to import per batch from 20 to 10.
So batch sizes are now: 1 (importing 1 to 10 items); 5 (importing 11 to 100 items) or 10 (importing more than 100 items)

= 1.1.0 =
* [IMPORTANT] Date of imported Media Library items now defaults to the current date.
But there are options to choose before importing and also a possibility to overwrite that using the custom hook.
* New options page with the option to disable and hide "Dry Run".
* Fix Media Library filter that was showing all items when the filter didn't find any result.

= 1.0.4 =
* Reduce the number of items to import per batch

= 1.0.3 =
* Support multisite network by changing required access [capability](https://wordpress.org/support/article/roles-and-capabilities/#capability-vs-role-table) from `update_plugins` to `import`

= 1.0.2 =
* Another fix for get_current_screen error

= 1.0.1 =
* Fix get_current_screen error

= 1.0.0 =
* New option to clean up Media Library from items that are missing actual files (using custom Media Library filter)
* New filter when scanning uploads directory which can help to show only files missing from Media Library

= 0.1.6 =
* Fix PHP short array syntax
* Update required PHP version to 5.4

= 0.1.5 =
* Date of imported Media Library item is now set based on file modification timestamp

= 0.1.4 =
* Add plugin localization
* Add Serbian translation

= 0.1.3 =
* Various improvements and fixes

= 0.1.2 =
* Fix sorting of directories and files
* Minor wording changes and code cleanup

= 0.1.1 =
* Fix error on activation

= 0.1.0 =
* Initial plugin features

== Upgrade Notice ==

= 0.1.0 =
Initial plugin features