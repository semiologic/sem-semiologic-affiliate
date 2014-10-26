=== Semiologic Affiliate ===
Contributors: Denis-de-Bernardy, Mike_Koepke
Donate link: http://www.semiologic.com/partners/
Tags: affiliate, marketing, internet-marketing, semiologic
Requires at least: 2.8
Tested up to: 4.0
Stable tag: trunk

Automatically adds your affiliate ID to all links to Semiologic.


== Description ==

The Semiologic Affiliate plugin will add your [Semiologic affiliate ID](http://www.semiologic.com/partners/) to links on your site that point to [semiologic.com](http://www.semiologic.com) or [getsemiologic.com](http://www.getsemiologic.com).

Start by signing up with the [Semiologic Affiliate Program](http://www.semiologic.com/partners/), and creating an affiliate campaign.

Then, configure the plugin under Settings / Semiologic Affiliate, by entering your affiliate campaign.

From that point onwards, any link to semiologic.com or getsemiologic.com will now have that ID attached to it.

= Help Me! =

The [Semiologic forum](http://forum.semiologic.com) is the best place to report issues.

Alternatively, email sales at semiologic dot com.


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Change Log ==

= 2.5 =

- WP 4.0 compat

= 2.4.2 =

- Fix compatibility with Yoast WP SEO plugin when Force Title Rewrite option is on.

= 2.4.1 =

- Fix svn issue

= 2.4 =

- Use wp_print_footer_scripts hook instead of wp_footer as some themes fail to call wp_footer();
- Use own custom version of the anchor_utils class

= 2.3 =

- Code refactoring
- WP 3.9 compat

= 2.2.1 =

- WP 3.8 compat

= 2.2 =

- Fixed issue with parsing of links with non-standard (class, href, rel, target) attributes included in the <a> tag.  This caused twitter widgets to break
- Fixed issue with links contained onclick attributes with embedded javascript code.  WordPress' threaded comments does this
- WP 3.6 compat
- PHP 5.4 compat

= 2.1.1 =

- WP 3.5 compat

= 2.1 =

- WP 3.0 compat

= 2.0.1 =

- Force a higher pcre.backtrack_limit and pcre.recursion_limit to avoid blank screens on large posts

= 2.0 =

- New, better regular expression for link processing
- Enhance escape/unescape methods
- Localization
- Code enhancements and optimizations