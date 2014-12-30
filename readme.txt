=== Auto Post Scheduler ===
Contributors: johnh10
Plugin Name: Auto Post Scheduler
Plugin URI: http://www.superblogme.com/auto-post-scheduler/
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W4W9RA2Q6TAGQ
Tags: schedule post, schedule, auto post, draft, pending, publish, scheduling, posts, queue, post scheduler, automate posts, queue posts, auto publish, post
Tested up to: 4.0
Stable Tag: 1.41
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin will schedule 'auto post checks' to publish new posts and/or recycle old posts automatically.


== Description ==

Use the Auto Post Scheduler to publish new posts and/or recycle old posts, automatically! No need to schedule post times individually, and recycling old posts keeps your site looking fresh. 

Especially useful when importing a large number of posts, you can 
have the Auto Post Scheduler publish them at whatever frequency you choose.


== Installation ==

1. Install the plugin through WordPress admin or upload the `Auto Post
Scheduler` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit `Settings -> Auto Post Scheduler` to set options and Enable the scheduler.


== Frequently Asked Questions ==

= Why don't the auto post checks trigger? Nothing is happening. =

1. Auto Post Scheduler hooks into the WordPress WP-Cron for scheduling. These
cron events are only checked when a visitor loads any WordPress page on the 
site. If there are no visitors, there can be no cron checks and therefore no
auto post checks.

2. If you are using .htaccess to allow,deny by IP, make sure to allow the IP
of your WordPress site itself as the wp-cron uses that IP address.

= Error: aps_auto_hook not scheduled, likely another plugin misuse of
cron_schedules =

This happens when another plugin incorrectly replaces cron_schedules with
their own schedules instead of correctly adding to existing schedules, so the
other plugin actually removes our aps_auto_hook. Other plugin code needs to be fixed.

= How do I turn off WP_Cron and use server cron jobs instead?

WordPress calls WP_Cron on every page load to check for cron_schedules.
If you have a high traffic site, you might want to use caching or set cron checks on a
schedule instead to save on resources. To do this:

1. Make sure Auto Post Scheduler is enabled.
2. Edit /wp-config.php and add the line
define('DISABLE_WP_CRON', true);
3. From the server command line, edit your cron with 'crontab -e' and add the line
*/5 * * * * wget -q -O -
"http://www.mydomain.com/wp-cron.php?doing_wp_cron=`date +\%s`" >
/dev/null2>&1

and change 'mydomain' to your site domain.

This crontab entry will call wp_cron every 5 minutes.

= When using WP Super Cache the Home/Front page isn't updated when APS
publishes a post or recycles an old post. Why is that?

As far as I know, WP Super Cache must not hook into when a post status has
changed. User MassimoD reports "Quick Cache does, W3Total Cache does, Hyper
Cache does, Gator Cache does. Only WP Super Cache doesn't."



== Screenshots ==

1. The admin options.


== Changelog ==

= 1.42 = 

* Added i18n support.
* Changed priority of 'cron_schedules' add_filter call to 99.
* If no 'Eligible Post Statuses?' selected, default is 'publish'.

= 1.41 = 

* Bugfix: Limit by category: WP_Query function uses 'cat' arg instead of 'category'

= 1.4 = 

* Added feature: Option to also handle posts in Pending and Publish status.
* Added feature: Support for a time range in Auto Post Schedule field. i.e. 2-6 hours.
* Added feature: Detect if another plugin removed cron_schedules.
* Updated feature: No longer restarts if settings are updated when Scheduler is Enabled.
* Updated feature: Expanded 'Limit Certain Day(s)' to allow different limits for each day.
* Updated feature: Time display on settings page and log file to use same 24h format.
* Updated feature: Log messages detail post type.
* Updated feature: Added Minimum Recycle Age for recycling posts.
* Internal change: Use WP_Query instead of get_posts for more arg options.
* Internal change: Moved inline css to css file.
* Internal change: Use primary buttons instead of custom.
* Internal change: Easier log file display code.
* Internal change: Easier way to retrieve 'Next auto post check' time.

= 1.3 =

* Will now set post time based on the timezone in Settings->General. Default UTC.
* Added ability to limit post checks to certain days of the week and time ranges.


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


