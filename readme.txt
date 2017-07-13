=== Author Listing ===
Contributors: simonwheatley
Donate link: http://www.simonwheatley.co.uk/wordpress/
Tags: authors, users, profile
Requires at least: 2.6
Tested up to: 2.7-beta3-9909
Stable tag: 1.02

Allows listing of authors who are active or inactive within a defined recent period of time.

== Description ==

A plugin providing template tags to list all active (or inactive) authors in the WordPress installation.

`<?php list_active_authors( 'days=30&include_protected_posts=0' ); ?>` Will list all the authors active in the last 30 days, not counting password protected posts as "activity". Both parameters are optional.

`<?php list_inactive_authors( 'days=30&include_protected_posts=0' ); ?>` Will list all the authors NOT active in the last 30 days, counting password protected posts as "activity". Again, both parameters are optional.

If "days" are not provided the tags default to the last 30 days.
If "include_protected_posts" is not specified, the tags default to not including password protected posts.

The HTML output is fairly heavily classed, but if you need to adapt it you can. Create a directory in your *theme* called "view", and a directory within that one called "author-listings". Then copy the template files `view/author-listings/active-authors-list.php` and/or `view/author-listings/inactive-authors-list.php` from the plugin directory into your theme directory and amend as you need. If these files exist in these directories in your theme they will override the ones in the plugin directory. This is good because it means that when you update the plugin you can simply overwrite the old plugin directory as you haven't changed any files in it. All hail [John Godley](http://urbangiraffe.com/) for the code which allows this magic to happen. Can hook into the [User Photo plugin](http://wordpress.org/extend/plugins/user-photo/) to display the author photos.

Plugin initially produced on behalf of [Puffbox](http://www.puffbox.com).

Is this plugin lacking a feature you want? I'm happy to accept offers of feature sponsorship: [contact me](http://www.simonwheatley.co.uk/contact-me/) and we can discuss your ideas.

Any issues: [contact me](http://www.simonwheatley.co.uk/contact-me/).

== Installation ==

The plugin is simple to install:

1. Download `author-listings.zip`
1. Unzip
1. Upload `author-listings` directory to your `/wp-content/plugins` directory
1. Go to the plugin management page and enable the plugin
1. Give yourself a pat on the back

== Change Log ==

= v0.1b 2008/08/18 =

* Plugin first sees the light of day.

= v0.2b 2008/08/19 =

* Added more templating functions for the latest posts
* Revised the excerpt generation functions

= v0.3b 2008/08/20 =

* Added template methods to hook into the User Photo plugin

= v0.4b 2008/10/04 =

* Cleaned up the documentation, general housekeeping

= v1.0 2008/10/09 =

* First stable release!

= v1.01 2008/11/27 =

* BUGFIX: Previously if you listed inactive authors after having listed active authors, then all authors in the site would be listed (regardless of activity). This is now fixed.

= v1.02 2008/11/27 =

* BUGFIX: I left some error logging active in the plugin. This release removes it.
