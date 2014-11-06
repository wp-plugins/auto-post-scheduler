<?php
/**
 * Plugin Name: Auto Post Scheduler
 * Plugin URI: http://www.superblogme.com/auto-post-scheduler/
 * Description: Publishes posts or recycles old posts at specified time intervals automatically.
 * Version: 1.4
 * Released: Nov 6th, 2014
 * Author: Super Blog Me
 * Author URI: http://www.superblogme.com
 * License: GPL2
 **/

define('AUTOPOSTSCHEDULER_VERSION', '1.4');

defined('ABSPATH') or die ("Oops! This is a WordPress plugin and should not be called directly.\n");

register_activation_hook( __FILE__, 'aps_activation' );
register_deactivation_hook( __FILE__, 'aps_deactivation' );

add_action('admin_init', 'aps_admin_init' );
add_action('admin_menu', 'aps_add_options');
add_action('aps_auto_post_hook', 'aps_auto_post');

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_admin_init() {
	wp_register_style( 'apsStyleSheet', plugins_url('auto-post-scheduler.css', __FILE__) );
}

function aps_add_options() {
        if (function_exists('add_options_page')) {
                $page = add_options_page('Auto Post Scheduler Options', 'Auto Post Scheduler', 'manage_options', 'auto-post-scheduler', 'aps_options_page');
		add_action( 'admin_print_styles-' . $page, 'aps_options_styles' );
        }
}

function aps_options_styles() {
	wp_enqueue_style('apsStyleSheet');
}

