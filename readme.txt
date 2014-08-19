=== Auto Post Scheduler ===
Contributors: johnh10
Plugin Name: Auto Post Scheduler
Plugin URI: http://www.superblogme.com/auto-post-scheduler/
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W4W9RA2Q6TAGQ
Tags: schedule post, schedule, auto post, drafts, scheduling, posts, queue,
queue posts
Tested up to: 3.9.2
Requires at least:
Stable Tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin will perform 'auto post checks', triggered at set time intervals to either publish drafts or recycle old posts as new.


== Description ==

Auto Post Scheduler will schedule drafts to publish automatically and/or recycle old posts as new ones! No need to schedule draft times individually, and recycling old posts keeps your site looking fresh. 

Especially useful when importing a large number of posts, you can import them
as drafts and have the Auto Post Scheduler publish them at whatever schedule you choose.


== Installation ==

1. Install the plugin through WordPress admin or upload the `Auto Post
Scheduler` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit `Settings -> Auto Post Scheduler` to set options and Enable the scheduler.


== Frequently Asked Questions ==

= Why don't the auto post checks trigger? Nothing is happening. =

1. Auto Post Scheduler hooks into the WordPress cron for scheduling. These
cron events are only checked when a visitor loads any page on your WordPress
site. If there are no visitors, there can be no cron checks and therefore no
auto post checks.

2. If you are using .htaccess to allow,deny by IP, make sure to allow the IP
of your WordPress site itself as the wp-cron uses that IP address.


== Screenshots ==

1. The admin options.


== Changelog ==

= 1.2 =

* Minor cosmetic changes on options page.
* Added quick link buttons to support/review/donations on options page.
* Added security check for current_user_can() to manage options.


= 1.1 =

* Added support for custom post types.

* Bypass kses filter check during wp_update_post() to avoid WordPress
* automatically stripping embed codes such as Youtube iframes.

* Added 'Settings' shortcut link under Admin->Plugins->Auto Post Scheduler


= 1.0 =

* Initial Release


== Upgrade Notice ==

= none =


