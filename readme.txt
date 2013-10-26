=== Network Shared Media ===
Contributors: joostdekeijzer
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=joost@dekeijzer.org&item_name=network-shared-media+WordPress+plugin&item_number=Joost+de+Keijzer&currency_code=EUR
Tags: multisite, network, wpmu, media, image, photo, picture, mp3, video, integration
Requires at least: 3.3
Tested up to:  3.7
Stable tag: 0.9.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to use media from other blogs in a Multisite environment.

== Description ==

This plugin adds a new tab to the "Add Media" window, allowing you to access media in other blogs.

It can be very helpful when you use [Multisite Language Switcher](http://wordpress.org/extend/plugins/multisite-language-switcher/) plugin for setting up a multilingual site as it prevents the editors from having to upload images twice.

Based on a blogpost by [Aaron Eaton](http://blog.channeleaton.com/sharing-media-libraries-across-network-sites).

Also see https://github.com/joostdekeijzer/wp_network_shared_media

== Installation ==

* Download the plugin
* Uncompress it with your preferred unzip programme
* Copy the entire directory in your plugin directory of your WordPress blog (/wp-content/plugins)
* Network-activate the plugin
* Edit a post or create a new one
* Open the Add Media popup -> see the new tab!

== Frequently Asked Questions ==

= About Permissions =

This plugin depends on the global WordPress blog permissions for viewing and editing Media.

Only blogs where the user has 'upload_files' permission are shown in the Network Shared Media tab.

If a user doesn't have the 'upload_files' permission on the current (active) blog, the tab isn't shown at all since that user can't access the Add Media pop-up window.

= I still can't see the site listed! =

A site can have several attributes which can be set through /wp-admin/network/sites.php .

This plugin only shows sites which attributes are set as follows:

* Public: true
* Archived: false
* Spam: false
* Delete: false
* Mature: ignored

== Screenshots ==
1. New tab added to the Add Media popup window
2. The Network Shared Media tab lists the media per site
3. Show a selected image and "Insert into Post"

== Changelog ==

= 0.9.4 =
* Minor bugfix for WP3.5 (remove the "Edit picture" button)

= 0.9.3 =
* Set is_admin() to true, fixing issues with Contact Form 7 (and possibly others)

= 0.9.2 =
* bugfix for cross-domain errors in "multiple domain" installations

= 0.9.1 =
* some speed improvements
* sorting of sites

= 0.9 =
* When many sites are available, the site-selection becomes a drop-down list (as suggested by SooBahkDo)
* Ignore "mature" site attribute
* tested with WP3.4RC

= 0.8 =
* fixed some embarrassing typos

= 0.7 =
* i18n (feel free to contact me for translations)
* Updated read-me
* Added screenshot

= 0.6 =
* fixed bug where restore_current_blog would give unexpected results (when number of sites > 2)
* checking for correct permissions: upload_files permission required for both the "active" site as the networked sites
* to reduce server load: choose blog to display media of (in stead of just showing them all)

= 0.5 =
* first public version
