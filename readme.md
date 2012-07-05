# Authenticator
Contributors: inpsyde, Bueltge, nullbyte
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6069955
Tags: login, authentification, accessible, access, members
Requires at least: 1.5
Tested up to: 3.5
Stable tag: 1.0.0

This plugin allows you to make your WordPress site accessible to logged in users only.

## Description
This plugin allows you to make your WordPress site accessible to logged in users only. In other words to view your site they have to create / have an account in your site and be logged in. No configuration necessary, simply activating - thats all.

### Requirements
* WordPress version 1.5 and later; current (01/2012) tested with 3.3* and 3.4-alpha
* PHP 5.2*


## Installation
1. Unpack the download-package
2. Upload folder include the file to the `/wp-content/plugins/` directory.
3. Activate the plugin through the `Plugins` menu in WordPress

or use the installer via backend of WordPress

```
RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]
```

## Other Notes
### Licence
Good news, this plugin is free for everyone! Since it's released under the GPL, you can use it free of charge on your personal or commercial blog. But if you enjoy this plugin, you can thank me and leave a [small donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6069955) for the time I've spent writing and supporting this plugin. And I really don't want to know how many hours of my life this plugin has already eaten ;)

### Translations
The plugin comes with various translations, please refer to the [WordPress Codex](http://codex.wordpress.org/Installing_WordPress_in_Your_Language "Installing WordPress in Your Language") for more information about activating the translation. If you want to help to translate the plugin to your language, please have a look at the .pot file which contains all defintions and may be used with a [gettext](http://www.gnu.org/software/gettext/) editor like [Poedit](http://www.poedit.net/) (Windows) or plugin for WordPress [Localization](http://wordpress.org/extend/plugins/codestyling-localization/).


## Changelog
### 1.1.0
* add http authentification for feeds
* add settings for reading feed

### 1.0.0 (01/20/2012)
* fix in MU for redirect, also if the user have not an account
* small rewrite for better codex

### v0.4.1 (04/20/2011)
* Remove network comment for use different in blogs of WPMultisite

### v0.4.0 (04/11/2011)
* Bugfix for login without multisite
* ask for multisite
* Fix for use plugin WP smaller 3.*
* Also usable in mu-plugins folder

###  v0.3.0 (04/06/2011)
* Add check for rights to publish posts to use the plugin on Multisite Install; only users with this rights have acces to the blog of Mutlisite install
* Small changes on code
