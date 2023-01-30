=== WP Bouncer ===
Contributors: strangerstudios, norcross
Website Link: https://www.paidmembershipspro.com/add-ons/wp-bouncer/
Tags: login, security, membership, firewall, protection
Requires at least: 5.2
Tested up to: 6.1.1
Stable tag: 1.5.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Deter members from sharing login credentials: restrict simultaneous logins for the same user.

== Description ==

WP Bouncer restricts the number of simultaneous logins for the same WordPress user account. The plugin's goal is to deter people from sharing their login credentials for your site, which is especially important for a paid membership, premium content, or eLearning site.

***How WP Bouncer Protects Shared User Logins

* The plugin stores a random `FAKESESSID` for each user when they log in.
* If a user is logged in, on each page load (init hook), WP Bouncer checks if the `FAKESESSID` stored in the user’s cookies is the same as the last login stored in a transient (`fakesessid_user_login`).
* If the two values do no match, WP Bouncer logs the user out and redirects them to the WordPress login page or a custom page using the wp_bouncer_redirect_url filter.

If the WP_BOUNCER_HEARTBEAT_CHECK is defined to true, JavaScript will be loaded to bounce users when a new user logs in with the same login. This is useful for sites with page caching.

Admininstrator accounts or any users with the "manage_options" capability are excluded from bounces.

***Or, Allow a Specific Number of Active Sessions

By default, WP Bouncer only allows one session per user. 

You can use this plugin to offer bulk memberships to corporate, education, or other group-type customers via a shared login.

Use the `wp_bouncer_number_simultaneous_logins` filter to allow a defined number of active "sessions".

[View the Recipe](https://www.paidmembershipspro.com/limit-user-active-sessions/)

***Example Use Cases for WP Bouncer
* User A logs in as "user". Their `FAKESESSID`, say "SESSION_A" is stored in a WordPress option.
* User B logs in as "user". Their `FAKESESSID`, say "SESSION_B" is overwrites the stored WordPress option.
* User A tries to load a page on your site, WP Bouncer catches them and logs them out, redirecting them to the warning message.
* User B can browse around the site as normal... unless...
* User A logs in again as "user". Their `FAKESESSID`, "SESSION_A_v2" is stored in the WordPress option.
* Now user B would be logged out if they load another page.

***Hooks and Filters
* `wp_bouncer_ignore_admins` filter: if returning false even admins will be bounced.
* `wp_bouncer_redirect_url` filter: can be used to change the URL redirected to after being bounced.
* `wp_bouncer_number_simultaneous_logins` filter: can be set to limit logins to a number other than 1. 0 means unlimited logins.
* `wp_bouncer_login_flag`: runs right before bouncing (can be used to potentially stop the bouncing).
* `wp_bouncer_session_ids` hook: used to filter session ids when saving them. Passes $session_ids, $old_session_ids (before any were removed/bounced), and the current user's ID as parameters.
* `wp_bouncer_session_length` hook: used to filter how long the session ids transients are set. This way, you can time the transients to expire at a specific time of day. Note that the transient is saved on every page load, so if you set it to 5 minutes, it's going to push it out 5 minutes on every page load. You should try to set it to (the number of seconds until midnight) or something like that.

***Support the Plugin Authors
If you like this plugin, please check out Jason's work with [Stranger Studios](https://www.strangerstudios.com) and [Paid Memberships Pro](https://www.paidmembershipspro.com) and [Andrew's work at his personal site](http://andrewnorcross.com/).

== Installation ==

***Install WP Bouncer from within WordPress

1. Visit the plugins page within your dashboard and select "Add New"
1. Search for "WP Bouncer"
1. Locate this plugin and click "Install"
1. Activate "WP Bouncer" through the "Plugins" menu in WordPress

***Install WP Bouncer Manually

1. Upload the `wp-bouncer` folder to the `/wp-content/plugins/` directory
1. Activate "WP Bouncer" through the "Plugins" menu in WordPress

***Settings

There are no settings for this plugin. If you want to modify the default behavior to instead enable JavaScript checks, add the following code to your wp-config.php:

define( 'WP_BOUNCER_HEARTBEAT_CHECK', true );

== Frequently Asked Questions ==

= I need something strong to keep people from sharing accounts. =
We've found that using a 2-Factor-Authentication scheme on your site is a good way to keep people from sharing accounts. When we tried to design an advanced version of WP-Bouncer, it was basically 2FA. So try that.

== Changelog ==

= 1.5.1 - 2023-01-30 =
* ENHANCEMENT: Added filter `wp_bouncer_ajax_timeout` to adjust timeout (default 5000).
* ENHANCEMENT: Added support for translations.
* BUG FIX: Removed unused login warning file and screenshot from the SVN repository that is not used in this plugin.
* BUG FIX: Fixed misspelled constant for plugin version and usage in JS file load.

= 1.5 - 2021-06-02 =
* ENHANCEMENT: Removed the login-warning.php file. Instead, we redirect to the wp-login.php page and show a message.
* BUG FIX: Adjusted URLs to be https and adjusted meta tags to be be noindex/nofollow.

= 1.4.1 - 2020-01-01 =
* BUG FIX: Fixed issue where users were not redirected to the warning page when logged out.

= 1.4 - 2019-01-16 =
* BUG FIX: Fixed issue with how things were stored in transients. (Thanks, zackdn on GitHub)
* FEATURE: Added JavaScript to bounce users in case the PHP bouncer is not running (e.g. when using page caching). To enable this, add `define( 'WP_BOUNCER_HEARTBEAT_CHECK', true );` to your wp-config.php (without the backticks).

= 1.3.1 =
* Fixed a typo.
* Tested up to WP 4.8

= 1.3 =
* Added a user action link (hover over a user on the users.php page in the dashboard) to reset all sessions for a user.
* Added wp_bouncer_session_ids hook to filter session ids when saving them. Passes $session_ids, $old_session_ids (before any were removed/bounced), and the current user's ID as parameters.
* Added wp_bouncer_session_length hook to filter how long the session ids transients are set. This way, you can time the transients to expire at a specific time of day. Note that the transient is saved on every page load, so if you set it to 5 minutes, it's going to push it out 5 minutes on every page load. You should try to set it to (the number of seconds until midnight) or something like that.

= 1.2 =
* Fixed some typos in the variables used to generate the session ids.
* The fakesessid_{user_login} transients are now storing arrays of session ids. This allowed for multiple (but limited) sessions per user if wanted.
* Added wp_bouncer_ignore_admins filter, if returning false even admins will be bounced.
* Added wp_bouncer_redirect_url filter, which can be used to change the URL redirected to after being bounced.
* Added wp_bouncer_number_simultaneous_logins filter, which can be set to limit logins to a number other than 1. 0 means unlimited logins.
* Added wp_bouncer_login_flag in case you want to hook in and do something right before bouncing (or potentially stop the bouncing).

= 1.1 =
* Admin accounts (specifically users with "manage_options" capability) are excluded from bounces. This will eventually be a setting once we setup a settings page.
* Readme changes.

= 1.0.1 =
* Fixed bug with how transients were being set and get.
* Removed code in track_login that made sure you were logging in from login page. This will allow wp bouncer to kick in when logging in via wp_signon, etc.
* Moved redirect url to a class property. Will eventually add a settings page for this and any other setting/configuration value.

= 1.0 =
* First release!

== Upgrade Notice ==

= 1.1 =
* Admin accounts (specifically users with "manage_options" capability) are excluded from bounces. This will eventually be a setting once we setup a settings page.
