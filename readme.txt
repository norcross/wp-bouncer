=== WP Bouncer ===
Contributors: strangerstudios, norcross
Website Link: http://www.paidmembershipspro.com/add-ons/plugins-on-github/wp-bouncer/
Tags: login, security, member, members, membership, memberships, susbcription, subscriptions
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Only allow one device to be logged into WordPress for each user.

== Description ==

WP Bouncer will make sure users are logged in from only one device at a time. This should deter people from sharing their login credentials for your site, which is especially good for paid membership sites.

WP Bouncer works by:
* Storing a random "FAKESESSID" for each user when they log in.
* If a user is logged in, on each page load (init hook), WP Bouncer checks if the FAKESESSID stored in the user's cookies is the same as the last login stored in a transient (fakesessid_user_login).
* If not, WP Bouncer logs the user out and redirects them to a warning message.

For Example:
* User A logs in as "user". Their FAKESESSID, say "SESSION_A" is stored in a WordPress option.
* User B logs in as "user". Their FAKESESSID, say "SESSION_B" is overwrites the stored WordPress option.
* User A tries to load a page on your site, WP Bouncer catches them and logs them out, redirecting them to the warning message.
* User B can browse around the site as normal... unless...
* User A logs in again as "user". Their FAKESESSID, SESSION_A_v2 is stored in the WordPress option.
* Now user B would be logged out if they load another page.

Improvements:
* Settings page to choose where users are taken after being bounced.
* Keep track of how many bounces there are and lock the account down if there are so many in a small time frame.

= Support the Plugin Authors =
If you like this, checkout Jason's work with Paid Memberships Pro (http://www.paidmembershipspro.com) and Andrew's work at Reaktiv Studios (http://reaktivstudios.com/).

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `wp-bouncer` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. That's it! There are no settings for this plugin.

== Frequently Asked Questions ==

None yet.

== Changelog ==
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
