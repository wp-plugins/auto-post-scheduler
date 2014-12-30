<?php
/**
 * Plugin Name: Auto Post Scheduler
 * Plugin URI: http://www.superblogme.com/auto-post-scheduler/
 * Description: Publishes posts or recycles old posts at specified time intervals automatically.
 * Version: 1.5
 * Released: TBD
 * Author: Super Blog Me
 * Author URI: http://www.superblogme.com
 * License: GPL2
 * Text Domain: auto-post-scheduler
 * Domain Path: /lang
 **/

define('AUTOPOSTSCHEDULER_VERSION', '1.5');

defined('ABSPATH') or die ("Oops! This is a WordPress plugin and should not be called directly.\n");

register_activation_hook( __FILE__, 'aps_activation' );
register_deactivation_hook( __FILE__, 'aps_deactivation' );

add_action('admin_init', 'aps_admin_init' );
add_action('admin_menu', 'aps_add_options');
add_action('plugins_loaded', 'aps_plugin_init');
add_action('aps_auto_post_hook', 'aps_auto_post');

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_admin_init() {
	wp_register_style( 'apsStyleSheet', plugins_url('auto-post-scheduler.css', __FILE__) );
}

function aps_add_options() {
        if (function_exists('add_options_page')) {
                $page = add_options_page(__('Auto Post Scheduler Options', 'auto-post-scheduler' ), __('Auto Post Scheduler', 'auto-post-scheduler' ), 'manage_options', 'auto-post-scheduler', 'aps_options_page');
		add_action( 'admin_print_styles-' . $page, 'aps_options_styles' );
        }
}

function aps_plugin_init() {
	$plugin_dir = basename(dirname(__FILE__)) . '/lang';
	load_plugin_textdomain( 'auto-post-scheduler', false, $plugin_dir );
}

function aps_options_styles() {
	wp_enqueue_style('apsStyleSheet');
}

