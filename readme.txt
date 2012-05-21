=== Netword Shared Media ===
Contributors: joostdekeijzer
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=joost@dekeijzer.org&item_name=network-shared-media+Wordpress+plugin&item_number=Joost+de+Keijzer&currency_code=EUR
Tags: multisite, media
Requires at least: 3.3
Tested up to:  3.3
Stable tag: 0.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to use media from other blogs in a Multisite environment.

== Description ==

This plugin adds a new tab to the "Add Media" window, allowing you to access media in other blogs.

It can be very helpfull when you use http://wordpress.org/extend/plugins/multisite-language-switcher/ for setting up a multi-langual site as it prevents the editors from having to upload images twice.

Based on a blogpost by Aaron Eaton: http://blog.channeleaton.com/sharing-media-libraries-across-network-sites

== Installation ==

* download the plugin
* uncompress it with your preferred unzip programme
* copy the entire directory in your plugin directory of your wordpress blog (/wp-content/plugins)
* network-activate the plugin
* edit a post or create a new one
* insert media -> see the new tab!

== Changelog ==

= 0.6 =
* fixed bug where restore_current_blog would give unexpected results (when number of sites > 2)
* checking for correct perimissions: upload_files permission required for both the "active" site as the networked sites
* to reduce server load: choose blog to display media of (in stead of just showing them all)

= 0.5 =
* first public version
