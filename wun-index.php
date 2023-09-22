<?php

/*
 * Plugin Name: wpDiscuz - User Notifications
 * Description: Generates and displays user notifications with a notification bell in the website menu as well as generates web push notifications.
 * Version: 1.0.10
 * Author: gVectors Team
 * Author URI: https://gvectors.com/
 * Plugin URI: https://wpdiscuz.com/
 * Text Domain: wpdiscuz-user-notifications
 */
if (!defined("ABSPATH")) {
    exit();
}

define("WPDUN_DIR_PATH", __DIR__);
define("WPDUN_DIR_NAME", basename(__DIR__));
define("WPDUN_INDEX", __FILE__);
define("WPDUN_ACTION_DELETE_NOTIFICATIONS", "wun_action_delete_notifications");

/**
 * Load constants and database manager
 */
include_once WPDUN_DIR_PATH . "/includes/wunConstants.php";
include_once WPDUN_DIR_PATH . "/includes/wunDBManager.php";
include_once WPDUN_DIR_PATH . "/options/wunOptions.php";

/**
 * Initialize a new database manager object and try to create DB tables if does not exist
 */
$wunDBManager = new WunDBManager();
/**
 * Initialize a new options object for deleting expired notification via cron job
 */
$wunOptions = new WunOptions($wunDBManager);

/**
 * Create tables on activation
 */
register_activation_hook(WPDUN_INDEX, [$wunDBManager, "createTables"]);
add_action("wp_insert_site", [&$wunDBManager, "addNewBlog"]);
add_action("delete_blog", [&$wunDBManager, "deleteBlog"]);

/* CRON JOBS */
register_activation_hook(WPDUN_INDEX, "wunRegisterJobs");
register_deactivation_hook(WPDUN_INDEX, "wunDeregisterJobs");
add_action(WPDUN_ACTION_DELETE_NOTIFICATIONS, "wunCronDeleteNotifications");
/* /CRON JOBS */


function wunRegisterJobs() {
    if (!wp_next_scheduled(WPDUN_ACTION_DELETE_NOTIFICATIONS)) {
        wp_schedule_event(current_time("timestamp"), "twicedaily", WPDUN_ACTION_DELETE_NOTIFICATIONS);
    }
}

function wunDeregisterJobs() {
    if (wp_next_scheduled(WPDUN_ACTION_DELETE_NOTIFICATIONS)) {
        wp_clear_scheduled_hook(WPDUN_ACTION_DELETE_NOTIFICATIONS);
    }
}

function wunCronDeleteNotifications() {
    global $wunDBManager, $wunOptions;
    $wunDBManager->deleteAllOrExpiredNotifications($wunOptions->data["lastXDays"]);
    $wunDBManager->deleteNotifications(["is_new" => 0]);
}

add_action("plugins_loaded", function () {

    include_once WPDUN_DIR_PATH . "/includes/gvt-api-manager.php";
    include_once WPDUN_DIR_PATH . "/includes/wunHelper.php";
    include_once WPDUN_DIR_PATH . "/includes/wunHelperActions.php";
    include_once WPDUN_DIR_PATH . "/includes/wunRoute.php";
    include_once WPDUN_DIR_PATH . "/wunMain.php";

    $wpdiscuzUserNotifications = WpdiscuzUserNotifications::getInstance();
}, 10);
