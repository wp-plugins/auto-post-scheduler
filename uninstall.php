<?php

// uninstall file for Auto Post Scheduler

if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) 
	exit();

        delete_option('aps_enabled');
        delete_option('aps_next');
        delete_option('aps_next_time');
        delete_option('aps_start_delay');
        delete_option('aps_delay_time');
        delete_option('aps_cats');
        delete_option('aps_drafts');
        delete_option('aps_random');
        delete_option('aps_recycle');
        delete_option('aps_batch');
        delete_option('aps_logfile');
        delete_option('aps_post_types');
?>