function aps_activation() {
	add_option('aps_enabled', FALSE);
	add_option('aps_next', '24');
	add_option('aps_next_time', 'hours');
	add_option('aps_start_delay', 0);
	add_option('aps_delay_time', 'seconds');
	add_option('aps_cats', '');
	add_option('aps_drafts', FALSE);
	add_option('aps_pending', TRUE);
	add_option('aps_publish', FALSE);
	add_option('aps_random', FALSE);
	add_option('aps_recycle', FALSE);
	add_option('aps_recycle_min', '7');
	add_option('aps_recycle_min_time', 'days');
	add_option('aps_batch', 1);
	add_option('aps_logfile', plugin_dir_path( __FILE__ ) . 'auto-post-scheduler.log');
	add_option('aps_post_types', 'post');
	add_option('aps_hours_mon', '');
	add_option('aps_hours_tue', '');
	add_option('aps_hours_wed', '');
	add_option('aps_hours_thu', '');
	add_option('aps_hours_fri', '');
	add_option('aps_hours_sat', '');
	add_option('aps_hours_sun', '');
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

		if ($_POST['aps_next'] != get_option('aps_next'))
			$aps_restart = 1;
		else
			$aps_restart = 0;

		$sn = $_POST['aps_next'];
		if ((int)$sn <= 0) $sn = 24;	// if improperly set put back to default 24
                update_option('aps_next', $sn);

                update_option('aps_next_time', $_POST['aps_next_time']);
                update_option('aps_start_delay', (int)$_POST['aps_start_delay']);
                update_option('aps_delay_time', $_POST['aps_delay_time']);
                update_option('aps_cats', stripslashes((string)$_POST['aps_cats']));
                update_option('aps_drafts', isset($_POST['aps_drafts']) ? TRUE : FALSE);
                update_option('aps_pending', isset($_POST['aps_pending']) ? TRUE : FALSE);
                update_option('aps_publish', isset($_POST['aps_publish']) ? TRUE : FALSE);
                update_option('aps_random', isset($_POST['aps_random']) ? TRUE : FALSE);
                update_option('aps_recycle', isset($_POST['aps_recycle']) ? TRUE : FALSE);
                update_option('aps_recycle_min', $_POST['aps_recycle_min']);
                update_option('aps_recycle_min_time', $_POST['aps_recycle_min_time']);
                update_option('aps_batch', (int)$_POST['aps_batch']);
                update_option('aps_logfile', stripslashes((string)$_POST['aps_logfile']));
                update_option('aps_post_types', stripslashes((string)$_POST['aps_post_types']));
                update_option('aps_hours_mon', stripslashes((string)$_POST['aps_hours_mon']));
                update_option('aps_hours_tue', stripslashes((string)$_POST['aps_hours_tue']));
                update_option('aps_hours_wed', stripslashes((string)$_POST['aps_hours_wed']));
                update_option('aps_hours_thu', stripslashes((string)$_POST['aps_hours_thu']));
                update_option('aps_hours_fri', stripslashes((string)$_POST['aps_hours_fri']));
                update_option('aps_hours_sat', stripslashes((string)$_POST['aps_hours_sat']));
                update_option('aps_hours_sun', stripslashes((string)$_POST['aps_hours_sun']));

                if (get_option('aps_enabled') == TRUE && $aps_restart) {
                	echo "Options Saved! New Auto Post Schedule time will be used after next auto post check.";
                	aps_write_log("Options Saved! New Auto Post Schedule time will be used after next auto post check.\n");
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
          		@fwrite($fh, date('Y-m-d H:i:s',current_time("timestamp"))." <strong><font color=\"#FF0000\">Auto Post Scheduler Log file cleared</font></strong>\n");
          		@fclose($fh);
          		echo "<div id='message' class='updated fade'><p><strong>Auto Post Scheduler Log file cleared!</strong></p></div>";
	  	}
        }

        ?>

        <div class='wrap aps'>

        <h2>Auto Post Scheduler v<?php echo AUTOPOSTSCHEDULER_VERSION; ?></h2>
	&nbsp; &nbsp; &nbsp;
	<a target='_blank' href="http://wordpress.org/support/plugin/auto-post-scheduler" class="button-primary">Support Forum</a>
	<a target='_blank' href="http://wordpress.org/support/view/plugin-reviews/auto-post-scheduler#postform" class="button-primary">Leave a review</a>
	<a target='_blank' href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W4W9RA2Q6TAGQ" class="button-primary">Instant Karma $1</a>

        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

        <h3>Current Status: <?php echo (get_option('aps_enabled') == TRUE) ? 'Enabled' : 'Disabled'; ?></h3>

        <?php if (get_option('aps_enabled') == TRUE) { 
	
                echo "<div class='aps-schedule'>Current server time:</div>";
                echo date('Y-m-d H:i:s',current_time("timestamp")) . " " . get_option('timezone_string');
                $scheduledtime = wp_next_scheduled('aps_auto_post_hook');
		if ($scheduledtime) {
                	$formatscheduledtime = date("Y-m-d H:i:s", $scheduledtime + (get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ));
                	echo "<br /><div class='aps-schedule'>Next auto post check:</div>";
                	echo $formatscheduledtime . " " . get_option('timezone_string');
		}
		else {
			echo "<br />Error: aps_auto_hook not scheduled, likely another plugin misuse of cron_schedules. See FAQ.";
		}
                echo "<br/>";
        ?>
                <input type="submit" name="disable_auto_post_scheduler" value="<?php _e('Disable Auto Post Scheduler'); ?> &raquo;" />
        <?php } else { ?>
                <input type="submit" name="enable_auto_post_scheduler" value="<?php _e('Enable Auto Post Scheduler'); ?> &raquo;" />
        <?php } ?>

	<h4>
	This plugin will schedule 'auto post checks' to publish new posts and/or recycle old posts automatically.
	</h4>

        <fieldset class="options">
	<h3>Auto Post Scheduler Options</h3>

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
                <br />Time or time range between each auto post check. 
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
                <input name="aps_post_types" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_post_types')); ?>"/>
                <br />Separate post types with commas
                <br />If left blank, the plugin will only check for the default 'post' type
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Limit to Category ID(s)</strong>
        </td><td align="left">
                <input name="aps_cats" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_cats')); ?>"/>
                <br />Separate category IDs with commas
                <br />If left blank, the plugin will check for posts from all categories
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Limit certain Day(s)</strong>
        </td><td align="left">
		<div class='aps-limitday'><strong><em>Mondays</em></strong></div>Time range(s)
                <input name="aps_hours_mon" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_mon')); ?>"/> in 24-hour format
		<br />
		<div class='aps-limitday'><strong><em>Tuesdays</em></strong></div>Time range(s)
                <input name="aps_hours_tue" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_tue')); ?>"/> in 24-hour format
		<br />
		<div class='aps-limitday'><strong><em>Wednesdays</em></strong></div>Time range(s)
                <input name="aps_hours_wed" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_wed')); ?>"/> in 24-hour format
		<br />
		<div class='aps-limitday'><strong><em>Thursdays</em></strong></div>Time range(s)
                <input name="aps_hours_thu" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_thu')); ?>"/> in 24-hour format
		<br />
		<div class='aps-limitday'><strong><em>Fridays</em></strong></div>Time range(s)
                <input name="aps_hours_fri" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_fri')); ?>"/> in 24-hour format
		<br />
		<div class='aps-limitday'><strong><em>Saturdays</em></strong></div>Time range(s)
                <input name="aps_hours_sat" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_sat')); ?>"/> in 24-hour format
		<br />
		<div class='aps-limitday'><strong><em>Sundays</em></strong></div>Time range(s)
                <input name="aps_hours_sun" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_sun')); ?>"/> in 24-hour format
                <br />Separate hours with dashes and commas. Example: 0400-1230, 1500-2100
                <br />If left blank, all times are considered
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Eligible Post Statuses?</strong>
        </td><td align="left">
                <input type="checkbox" name="aps_drafts" value="checkbox" <?php if (get_option('aps_drafts')) echo "checked='checked'"; ?>/> Drafts. If checked, drafts will be checked for posting.<br /> 
                <input type="checkbox" name="aps_pending" value="checkbox" <?php if (get_option('aps_pending')) echo "checked='checked'"; ?>/> Pending. If checked, pending posts will be checked for posting.<br /> 
                <input type="checkbox" name="aps_publish" value="checkbox" <?php if (get_option('aps_publish')) echo "checked='checked'"; ?>/> Publish. If checked, published posts will be checked for posting.<br /> 
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Pick Random Post(s)?</strong>
        </td><td align="left">
                <input type="checkbox" name="aps_random" value="checkbox" <?php if (get_option('aps_random')) echo "checked='checked'"; ?>/> If checked, random eligible posts will be chosen. <br />If not checked, the oldest eligible posts will be posted first.
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Recycle Published Posts?</strong>
        </td><td align="left">
                <input type="checkbox" name="aps_recycle" value="checkbox" <?php if (get_option('aps_recycle')) echo "checked='checked'"; ?>/> If checked, the oldest published posts will be re-published as a new post, <br />Will ONLY be used if no eligible posts are checked OR there are no eligible posts left to publish.
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Minimum Recycle Age</strong>
        </td><td align="left">
                <input name="aps_recycle_min" type="text" size="10" value="<?php echo htmlspecialchars(get_option('aps_recycle_min')); ?>"/>
		<?php 
			$seccheck=$hrscheck=$dayscheck="";
			if (get_option('aps_recycle_min_time') == 'days') $dayscheck = "checked";
			else if (get_option('aps_recycle_min_time') == 'hours') $hrscheck = "checked";
			else $seccheck = "checked";
		?>
		<input name="aps_recycle_min_time" type="radio" value="seconds" <?php echo $seccheck; ?>>seconds</input>
		<input name="aps_recycle_min_time" type="radio" value="hours" <?php echo $hrscheck; ?>>hours</input>
		<input name="aps_recycle_min_time" type="radio" value="days" <?php echo $dayscheck; ?>>days</input>
                <br />Posts must be older than this to be recycled.
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong>Number of Posts</strong>
        </td><td align="left">
                <input name="aps_batch" type="text" size="10" value="<?php echo htmlspecialchars(get_option('aps_batch')); ?>"/>
                <br />The number of eligible posts to publish OR number of published posts to recycle as new at each auto post check
        </td></tr>


        <tr valign="top"><td width="25%" align="right">
                <strong>Log File</strong>
        </td><td align="left">
                <input name="aps_logfile" type="text" size="100" value="<?php echo htmlspecialchars(get_option('aps_logfile')); ?>"/>
                <br />Make sure the log file is writable by WordPress.
        </td></tr>

        </table>
        </fieldset>

        <div class="submit" class="aps-center">
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
		$timeval = 3600 * 24;
	else if ($timeperiod == 'hours')
		$timeval = 3600;
	else
		$timeval = 1;
	// is this a range? i.e 2-6? pick a random time between them
	if ( preg_match( "/(\d+)\s*\D+\s*(\d+)/", $num, $matches ) ) {
		$random = mt_rand( $matches[1] * $timeval, $matches[2] * $timeval);
		return $random;
	}
	else
		return (int)($num * $timeval);
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

