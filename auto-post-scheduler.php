<?php
/**
 * Plugin Name: Auto Post Scheduler
 * Plugin URI: http://www.superblogme.com/auto-post-scheduler/
 * Description: Publishes drafts or recycles old posts based on a set schedule 
 * Version: 1.2
 * Released: August 19th, 2014
 * Author: Super Blog Me
 * Author URI: http://www.superblogme.com
 * License: GPL2
 **/

define('AUTOPOSTSCHEDULER_VERSION', '1.2');

defined('ABSPATH') or die ("Oops! This is a WordPress plugin and should not be called directly.\n");

register_activation_hook( __FILE__, 'aps_activation' );
register_deactivation_hook( __FILE__, 'aps_deactivation' );

add_action( 'admin_init', 'aps_admin_init' );
add_action('admin_menu', 'aps_add_options');
add_action('aps_auto_post_hook', 'aps_auto_post');

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_admin_init() {
	wp_register_style( 'apsStyleSheet', plugins_url('auto-post-scheduler.css', __FILE__) );
}

function aps_add_options() {
        if (function_exists('add_options_page')) {
                $page = add_options_page('Auto Post Scheduler Options', 'Auto Post Scheduler', 'manage_options', 'auto-post-scheduler', 'aps_options_page');
        }
	add_action( 'admin_print_styles-' . $page, 'aps_options_styles' );
}

function aps_options_styles() {
	wp_enqueue_style('apsStyleSheet');
}

function aps_activation() {
	add_option('aps_enabled', FALSE);
	add_option('aps_next', 24);
	add_option('aps_next_time', 'hours');
	add_option('aps_start_delay', 0);
	add_option('aps_delay_time', 'seconds');
	add_option('aps_cats', '');
	add_option('aps_drafts', TRUE);
	add_option('aps_random', FALSE);
	add_option('aps_recycle', FALSE);
	add_option('aps_batch', 1);
	add_option('aps_logfile', plugin_dir_path( __FILE__ ) . 'auto-post-scheduler.log');
	add_option('aps_post_types', 'post');
}

