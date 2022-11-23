=== Authenticator ===
Contributors: inpsyde, Bueltge, nullbyte, dnaber-de
Tags: login, authentification, accessible, access, members
Requires at least: 5.0
Tested up to: 6.1
Stable tag: 1.3.1
requires PHP: 5.6
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin allows you to make your WordPress site accessible to logged in users only.

== Description ==
This plugin allows you to make your WordPress site accessible to logged in users only. In other words, to view your site they have to create or have an account on your site and be logged in. No configuration necessary, simply activating - that's all.

= Crafted by Inpsyde =
The team at [Inpsyde](https://inpsyde.com) is engineering the web and WordPress since 2006.

= Donation? =
You want to donate - we prefer a positive review, not more.

= Bugs, technical hints or contribute =
Please give me feedback, contribute and file technical bugs on [GitHub Repo](https://github.com/bueltge/Authenticator).


== Installation ==
= Requirements =
* WordPress version 1.5 and later.
* PHP 5.2 or later.
* Single or Multisite installation.

On PHP-CGI setups:
 - `mod_setenvif` or `mod_rewrite` (if you want to user HTTP authentication for feeds).

= Installation =
1. Unzip the downloaded package.
2. Upload folder include the file to the `/wp-content/plugins/` directory.
3. Activate the plugin through the `Plugins` menu in WordPress.

or use the installer via the back end of WordPress.

= On PHP-CGI setups =
If you want to use HTTP authentication for feeds (available since 1.1.0 as an *optional* feature) you have to update your `.htaccess` file. If [mod_setenvif](http://httpd.apache.org/docs/2.0/mod/mod_setenvif.html) is available, add the following line to your `.htaccess`:

	SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1

Otherwise you need [mod_rewrite](http://httpd.apache.org/docs/current/mod/mod_rewrite.html) to be enabled. In this case you have to add the following line to your `.htaccess`:

	RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

In a typical WordPress `.htaccess` it all looks like:

	<IfModule mod_rewrite.c>
		RewriteEngine On
		RewriteBase /
		RewriteRule ^index\.php$ - [L]
		RewriteCond %{REQUEST_FILENAME} !-f
		RewriteCond %{REQUEST_FILENAME} !-d
		RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
		RewriteRule . /index.php [L]
	</IfModule>

On a multisite installation:

	# BEGIN WordPress
	RewriteEngine On
	RewriteBase /
	RewriteRule ^index\.php$ - [L]

	# uploaded files
	RewriteRule ^files/(.+) wp-includes/ms-files.php?file=$1 [L]

	RewriteCond %{REQUEST_FILENAME} -f [OR]
	RewriteCond %{REQUEST_FILENAME} -d
	RewriteRule ^ - [L]

	RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
	RewriteRule . index.php [L]
	# END WordPress

= Settings =
You can change the settings of Authenticator in Settings → Reading. The settings refer to the behavior of your blog's feeds. They can be protected by HTTP authentication (not all feed readers support this) or by an authentication token which is added to your feed URL as a parameter. The third option is to keep everything in place. So feed URLs will be redirected to the login page if the user is not logged in (send no auth-cookie).

If you using token authentication, you can show the token to the blog users on their profile settings page by setting this option.

= HTTP Auth =
Users can gain access to the feed with their username and password.

= Token Auth =
The plugin will generate a token automatically when choosing this option. Copy this token and share it with the people who should have access to your feed. If your token is `ef05aa961a0c10dce006284213727730` the feed URLs look like so:

	# Main feed
	https://example.com/feed/?ef05aa961a0c10dce006284213727730

	# Main comment feed
	https://example.com/comments/feed/?ef05aa961a0c10dce006284213727730

	# Without permalinks
	https://example.com/?feed=rss2&ef05aa961a0c10dce006284213727730

= API =

**Filters**

* `authenticator_get_options` gives you access to the current authentication token:

	<?php
	$authenticator_options = apply_filters( 'authenticator_get_options', array() );

* `authenticator_bypass` gives you the possibility to completely bypass the authentication. No authentication will be required then.

	<?php
	add_filter( 'authenticator_bypass', '__return_true' );

* `authenticator_bypass_feed_auth` gives you the possibility to open the feeds for everyone. No authentication will be required then.

	<?php
	add_filter( 'authenticator_bypass_feed_auth', '__return_true' );

* `authenticator_exclude_pagenows` Pass an array of `$GLOBALS[ 'pagenow' ]` values to it, to exclude several WordPress pages from redirecting to the login page.

* `authenticator_exclude_ajax_actions` AJAX-Actions (independend of `_nopriv`) which should not be authenticated (remain open for everyone)

* `authenticator_exclude_posts` List of post-titles which should remain public, like the follow example source to public the 'Contact'-page.

		<?php
		add_action( 'plugins_loaded', function() {
			add_filter( 'authenticator_exclude_posts', function( $titles ) {
				$titles[] = 'Contact'; // here goes the post-title of the post/page you want to exclude
				return $titles;
			} );
		} );

== Screenshots ==
1. Authenticator's setting options at Settings → Reading.
2. Auth token for feeds is displayed on the user's profile settings page.

== Other Notes ==
= License =
Good news, this plugin is free for everyone! Since it's released under the GPL, you can use it free of charge on your personal or commercial blog. But if you enjoy this plugin, you can thank me and leave a [small donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6069955) for the time I've spent writing and supporting this plugin. And I really don't want to know how many hours of my life this plugin has already eaten ;)

= Translations =
The plugin comes with various translations, please refer to the [WordPress Codex](https://codex.wordpress.org/Installing_WordPress_in_Your_Language) for more information about activating the translation. If you want to help to translate the plugin to your language, please have a look at the translation possibility in [this page here](https://translate.wordpress.org/projects/wp-plugins/authenticator).

= Donation? =
You want to donate - we prefer a positive review, not more.

== Changelog ==

= 1.3.1 (2022-11-22) =
* Security Fix: Generate valid nonce only for privileged user to prevent privilege elevation.

= 1.3.0 (2017-11-30) =
* Fixed a topic on login of users if you exclude posts from the Authenticator.
* Add new filter hook to bypass the plugin `authenticator_bypass`, see the readme.
* Should now be ready for translations from the WordPress translation service.

= 1.2.3 (08/10/2017) =
* Fixed loop about settings that create a fatal error.
* Added authentication also for REST API; probs steffenster.

= 1.2.2 (08/10/2017) =
* Update readme to solve support questions, it works also under newer WP versions, tested up 4.9-alpha.

= 1.2.1 (08/31/2014) =
* Add guard for the constant `XMLRPC_REQUEST`.
* Fix for XML-RPC bug [#17](https://github.com/bueltge/Authenticator/issues/17).
* Enhance the readme to exclude posts/pages [#18](https://github.com/bueltge/Authenticator/issues/18).

= 1.2.0 (06/26/2014) =
* Fix the PHP notice [#15](https://github.com/bueltge/Authenticator/issues/15).
* Fix [#14](https://github.com/bueltge/Authenticator/issues/14).
* Add a removal of backlink in login footer [#8](https://github.com/bueltge/Authenticator/issues/8).
* Filter for Ajax actions [#12](https://github.com/bueltge/Authenticator/issues/12).
* Redefine `$reauth` for redirect [#11](https://github.com/bueltge/Authenticator/issues/11).
* Apply API Hook for exclude several URLs from redirect [#10](https://github.com/bueltge/Authenticator/issues/10).
* Add settings for XML-RPC [#9](https://github.com/bueltge/Authenticator/issues/9).
* Add Composer support.
* Update readme to see all information on wordpress.org repo.

= 1.1.0 (04/17/2014) =
* Add HTTP authentification for feeds.
* Add settings for reading the feed.
* Add token auth for feeds.

= 1.0.0 (01/20/2012) =
* Fix in multisite for redirect, also if the user does not have an account.
* Small rewrite for better codex.

= v0.4.1 (04/20/2011) =
* Remove network comment for using different blogs in Multisite.

= v0.4.0 (04/11/2011) =
* Bugfix for login without multisite.
* Ask for multisite.
* Fix for using plugin with WP earlier than 3.*.
* Also usable in mu-plugins folder.

=  v0.3.0 (04/06/2011) =
* Add check for rights to publish posts to use the plugin on Multisite Install; only users with this rights have access to the blog of Multisite install.
* Small changes of code.
