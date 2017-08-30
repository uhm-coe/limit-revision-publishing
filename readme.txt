=== Limit Revision Publishing ===
Contributors: figureone
Tags: revision, capability, publish, limit, workflow, permissions
Requires at least: 3.9
Tested up to: 4.8
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Limit Revision Publishing restricts edits made by users without the publish_{post_type} capability.

== Description ==

Limit Revision Publishing restricts edits made by users without the publish_{post_type} capability. Their edits will be queued, and the original post will remain published. The All Posts view in the WordPress Dashboard will contain a column indicating posts with unpublished revisions. Notification emails can be sent to a subset of users whenever a new revision has been submitted so users with elevated privileges can publish them.

== Installation ==

1. Upload "limit-revision-publishing" to the "/wp-content/plugins/" directory.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. Set the requisite permissions and roles via the Settings -> Limit Revision Publishing options page.

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

= 1.1.7 =
* Fix for autosave content getting published if the autosave is the revision immediately prior to the current revision in the revision history.

= 1.1.6 =
* Fix encoded ampersands in notification emails.

= 1.1.5 =
* Fix for pending revisions on ACF fields being shown.

= 1.1.4 =
* Fix for notification emails not being sent when creating a new post as Pending Review.
* Fix for invalid parameters to get_user_by() causing certain notification emails not to send. Props @joelstransky!

= 1.1.3 =
* Fix for PHP error on ACF Field Groups screen.

= 1.1.2 =
* Add filter for Pending Revision to All Posts/Pages screen.
* Fix for sorting by Pending Revision in All Posts/Pages screen.
* Fix for Pages missing from permissions chart in plugin settings.

= 1.1.1 =
* Add [edit_url] shortcode in notification emails.
* Fix for all users being emailed if 'Roles to notify' setting was empty.
* Fix for Role Settings missing custom post types with show_in_nav_menus=false (i.e., types meant to not show up under Appearance>Menus).
* Fix: Remove redundant labels in Role Settings.

= 1.1.0 =
* Add ability to restrict any role's publishing capability.
* Add descriptive table of roles to plugin options.
* Add customizable notification email to plugin options.
* Fix ACF revision integration (ACF Pro >= 5.4.0).

= 1.0.0 =
* First official release.

= 0.1.0 =
* Development version.

== Upgrade Notice ==

= 1.0.0 =
Upgrade to v1.0 to get the first stable version.