function aps_deactivation() {
        wp_clear_scheduled_hook('aps_auto_post_hook');
        update_option('aps_enabled', FALSE);
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_options_page() {

	if (!current_user_can('manage_options')) {
                ?><div id="message" class="error"><p><strong>
		You do not have permission to manage options.
            	</strong></p></div><?php
	}
        else if (isset($_POST['enable_auto_post_scheduler'])) {

                ?><div id="message" class="updated fade"><p><strong><?php

                update_option('aps_enabled', TRUE);

                wp_clear_scheduled_hook('aps_auto_post_hook');
    		$timesecs = aps_time_seconds(get_option('aps_start_delay'),get_option('aps_delay_time'));
		wp_schedule_event( time() + $timesecs, 'aps_schedule', 'aps_auto_post_hook' );

                echo "Auto Post Scheduler Enabled!";
		aps_write_log("Auto Post Scheduler Enabled.\n");

            ?></strong></p></div><?php

        } else if (isset($_POST['disable_auto_post_scheduler'])) {

                ?><div id="message" class="updated fade"><p><strong><?php

                update_option('aps_enabled', FALSE);

                wp_clear_scheduled_hook('aps_auto_post_hook');

                echo "Auto Post Scheduler Disabled!";
		aps_write_log("Auto Post Scheduler Disabled.\n");

            ?></strong></p></div><?php

        } else if (isset($_POST['update_options'])) {

                ?><div id="message" class="updated fade"><p><strong><?php

		$sn = (int)$_POST['aps_next'];
		if ($sn <= 0) $sn = 600;	// if improperly set put back to default 600
                update_option('aps_next', $sn);

                update_option('aps_next_time', $_POST['aps_next_time']);
                update_option('aps_start_delay', (int)$_POST['aps_start_delay']);
                update_option('aps_delay_time', $_POST['aps_delay_time']);
                update_option('aps_cats', stripslashes((string)$_POST['aps_cats']));
                update_option('aps_drafts', isset($_POST['aps_drafts']) ? TRUE : FALSE);
                update_option('aps_random', isset($_POST['aps_random']) ? TRUE : FALSE);
                update_option('aps_recycle', isset($_POST['aps_recycle']) ? TRUE : FALSE);
                update_option('aps_batch', (int)$_POST['aps_batch']);
                update_option('aps_logfile', stripslashes((string)$_POST['aps_logfile']));
                update_option('aps_post_types', stripslashes((string)$_POST['aps_post_types']));

		// new options so reset the callback
                wp_clear_scheduled_hook('aps_auto_post_hook');
                if (get_option('aps_enabled') == TRUE) {
    			$timesecs = aps_time_seconds(get_option('aps_start_delay'),get_option('aps_delay_time'));
			wp_schedule_event( time() + $timesecs, 'aps_schedule', 'aps_auto_post_hook' );
                	echo "Options Saved! Auto post checks restarted using new values.";
                	aps_write_log("Options Saved! Auto post checks restarted using new values.\n");
                }
		else
                	echo "Options Saved!";

            ?></strong></p></div><?php

        } else if (isset($_POST['clear_log'])) {
          	$logfile=get_option('aps_logfile');
          	$fh = @fopen($logfile, "w");
		if (!$fh)
			echo "<div id='message' class='error'><p>Unable to open Log File for writing! Check permissions.</p></div>";
		else {
          		@fwrite($fh, strftime("%D %T")." <strong><font color=\"#FF0000\">Auto Post Scheduler Log file cleared</font></strong>\n");
          		@fclose($fh);
          		echo "<div id='message' class='updated fade'><p><strong>Auto Post Scheduler Log file cleared!</strong></p></div>";
	  	}
        }

        ?>

        <div class='wrap aps'>

        <h2>Auto Post Scheduler v<?php echo AUTOPOSTSCHEDULER_VERSION; ?></h2>
	<a target='_blank' href="http://wordpress.org/support/plugin/auto-post-scheduler" class="ibutton btnblack">Support Forum</a>
	<a target='_blank' href="http://wordpress.org/support/view/plugin-reviews/auto-post-scheduler#postform" class="ibutton btnblack">Leave a review</a>
	<a target='_blank' href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W4W9RA2Q6TAGQ" class="ibutton btnblack">Donations</a>

        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

        <div style="padding: 0 0 20px 40px;">
        <h3>Current Status: <?php echo (get_option('aps_enabled') == TRUE) ? 'Enabled' : 'Disabled'; ?></h3>

        <?php if (get_option('aps_enabled') == TRUE) { 
	
	# lot of work just to get the time left in cron job:
	$schedule = wp_get_schedules();
	$crons = _get_cron_array();
        foreach ($crons as $timestamp => $cronhooks) :
        	foreach ($cronhooks as $hook => $cronjobs) :
        		foreach ($cronjobs as $cronjob) :
				if ($schedule[$cronjob['schedule']]['display'] == "Auto Post Scheduler Check") {
					echo "Next auto post check: " . date('Y-m-d h:i:s e', $timestamp) . "<p/>";
				}
			endforeach;
		endforeach;
	endforeach;
	} ?>

        <?php if (get_option('aps_enabled') == TRUE) { ?>
                <input type="submit" name="disable_auto_post_scheduler" value="<?php _e('Disable Auto Post Scheduler'); ?> &raquo;" />
        <?php } else { ?>
                <input type="submit" name="enable_auto_post_scheduler" value="<?php _e('Enable Auto Post Scheduler'); ?> &raquo;" />
        <?php } ?>

	<h4>
	This plugin will perform 'auto post checks', triggered at set time intervals to either publish drafts or recycle old posts as new.
	</h4>

        <fieldset class="options">
	<h3>Auto Post Scheduler Options</h3>
        </div>

        <table width="100%" border="0" cellspacing="0" cellpadding="6">

        <tr valign="top"><td width="25%" align="right">
                <strong>Auto Post Schedule</strong>
        </td><td align="left">
                <input name="aps_next" type="text" size="10" value="<?php echo htmlspecialchars(get_option('aps_next')); ?>"/>
		<?php 
			$seccheck=$hrscheck=$dayscheck="";
			if (get_option('aps_next_time') == 'days') $dayscheck = "checked";
			else if (get_option('aps_next_time') == 'hours') $hrscheck = "checked";
			else $seccheck = "checked";
		?>
		<input name="aps_next_time" type="radio" value="seconds" <?php echo $seccheck; ?>>seconds</input>
		<input name="aps_next_time" type="radio" value="hours" <?php echo $hrscheck; ?>>hours</input>
		<input name="aps_next_time" type="radio" value="days" <?php echo $dayscheck; ?>>days</input>
                <br />Time between each auto post check.
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Start Scheduling Delay</strong>
        </td><td align="left">
                <input name="aps_start_delay" type="text" size="10" value="<?php echo htmlspecialchars(get_option('aps_start_delay')); ?>"/>
		<?php 
			$seccheck=$hrscheck=$dayscheck="";
			if (get_option('aps_delay_time') == 'days') $dayscheck = "checked";
			else if (get_option('aps_delay_time') == 'hours') $hrscheck = "checked";
			else $seccheck = "checked";
		?>
		<input name="aps_delay_time" type="radio" value="seconds" <?php echo $seccheck; ?>>seconds</input>
		<input name="aps_delay_time" type="radio" value="hours" <?php echo $hrscheck; ?>>hours</input>
		<input name="aps_delay_time" type="radio" value="days" <?php echo $dayscheck; ?>>days</input>
                <br />Time delay before the first auto post check.
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Limit to Post Type(s)</strong>
        </td><td align="left">
                <input name="aps_post_types" type="text" size="10" value="<?php echo htmlspecialchars(get_option('aps_post_types')); ?>"/>
                <br />Separate post types with commas
                <br />If left blank, the plugin will only check for the default 'post' type
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Limit to Category ID(s)</strong>
        </td><td align="left">
                <input name="aps_cats" type="text" size="10" value="<?php echo htmlspecialchars(get_option('aps_cats')); ?>"/>
                <br />Separate category IDs with commas
                <br />If left blank, the plugin will check for posts from all categories
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Post Drafts?</strong>
        </td><td align="left">
                <input type="checkbox" name="aps_drafts" value="checkbox" <?php if (get_option('aps_drafts')) echo "checked='checked'"; ?>/> If checked, drafts will be checked for posting. <br />If not checked, all drafts will be skipped.
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Pick Random Draft(s)?</strong>
        </td><td align="left">
                <input type="checkbox" name="aps_random" value="checkbox" <?php if (get_option('aps_random')) echo "checked='checked'"; ?>/> If checked, random drafts will be chosen. <br />If not checked, the oldest drafts will be posted first.
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Recycle Posts?</strong>
        </td><td align="left">
                <input type="checkbox" name="aps_recycle" value="checkbox" <?php if (get_option('aps_recycle')) echo "checked='checked'"; ?>/> If checked, the oldest published posts will be re-published as a new post, <br />Will ONLY be used if 'Post Drafts?' is off OR there are no drafts left to publish.
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Number of Posts</strong>
        </td><td align="left">
                <input name="aps_batch" type="text" size="10" value="<?php echo htmlspecialchars(get_option('aps_batch')); ?>"/>
                <br />The number of drafts to publish OR published posts to recycle as new at each auto post check
        </td></tr>


        <tr valign="top"><td width="25%" align="right">
                <strong>Log File</strong>
        </td><td align="left">
                <input name="aps_logfile" type="text" size="100" value="<?php echo htmlspecialchars(get_option('aps_logfile')); ?>"/>
                <br />Make sure the log file is writable by WordPress.
        </td></tr>

        </table>
        </fieldset>

        <div class="submit" style="text-align:center;">
                <input type="submit" name="update_options" value="<?php _e('Update options'); ?> &raquo;" />
                <input type="submit" name="clear_log" value="<?php _e('Clear Log File'); ?>" onclick="return confirm(\'Are you sure you want to clear the log file?\');" />
        </div>

        </form>


        <p/>
        <h2>Auto Post Scheduler log</h2>
        <p><code>
		<?php aps_show_log(); ?>
        </code></p>
	</div>
<?php
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_time_seconds($num,$timeperiod) {
    if ($timeperiod == 'days')
    	return (int)($num * 3600 * 24);
    else if (get_option('aps_next_time') == 'hours')
    	return (int)($num * 3600);
    else
    	return (int)($num);
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_set_next_schedule($schedules) {	// add custom time when to check for next auto post
    if (get_option('aps_enabled') == FALSE) return $schedules;
    $timesecs = aps_time_seconds(get_option('aps_next'),get_option('aps_next_time'));
    $schedules['aps_schedule'] = array(
        'interval' => $timesecs, 'display' => 'Auto Post Scheduler Check'
    );
    return $schedules;
}
add_filter('cron_schedules', 'aps_set_next_schedule');

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_auto_post() {

	$aps_enabled = (bool)get_option('aps_enabled');
	if ($aps_enabled == FALSE) return;

	$aps_drafts = (bool)get_option('aps_drafts');
	$aps_cats = get_option('aps_cats');
	$aps_batch = get_option('aps_batch');
	$aps_random = (bool)get_option('aps_random');
	$aps_recycle = (bool)get_option('aps_recycle');
	$aps_post_types = get_option('aps_post_types');

	// set up the basic post query
	$post_types = explode(',', $aps_post_types);

	$args = array(
		'numberposts' => $aps_batch,
		'category' => $aps_cats,
		'post_type' => $post_types
	);

        if ($aps_drafts == TRUE)  {
		$args['post_status'] = "draft";
		$args['order'] = "ASC";
        	if ($aps_random == TRUE) $args['orderby'] = "rand";
		$results = get_posts($args);
	}

	// if check drafts off or if no drafts match query, check if we should recycle posts instead
	if (empty($results) && $aps_recycle == TRUE) {	
		$args['post_status'] = "publish";
		$args['orderby'] = "post_date";
		$args['order'] = "ASC";
		$results = get_posts($args);
	}

	if (!empty($results)) {
	// cycle through results and update
		foreach ($results as $thepost) {
			$update = array();
			$update['ID'] = $thepost->ID;
			$update['post_status'] = 'publish';
			$thetime = date("Y-m-d H:i:s");
			$update['post_date'] = $thetime;
			kses_remove_filters();
			wp_update_post($update);
			kses_init_filters();
			if ($args['post_status'] == "draft")
				aps_write_log($thepost->post_type . " DRAFT id " . $thepost->ID . " PUBLISHED: '" . $thepost->post_title . "'\n");
			else
				aps_write_log($thepost->post_type . " id " . $thepost->ID . " RECYCLED: '" . $thepost->post_title . "'\n");
		}
	}
	else {
		aps_write_log("Unable to find posts to publish.\n");
	}

}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_write_log($msg)
{
	$fh=@fopen(get_option('aps_logfile'),"a");
	if (!$fh) return;
	fwrite($fh, strftime("%D %T") . "\t - " . $msg);
	fclose($fh);
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_show_log() {
	$logfile = get_option('aps_logfile');
	$log = @file($logfile);
	if ($log === FALSE) {
        	echo "Error reading log file (".$logfile.") Check that the file exists and is writable by WordPress";
    	} else {
      		$msg = "";
      		foreach($log as $line) {
        		$msg.=trim($line)."<br />";
      	}
      	echo $msg;
	}
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

add_filter('plugin_action_links', 'aps_plugin_action_links', 10, 2);

function aps_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=auto-post-scheduler">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
