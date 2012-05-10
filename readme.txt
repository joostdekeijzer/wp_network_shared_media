=== Netword Shared Media ===

Contributors: Joost de Keijzer, Aaron Eaton
Tags: multisite, media
Requires at least: 3.3
Tested up to:  3.3
Stable tag: 0.6

Allows you to use media from other blogs in a Multisite environment.

== Description ==

This plugin adds a new tab to the "Add Media" window, allowing you to access media in other blogs.

Based on http://blog.channeleaton.com/sharing-media-libraries-across-network-sites

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