function aps_time_check() { // check if there are day/hour limits
	$today = strtolower(date("D",current_time("timestamp")));
	$aps_hours = get_option('aps_hours_' . $today);

	if (!empty($aps_hours)) {
		$time = date("Hi",current_time("timestamp"));
		$ranges = explode(",",$aps_hours);
		foreach($ranges as $range) {
			$hours = explode("-",$range);
			if (count($hours) != 2)
				aps_write_log("Error: " . $today . " time range of " . $range . " not recognized.\n");
			if ($hours[0] <= $time && $time <= $hours[1]) return 1;
		}
		return 0;
	}
return 1;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_auto_post() {

	$aps_enabled = (bool)get_option('aps_enabled');
	if ($aps_enabled == FALSE) return;

	$aps_drafts = (bool)get_option('aps_drafts');
	$aps_pending = (bool)get_option('aps_pending');
	$aps_publish = (bool)get_option('aps_publish');
	$aps_cats = get_option('aps_cats');
	$aps_batch = get_option('aps_batch');
	$aps_random = (bool)get_option('aps_random');
	$aps_recycle = (bool)get_option('aps_recycle');
	$aps_recycle_min = get_option('aps_recycle_min');
	$aps_recycle_min_time = get_option('aps_recycle_min_time');
	$aps_post_types = get_option('aps_post_types');

	if (!aps_time_check()) return;

	// set up the basic post query
	$post_types = explode(',', $aps_post_types);

	$args = array(
		#MARK 'numberposts' => $aps_batch,
		'posts_per_page' => $aps_batch,
		'category' => $aps_cats,
		'post_type' => $post_types
	);

	$post_statuses = array();
        if ($aps_drafts == TRUE)
		$post_statuses[] = 'draft';
        if ($aps_pending == TRUE)
		$post_statuses[] = 'pending';
        if ($aps_publish == TRUE)
		$post_statuses[] = 'publish';

	$results = null;
        if (!empty($post_statuses))  {
		$args['post_status'] = $post_statuses;
		$args['order'] = "ASC";
        	if ($aps_random == TRUE) $args['orderby'] = "rand";
		$results = new WP_Query($args);
	}

	// if no eligible post types checked or no results, check if we should recycle posts instead
	if (!$results->have_posts() && $aps_recycle == TRUE) {	
		if ($aps_recycle_min) {
			$before = $aps_recycle_min . ' ' . $aps_recycle_min_time . ' ago';
			$args['date_query'] = array(
					'before' => $before
			);
		}
		$args['post_status'] = "publish";
		$args['orderby'] = "post_date";
		$args['order'] = "ASC";
		$results = new WP_Query($args);
	}

	if ($results->have_posts()) {
	// cycle through results and update
		while ($results->have_posts()) {
			$results->the_post();
			$id = get_the_ID();
			$status = get_post_status($id);
			$title = get_the_title($id);
			if ($status == "publish")
				aps_write_log("POST id " . $id . " RECYCLED: '" . $title . "'\n");
			else
				aps_write_log($status . " POST id " . $id . " PUBLISHED: '" . $title . "'\n");
			$update = array();
			$update['ID'] = $id;
			$update['post_status'] = 'publish';
			$update['post_date_gmt'] = date('Y-m-d H:i:s',current_time("timestamp",1));
			$update['post_date'] = get_date_from_gmt($update['post_date_gmt']);
			kses_remove_filters();
			wp_update_post($update);
			kses_init_filters();
		}
	}
	else {
		aps_write_log("Unable to find eligible posts to publish.\n");
	}

}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_write_log($msg)
{
	$fh=@fopen(get_option('aps_logfile'),"a");
	if (!$fh) return;
	fwrite($fh, date('Y-m-d H:i:s',current_time("timestamp")) . "\t - " . $msg);
	fclose($fh);
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_show_log() {
	$logfile = get_option('aps_logfile');
	$msg = file_get_contents($logfile);
	if (!$msg)
        	echo "Error reading log file (".$logfile.") Check that the file exists and is writable by WordPress";
	else
		echo nl2br($msg);
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
