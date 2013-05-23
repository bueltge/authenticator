# Authenticator
This plugin allows you to make your WordPress site accessible to logged in users only.

## Description
This plugin allows you to make your WordPress site accessible to logged in users only. In other words to view your site they have to create / have an account in your site and be logged in. No configuration necessary, simply activating - thats all.

### Requirements
* WordPress version 1.5 and later
* PHP 5.2*

On PHP-CGI setups:

* `mod_setenvif` or `mod_rewrite` (if you want to user HTTP-Authentication for feeds)


## Installation
1. Unpack the download-package
2. Upload folder include the file to the `/wp-content/plugins/` directory.
3. Activate the plugin through the `Plugins` menu in WordPress

or use the installer via backend of WordPress

### On PHP-CGI setups
If you want to use HTTP-Authentication for feeds (available since 1.1.0 as a *optional* feature) you have to update your `.htaccess` file. If [mod_setenvif](http://httpd.apache.org/docs/2.0/mod/mod_setenvif.html) is available, add the following line to your `.htaccess`:

```
SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1
```

Otherwise you need [mod_rewrite](http://httpd.apache.org/docs/current/mod/mod_rewrite.html) to be enabled. In this case you have to add the following line to your `.htaccess`:

```
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

In a typical Wordpress .htaccess it all looks like:

```
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteRule . /index.php [L]
</IfModule>
```

respectively in a multisite installation:

```
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
```
## Settings
You can change the settings of Authenticator on Options → Reading. The settings refer to the behaviour of your blog's feeds. Should they be protected by HTTP-Authentication (not all Feed-Readers support this) or by an authentication token, which is simply add to your feed URL as Parameter. The third option is to keep everything in place. So Feed-URLs will be redirected to the login page if the user is not logged in (send no auth-cookie). 

If you using token authentication, you can show the token to the blog users on their profile settings page by setting these option.

### HTTP Auth
Users can gain access to the feed with their Username/Password. 

### Token Auth
The plugin will generate a token automaticaly, when choosing this option. Copy this token and share it with the people who should have access to your feed. If your token is ```ef05aa961a0c10dce006284213727730``` the feed-URLs looks like so:
```php
# main feed
http://yourblog.com/feed/?ef05aa961a0c10dce006284213727730

#main comment feed 
http://yourblog.com/comments/feed/?ef05aa961a0c10dce006284213727730

#without permalinks
http://yourblog.com/?feed=rss2&ef05aa961a0c10dce006284213727730
```

## Screenshots
1. Authenticator's setting options at Settings → Reading.
2. Auth-Token for feeds is displayed on the users profile settings page.

## API
### Filters
* ```authenticator_get_options``` Whith this filter you have access to the current authentication-token: 
```php
<?php
$authenticator_options = apply_filters( 'authenticator_get_options', array() );
```
* ```authenticator_bypass_feed_auth``` gives you the posibillity to open the feeds for everyone. No authentication will be required then.
```php
<?php
add_filter( 'authenticator_bypass_feed_auth', '__return_true' );
```

## Other Notes
### Licence
Good news, this plugin is free for everyone! Since it's released under the GPL, you can use it free of charge on your personal or commercial blog. But if you enjoy this plugin, you can thank me and leave a [small donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6069955) for the time I've spent writing and supporting this plugin. And I really don't want to know how many hours of my life this plugin has already eaten ;)

### Translations
The plugin comes with various translations, please refer to the [WordPress Codex](http://codex.wordpress.org/Installing_WordPress_in_Your_Language "Installing WordPress in Your Language") for more information about activating the translation. If you want to help to translate the plugin to your language, please have a look at the .pot file which contains all defintions and may be used with a [gettext](http://www.gnu.org/software/gettext/) editor like [Poedit](http://www.poedit.net/) (Windows) or plugin for WordPress [Localization](http://wordpress.org/extend/plugins/codestyling-localization/).


## Changelog
see [Short Changelog on WP Repo](http://wordpress.org/plugins/authenticator/changelog/) or see [commits](https://github.com/bueltge/Authenticator/commits/stable)