function aps_activation() {
	add_option('aps_enabled', FALSE);
	add_option('aps_next', '24');
	add_option('aps_next_time', __('hours', 'auto-post-scheduler' ) );
	add_option('aps_start_delay', 0);
	add_option('aps_delay_time', __('seconds', 'auto-post-scheduler' ) );
	add_option('aps_cats', '');
	add_option('aps_drafts', FALSE);
	add_option('aps_pending', TRUE);
	add_option('aps_publish', FALSE);
	add_option('aps_random', FALSE);
	add_option('aps_recycle', FALSE);
	add_option('aps_recycle_min', '7');
	add_option('aps_recycle_min_time', __('days', 'auto-post-scheduler' ) );
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

function aps_schedule_event() {
	wp_clear_scheduled_hook('aps_auto_post_hook');
	$timesecs = aps_time_seconds(get_option('aps_start_delay'),get_option('aps_delay_time'));
	if ( wp_schedule_event( time() + $timesecs, 'aps_schedule', 'aps_auto_post_hook' ) !== FALSE )
		$str = __("Auto Post Scheduler Enabled!", 'auto-post-scheduler' );
	else
		$str = __("Error with wp_schedule_event for aps_auto_post_hook!", 'auto-post-scheduler' );
	return $str;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_options_page() {

	if (!current_user_can('manage_options')) {
                ?><div id="message" class="error"><p><strong>
		<?php _e('You do not have permission to manage options.', 'auto-post-scheduler' );?>
            	</strong></p></div><?php
	}
        else if (isset($_POST['enable_auto_post_scheduler'])) {

                ?><div id="message" class="updated fade"><p><strong><?php

                update_option('aps_enabled', TRUE);
		$str = aps_schedule_event();
                echo $str;
		aps_write_log( $str );

            ?></strong></p></div><?php

        } else if (isset($_POST['disable_auto_post_scheduler'])) {

                ?><div id="message" class="updated fade"><p><strong><?php

                update_option('aps_enabled', FALSE);
                wp_clear_scheduled_hook('aps_auto_post_hook');
                $str = __("Auto Post Scheduler Disabled!", 'auto-post-scheduler' );
                echo $str;
		aps_write_log( $str );
aps_write_log( print_r(wp_get_schedules(), TRUE) );

            ?></strong></p></div><?php

        } else if (isset($_POST['update_options'])) {

                ?><div id="message" class="updated fade"><p><strong><?php

		if ($_POST['aps_next'] != get_option('aps_next'))
			$aps_restart = 1;
		else if ($_POST['aps_next_time'] != get_option('aps_next_time'))
			$aps_restart = 1;
		else
			$aps_restart = 0;

		$sn = $_POST['aps_next'];
		if ((int)$sn <= 0) $sn = 24;	// if improperly set, put back to default 24
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
                	$str = __( "Options Saved! New Auto Post Schedule time will be used after next auto post check.", 'auto-post-scheduler' );
                	echo $str;
                	aps_write_log( $str );
                }
		else
                	_e( "Options Saved!", 'auto-post-scheduler' );

            ?></strong></p></div><?php

        } else if (isset($_POST['clear_log'])) {
          	$logfile=get_option('aps_logfile');
          	$fh = @fopen($logfile, "w");
		if (!$fh) {
			$str = __( "Unable to open Log File for writing! Check permissions.", 'auto-post-scheduler' );
			echo "<div id='message' class='error'><p>" . $str . "</p></div>";
		}
		else {
          		$str = __( "Auto Post Scheduler Log file cleared", 'auto-post-scheduler' );
          		@fwrite($fh, date('Y-m-d H:i:s',current_time("timestamp"))." <strong><font color='#FF0000'>" . $str . "</font></strong>\n");
          		@fclose($fh);
          		echo "<div id='message' class='updated fade'><p><strong>" . $str . "</strong></p></div>";
	  	}
        }

        ?>

        <div class='wrap aps'>

        <h2><?php _e( 'Auto Post Scheduler', 'auto-post-scheduler' );?> v<?php echo AUTOPOSTSCHEDULER_VERSION; ?></h2>
	&nbsp; &nbsp; &nbsp;
	<a target='_blank' href="http://wordpress.org/support/plugin/auto-post-scheduler" class="button-primary"><?php _e('Support Forum', 'auto-post-scheduler' ); ?></a>
	<a target='_blank' href="http://wordpress.org/support/view/plugin-reviews/auto-post-scheduler#postform" class="button-primary"><?php _e( 'Leave a review', 'auto-post-scheduler' ); ?></a>
	<a target='_blank' href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W4W9RA2Q6TAGQ" class="button-primary"><?php _e( 'Instant Karma $1', 'auto-post-scheduler' ); ?></a>

        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

        <h3><?php _e( 'Current Status:', 'auto-post-scheduler' );?> <?php echo (get_option('aps_enabled') == TRUE) ? __('Enabled', 'auto-post-scheduler' ) : __('Disabled', 'auto-post-scheduler' ) ; ?></h3>

        <?php if (get_option('aps_enabled') == TRUE) { 
	
                echo "<div class='aps-schedule'>" . __('Current server time:', 'auto-post-scheduler' ) . "</div>";
                echo date('Y-m-d H:i:s',current_time("timestamp")) . " " . get_option('timezone_string');
                $scheduledtime = wp_next_scheduled('aps_auto_post_hook');
		if ($scheduledtime) {
                	$formatscheduledtime = date("Y-m-d H:i:s", $scheduledtime + (get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ));
                	echo "<br /><div class='aps-schedule'>" . __('Next auto post check:', 'auto-post-scheduler' ) . "</div>";
                	echo $formatscheduledtime . " " . get_option('timezone_string');
		}
		else {
			echo "<br />" . __("Error: aps_auto_post_hook not scheduled, likely another plugin misuse of cron_schedules. See FAQ. (Trying to reset...)", 'auto-post-scheduler' );
			$str = aps_schedule_event();
			echo "<br />" . $str;
		}
                echo "<br/>";
        ?>
                <input type="submit" name="disable_auto_post_scheduler" value="<?php _e('Disable Auto Post Scheduler', 'auto-post-scheduler' ); ?> &raquo;" />
        <?php } else { ?>
                <input type="submit" name="enable_auto_post_scheduler" value="<?php _e('Enable Auto Post Scheduler', 'auto-post-scheduler' ); ?> &raquo;" />
        <?php } ?>

	<h4>
	<?php _e("This plugin will schedule 'auto post checks' to publish new posts and/or recycle old posts automatically.", 'auto-post-scheduler' ); ?>
	</h4>

        <fieldset class="options">
	<h3><?php _e('Auto Post Scheduler Options', 'auto-post-scheduler' );?></h3>

        <table width="100%" border="0" cellspacing="0" cellpadding="6">

        <tr valign="top"><td width="25%" align="right">
                <strong><?php _e('Auto Post Schedule', 'auto-post-scheduler' );?></strong>
        </td><td align="left">
                <input name="aps_next" type="text" size="10" value="<?php echo htmlspecialchars(get_option('aps_next')); ?>"/>
		<?php 
			$seccheck=$hrscheck=$dayscheck="";
			if (get_option('aps_next_time') == 'days') $dayscheck = "checked";
			else if (get_option('aps_next_time') == 'hours') $hrscheck = "checked";
			else $seccheck = "checked";
		?>
		<input name="aps_next_time" type="radio" value="seconds" <?php echo $seccheck; ?>><?php _e('seconds', 'auto-post-scheduler' );?></input>
		<input name="aps_next_time" type="radio" value="hours" <?php echo $hrscheck; ?>><?php _e('hours', 'auto-post-scheduler' );?></input>
		<input name="aps_next_time" type="radio" value="days" <?php echo $dayscheck; ?>><?php _e('days', 'auto-post-scheduler' );?></input>
                <br /><?php _e('Time or time range between each auto post check.', 'auto-post-scheduler' );?> 
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong><?php _e('Start Scheduling Delay', 'auto-post-scheduler' );?></strong>
        </td><td align="left">
                <input name="aps_start_delay" type="text" size="10" value="<?php echo htmlspecialchars(get_option('aps_start_delay')); ?>"/>
		<?php 
			$seccheck=$hrscheck=$dayscheck="";
			if (get_option('aps_delay_time') == 'days') $dayscheck = "checked";
			else if (get_option('aps_delay_time') == 'hours') $hrscheck = "checked";
			else $seccheck = "checked";
		?>
		<input name="aps_delay_time" type="radio" value="seconds" <?php echo $seccheck; ?>><?php _e('seconds', 'auto-post-scheduler' );?></input>
		<input name="aps_delay_time" type="radio" value="hours" <?php echo $hrscheck; ?>><?php _e('hours', 'auto-post-scheduler' );?></input>
		<input name="aps_delay_time" type="radio" value="days" <?php echo $dayscheck; ?>><?php _e('days', 'auto-post-scheduler' );?></input>
                <br /><?php _e('Time delay before the first auto post check.', 'auto-post-scheduler' );?>
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong><?php _e('Limit to Post Type(s)', 'auto-post-scheduler' );?></strong>
        </td><td align="left">
                <input name="aps_post_types" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_post_types')); ?>"/>
                <br /><?php _e('Separate post types with commas', 'auto-post-scheduler' );?>
                <br /><?php _e("If left blank, the plugin will only check for the default 'post' type", 'auto-post-scheduler' );?>
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong><?php _e('Limit to Category ID(s)', 'auto-post-scheduler' );?></strong>
        </td><td align="left">
                <input name="aps_cats" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_cats')); ?>"/>
                <br /><?php _e('Separate category IDs with commas', 'auto-post-scheduler' );?>
                <br /><?php _e('If left blank, the plugin will check for posts from all categories', 'auto-post-scheduler' );?>
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong><?php _e('Limit certain Day(s)', 'auto-post-scheduler' );?></strong>
        </td><td align="left">
		<div class='aps-limitday'><strong><em><?php _e('Mondays', 'auto-post-scheduler' );?></em></strong></div><?php _e('Time range(s)', 'auto-post-scheduler' );?>
                <input name="aps_hours_mon" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_mon')); ?>"/> <?php _e('in 24-hour format', 'auto-post-scheduler' );?>
		<br />
		<div class='aps-limitday'><strong><em><?php _e('Tuesdays', 'auto-post-scheduler' );?></em></strong></div><?php _e('Time range(s)', 'auto-post-scheduler' );?>
                <input name="aps_hours_tue" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_tue')); ?>"/> <?php _e('in 24-hour format', 'auto-post-scheduler' );?>
		<br />
		<div class='aps-limitday'><strong><em><?php _e('Wednesdays', 'auto-post-scheduler' );?></em></strong></div><?php _e('Time range(s)', 'auto-post-scheduler' );?>
                <input name="aps_hours_wed" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_wed')); ?>"/> <?php _e('in 24-hour format', 'auto-post-scheduler' );?>
		<br />
		<div class='aps-limitday'><strong><em><?php _e('Thursdays', 'auto-post-scheduler' );?></em></strong></div><?php _e('Time range(s)', 'auto-post-scheduler' );?>
                <input name="aps_hours_thu" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_thu')); ?>"/> <?php _e('in 24-hour format', 'auto-post-scheduler' );?>
		<br />
		<div class='aps-limitday'><strong><em><?php _e('Fridays', 'auto-post-scheduler' );?></em></strong></div><?php _e('Time range(s)', 'auto-post-scheduler' );?>
                <input name="aps_hours_fri" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_fri')); ?>"/> <?php _e('in 24-hour format', 'auto-post-scheduler' );?>
		<br />
		<div class='aps-limitday'><strong><em><?php _e('Saturdays', 'auto-post-scheduler' );?></em></strong></div><?php _e('Time range(s)', 'auto-post-scheduler' );?>
                <input name="aps_hours_sat" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_sat')); ?>"/> <?php _e('in 24-hour format', 'auto-post-scheduler' );?>
		<br />
		<div class='aps-limitday'><strong><em><?php _e('Sundays', 'auto-post-scheduler' );?></em></strong></div><?php _e('Time range(s)', 'auto-post-scheduler' );?>
                <input name="aps_hours_sun" type="text" size="20" value="<?php echo htmlspecialchars(get_option('aps_hours_sun')); ?>"/> <?php _e('in 24-hour format', 'auto-post-scheduler' );?>
                <br /><?php _e('Separate hours with dashes and commas. Example: 0400-1230, 1500-2100', 'auto-post-scheduler' );?>
                <br /><?php _e('If left blank, all times are considered', 'auto-post-scheduler' );?>
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong><?php _e('Eligible Post Statuses?', 'auto-post-scheduler' );?></strong>
        </td><td align="left">
                <input type="checkbox" name="aps_drafts" value="checkbox" <?php if (get_option('aps_drafts')) echo "checked='checked'"; ?>/> <?php _e('Drafts. If checked, drafts will be checked for posting.', 'auto-post-scheduler' );?><br /> 
                <input type="checkbox" name="aps_pending" value="checkbox" <?php if (get_option('aps_pending')) echo "checked='checked'"; ?>/> <?php _e('Pending. If checked, pending posts will be checked for posting.', 'auto-post-scheduler' );?><br /> 
                <input type="checkbox" name="aps_publish" value="checkbox" <?php if (get_option('aps_publish')) echo "checked='checked'"; ?>/> <?php _e('Publish. If checked, published posts will be checked for posting/recycling.', 'auto-post-scheduler' );?><br /> 
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong><?php _e('Pick Random Post(s)?', 'auto-post-scheduler' );?></strong>
        </td><td align="left">
                <input type="checkbox" name="aps_random" value="checkbox" <?php if (get_option('aps_random')) echo "checked='checked'"; ?>/> <?php _e('If checked, random eligible posts will be chosen.', 'auto-post-scheduler' );?> <br /><?php _e('If not checked, the oldest eligible posts will be posted first.', 'auto-post-scheduler' );?>
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong><?php _e('Recycle Published Posts?', 'auto-post-scheduler' );?></strong>
        </td><td align="left">
                <input type="checkbox" name="aps_recycle" value="checkbox" <?php if (get_option('aps_recycle')) echo "checked='checked'"; ?>/> <?php _e('If checked, the oldest published posts will be re-published as a new post.', 'auto-post-scheduler' );?> <br /><?php _e('Will ONLY be used if no eligible posts are checked OR there are no eligible draft/pending posts left to publish.', 'auto-post-scheduler' );?>
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong><?php _e('Minimum Recycle Age', 'auto-post-scheduler' );?></strong>
        </td><td align="left">
                <input name="aps_recycle_min" type="text" size="10" value="<?php echo htmlspecialchars(get_option('aps_recycle_min')); ?>"/>
		<?php 
			$seccheck=$hrscheck=$dayscheck="";
			if (get_option('aps_recycle_min_time') == 'days') $dayscheck = "checked";
			else if (get_option('aps_recycle_min_time') == 'hours') $hrscheck = "checked";
			else $seccheck = "checked";
		?>
		<input name="aps_recycle_min_time" type="radio" value="seconds" <?php echo $seccheck; ?>><?php _e('seconds', 'auto-post-scheduler' );?></input>
		<input name="aps_recycle_min_time" type="radio" value="hours" <?php echo $hrscheck; ?>><?php _e('hours', 'auto-post-scheduler' );?></input>
		<input name="aps_recycle_min_time" type="radio" value="days" <?php echo $dayscheck; ?>><?php _e('days', 'auto-post-scheduler' );?></input>
                <br /><?php _e('Posts must be older than this to be recycled.', 'auto-post-scheduler' );?>
        </td></tr>

        <tr valign="top"><td width="25%" align="right">
                <strong><?php _e('Number of Posts', 'auto-post-scheduler' );?></strong>
        </td><td align="left">
                <input name="aps_batch" type="text" size="10" value="<?php echo htmlspecialchars(get_option('aps_batch')); ?>"/>
                <br /><?php _e('The number of eligible posts to publish OR number of published posts to recycle as new at each auto post check', 'auto-post-scheduler' );?>
        </td></tr>


        <tr valign="top"><td width="25%" align="right">
                <strong><?php _e('Log File', 'auto-post-scheduler' );?></strong>
        </td><td align="left">
                <input name="aps_logfile" type="text" size="100" value="<?php echo htmlspecialchars(get_option('aps_logfile')); ?>"/>
                <br /><?php _e('Make sure the log file is writable by WordPress.', 'auto-post-scheduler' );?>
        </td></tr>

        </table>
        </fieldset>

        <div class="submit" class="aps-center">
                <input type="submit" name="update_options" value="<?php _e('Update options', 'auto-post-scheduler'); ?> &raquo;" />
                <input type="submit" name="clear_log" value="<?php _e('Clear Log File', 'auto-post-scheduler'); ?>" />
        </div>

        </form>


        <p/>
        <h2><?php _e('Auto Post Scheduler log', 'auto-post-scheduler' );?></h2>
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

function aps_heartbeat() { // absolutely ensure our aps event is still scheduled!
    if ( FALSE == get_option('aps_enabled') ) {	// APS is not enabled - do nothing
	return;
    }
    if ( wp_next_scheduled( 'aps_auto_post_hook' ) ) { // event hook exists - do nothing
	return;
    }
    // some other code/plugin has stomped our event hook! reset
    aps_write_log( "Notice! APS enabled but 'aps_auto_post_hook' myseriously removed. Resetting..." );
    aps_schedule_event();
}
add_action('wp_head', 'aps_heartbeat');

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_set_next_schedule($schedules) {	// add custom time when to check for next auto post
    if ( FALSE == get_option('aps_enabled') ) { // APS is not enabled - do nothing
	return $schedules;
    }

    $timesecs = aps_time_seconds(get_option('aps_next'),get_option('aps_next_time'));
    $schedules['aps_schedule'] = array(
        'interval' => $timesecs, 'display' => 'Auto Post Scheduler Check'
    );
    return $schedules;
}
add_filter('cron_schedules', 'aps_set_next_schedule', 99);

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
				aps_write_log( sprintf (__("Error: %s time range of %s not recognized.", 'auto-post-scheduler' ), $today, $range ) );
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
		'posts_per_page' => $aps_batch,
		'cat' => $aps_cats,
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
	}
	$args['order'] = "ASC";
       	if ($aps_random == TRUE) $args['orderby'] = "rand";
	$results = new WP_Query($args);

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
				$str = sprintf (__("POST id %d RECYCLED: '%s'", 'auto-post-scheduler' ), $id, $title );
			else
				$str = sprintf (__("%s POST id %d PUBLISHED : '%s'", 'auto-post-scheduler' ), $status, $id, $title );
			aps_write_log( $str );

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
		aps_write_log( __("Unable to find eligible posts to publish.", 'auto-post-scheduler' ) );
	}

}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_write_log($msg)
{
	$fh=@fopen(get_option('aps_logfile'),"a");
	if (!$fh) return;
	fwrite($fh, date('Y-m-d H:i:s',current_time("timestamp")) . "\t - " . $msg . "\n");
	fclose($fh);
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aps_show_log() {
	$logfile = get_option('aps_logfile');
	$msg = file_get_contents($logfile);
	if (!$msg)
        	printf( __("Error reading log file (%s) Check that the file exists and is writable by WordPress", 'auto-post-scheduler' ), $logfile );
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
