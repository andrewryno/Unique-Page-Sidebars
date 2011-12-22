=== Unique Page Sidebars ===
Contributors: andrewryno
Donate link: http://andrewryno.com/plugins/unique-page-sidebars/
Tags: sidebars, dynamic sidebars, sidebar management, dynamic widgets, widgets per page, sidebars per page
Requires at least: 3.0
Tested up to: 3.3
Stable tag: 0.1

This plugin allows for the creation and management of widget sidebars on a per-page basis from a single dynamic_sidebar() call in your theme sidebar.

== Description ==

Over the years developing WordPress themes, I have had many designs that require a different sidebar for just about every page on the site. While it has been possible to accomplish this using a combination of PHP if/else statements or plugins, I have yet to find a decent solution for managing sidebars on a page-by-page basis. Since many of the sites I now work on require this functionality, I decided it was time to write a rather simple but effective plugin that handles this. Therefore, I'm introducing Unique Page Sidebars.

There are obviously some limitations to this plugin, and I don't expect me to use it on every site I develop, but I chose the most common use-case I have for needing dynamic sidebars, and developed this plugin to fit that need. That said, here is a list of features:

* Creating an unlimited number of sidebars, each with customizable names, descriptions and before/after title/widget tags (see options of `register_sidebar()` on the WordPress codex)
* Mapping any of the created sidebars to anywhere from 1 to unlimited number of pages
* Only one line of code needed in your theme
* Can easily default to a default sidebar

However, there are a few drawbacks (some of which I'm looking to working out in subsequent versions):

* Only one sidebar per-page
* No 'default' options for new sidebars

== Installation ==

To install, download the .zip, unpack it and upload to your WordPress installation in /wp-content/plugins/. Log in to the admin backend and activate it on the Plugins page. You should now see another menu item under "Appearance" called "Manage Sidebars" which will be where you can manage all of the sidebars.

In your theme, all you need to add is the following line where you would like the dynamic sidebar to show up:

`<?php dynamic_sidebar( apply_filters( 'ryno_sidebar', 'default-sidebar-id' ) ); ?>`

In the above snippet of code, 'default-sidebar-id' is simply the ID for your default sidebar which you should define in your functions.php file using `register_sidebar()`.

== Frequently Asked Questions ==

None yet.

== Screenshots ==

1. The main screen where you add/edit/delete the sidebars.
2. Quick screenshot showing the sidebar added to the Widgets screen.

== Upgrade Notice ==

No upgrade notices yet.

== Changelog ==

= 0.1 =
* Initial version