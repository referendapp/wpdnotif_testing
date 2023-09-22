<?php

if (!defined("ABSPATH")) {
    exit();
}

class WunOptions implements WunConstants {

    public $tabKey = "wun";
    public $dbManager;
    public $data;

    public function __construct($dbManager) {

        $this->dbManager = $dbManager;

        add_option(self::OPTION_MAIN, $this->getDefaultOptions(), "", "no");
        $options = empty($opt = get_option(self::OPTION_MAIN, [])) ? [] : $opt;
        $this->initOptions($options);
        add_action("admin_init", [&$this, "deleteNotifications"], 1);
        add_action("wpdiscuz_save_options", [&$this, "saveOptions"], 99);
        add_action("wpdiscuz_reset_options", [&$this, "resetOptions"], 99);
        add_filter("wpdiscuz_settings", [&$this, "notificationSettings"], 99);
    }

    public function deleteNotifications() {
        if (empty($_GET["wun_delete"]) || empty($_GET["_nonce"]) || empty($_GET["redirect_to"])) {
            return;
        }

        $redirectTo = trim(sanitize_text_field($_GET["redirect_to"]));
        $delete = trim(sanitize_text_field($_GET["wun_delete"]));
        $nonce = trim(sanitize_text_field($_GET["_nonce"]));
        if (!in_array($delete, ["all", "expired", "read"]) || !wp_verify_nonce($nonce, WunHelper::uniqueNonceKey())) {
            return;
        }

        if ($delete === "all") {
            $this->dbManager->deleteAllOrExpiredNotifications();
        } else if ($delete === "expired") {
            $this->dbManager->deleteAllOrExpiredNotifications($this->data["lastXDays"]);
        } else if ($delete === "read") {
            $this->dbManager->deleteNotifications(["is_new" => 0]);
        }

        exit(wp_safe_redirect($redirectTo));
    }

    public function getDefaultOptions() {
        return [
            "loadMethod" => "rest",
            // === enabled notifications === //
            "notifications" => [
                "myCommentVote" => 1,
                "newFollower" => 1,
                "myPostRate" => 1,
                "mention" => 1,
                "myCommentReply" => 1,
                "myPostComment" => 1,
                "subscribedPostComment" => 1,
                "followingUserComment" => 1,
                "myCommentApprove" => 1,
            ],
            "adminBarBell" => 1,
            "browserNotifications" => 1,
            "setReadOnLoad" => 0,
            "bellForRoles" => $this->getDefaultRoles(),
            "bellForGuests" => 1,
            "bellForVisitors" => 0,
            "lastXDays" => 30,
            "liveUpdate" => 1,
            "updateTimer" => 60,
            "perLoad" => 25,
            "showCountOfNotLoaded" => 1,
            "soundUrl" => plugins_url(WPDUN_DIR_NAME . "/assets/audio/pristine.mp3"),
            "playSoundWhen" => "new",
            "bellStyle" => "bordered",
            "containerAnimationInMs" => 300,
            "bellFillColor" => "#00B38F",
            "counterTextColor" => "#ffffff",
            "counterBgColor" => "#ff6b4f",
            "counterShadowColor" => "#dd6650",
            "barBellFillColor" => "#effff4",
            "barCounterTextColor" => "#000000",
            "barCounterBgColor" => "#effff4",
            "barCounterShadowColor" => "#f4fff7",
            // === notifications messages === //
            "ntfTitleCommentLike" => __("New like", "wpdiscuz-user-notifications"),
            "ntfMessageCommentLike" => __("<strong>[NOTIFIER]</strong> liked your <a href='[COMMENT_URL]'>comment</a> <time class='wun-date'>[DATE]</time> <!--wundelete--><a href='[MARK_READ_URL]'>Mark as read</a><!--/wundelete-->", "wpdiscuz-user-notifications"),
            "ntfTitleCommentDislike" => __("New dislike", "wpdiscuz-user-notifications"),
            "ntfMessageCommentDislike" => __("<strong>[NOTIFIER]</strong> disliked your <a href='[COMMENT_URL]'>comment</a> <time class='wun-date'>[DATE]</time> <!--wundelete--><a href='[MARK_READ_URL]'>Mark as read</a><!--/wundelete-->", "wpdiscuz-user-notifications"),
            "ntfTitleNewFollower" => __("New follower", "wpdiscuz-user-notifications"),
            "ntfMessageNewFollower" => __("<strong>[NOTIFIER]</strong> has started following you <time class='wun-date'>[DATE]</time> <!--wundelete--><a href='[MARK_READ_URL]'>Mark as read</a><!--/wundelete-->", "wpdiscuz-user-notifications"),
            "ntfTitleMyPostRate" => __("New rate on my post", "wpdiscuz-user-notifications"),
            "ntfMessageMyPostRate" => __("<strong>[NOTIFIER]</strong> has rated ([RATING]) your post - <a href='[POST_URL]'>[POST_TITLE]</a> <time class='wun-date'>[DATE]</time> <!--wundelete--><a href='[MARK_READ_URL]'>Mark as read</a><!--/wundelete-->", "wpdiscuz-user-notifications"),
            "ntfTitleMention" => __("New mention", "wpdiscuz-user-notifications"),
            "ntfMessageMention" => __("You have been mentioned by <strong>[NOTIFIER]</strong> in this <a href='[COMMENT_URL]'>comment</a> <time class='wun-date'>[DATE]</time> <!--wundelete--><a href='[MARK_READ_URL]'>Mark as read</a><!--/wundelete-->", "wpdiscuz-user-notifications"),
            "ntfTitleMyCommentReply" => __("New Reply", "wpdiscuz-user-notifications"),
            "ntfMessageMyCommentReply" => __("<strong>[NOTIFIER]</strong> has replied to your <a href='[COMMENT_URL]'>comment</a> <time class='wun-date'>[DATE]</time> <!--wundelete--><a href='[MARK_READ_URL]'>Mark as read</a><!--/wundelete-->", "wpdiscuz-user-notifications"),
            "ntfTitleMyPostComment" => __("New Comment", "wpdiscuz-user-notifications"),
            "ntfMessageMyPostComment" => __("<strong>[NOTIFIER]</strong> has <a href='[COMMENT_URL]'>commented</a> on your post: <a href='[POST_URL]'>[POST_TITLE]</a> <time class='wun-date'>[DATE]</time> <!--wundelete--><a href='[MARK_READ_URL]'>Mark as read</a><!--/wundelete-->", "wpdiscuz-user-notifications"),
            "ntfTitleSubscribedPostComment" => __("New Comment", "wpdiscuz-user-notifications"),
            "ntfMessageSubscribedPostComment" => __("<strong>[NOTIFIER]</strong> has left a <a href='[COMMENT_URL]'>comment</a> on your subscribed post: <a href='[POST_URL]'>[POST_TITLE]</a> <time class='wun-date'>[DATE]</time> <!--wundelete--><a href='[MARK_READ_URL]'>Mark as read</a><!--/wundelete-->", "wpdiscuz-user-notifications"),
            "ntfTitleFollowingUserComment" => __("New Comment", "wpdiscuz-user-notifications"),
            "ntfMessageFollowingUserComment" => __("<strong>[NOTIFIER]</strong> has left a <a href='[COMMENT_URL]'>comment</a> on <a href='[POST_URL]'>[POST_TITLE]</a> <time class='wun-date'>[DATE]</time> <!--wundelete--><a href='[MARK_READ_URL]'>Mark as read</a><!--/wundelete-->", "wpdiscuz-user-notifications"),
            "ntfTitleMyCommentApprove" => __("Comment Approved", "wpdiscuz-user-notifications"),
            "ntfMessageMyCommentApprove" => __("Your <a href='[COMMENT_URL]'>comment</a> has been approved <time class='wun-date'>[DATE]</time> <!--wundelete--><a href='[MARK_READ_URL]'>Mark as read</a><!--/wundelete-->", "wpdiscuz-user-notifications"),
            // notification container texts
            "ntfContainerTitle" => __("Notifications", "wpdiscuz-user-notifications"),
            "ntfLoadMore" => __("Load More", "wpdiscuz-user-notifications"),
            "ntfDeleteAll" => __("Delete all", "wpdiscuz-user-notifications"),
            "ntfNoNotifications" => __("No new notifications", "wpdiscuz-user-notifications"),
        ];
    }

    public function initOptions($options) {
        $this->data = array_merge($this->getDefaultOptions(), $options);
        return $this->data;
    }

    public function saveOptions() {
        if ($this->tabKey === sanitize_text_field($_POST["wpd_tab"])) {
            $defaultOptions = $this->getDefaultOptions();
            $postOptions = $_POST[$this->tabKey];
            $options = array_merge($defaultOptions, $postOptions);
            $options["loadMethod"] = WunHelper::wunKsesPost($options["loadMethod"]);
            $options["notifications"] = array_map("intval", $options["notifications"]);
            $options["adminBarBell"] = absint($options["adminBarBell"]);
            $options["browserNotifications"] = absint($options["browserNotifications"]);
            $options["setReadOnLoad"] = absint($options["setReadOnLoad"]);
            $options["bellForRoles"] = empty($postOptions["bellForRoles"]) ? [] : array_map("sanitize_textarea_field", $postOptions["bellForRoles"]);
            $options["bellForGuests"] = absint($options["bellForGuests"]);
            $options["bellForVisitors"] = absint($options["bellForVisitors"]);
            $options["lastXDays"] = ($v = absint($options["lastXDays"])) ? $v : 30;
            $options["liveUpdate"] = absint($options["liveUpdate"]);
            $options["updateTimer"] = ($v = absint($options["updateTimer"])) ? $v : 60;
            $options["perLoad"] = ($v = absint($options["perLoad"])) ? $v : 25;
            $options["showCountOfNotLoaded"] = absint($options["showCountOfNotLoaded"]);
            $options["soundUrl"] = empty(trim($options["soundUrl"])) ? "" : WunHelper::wunKsesPost($options["soundUrl"]);
            $options["playSoundWhen"] = WunHelper::wunKsesPost($options["playSoundWhen"]);
            $options["bellStyle"] = empty(trim($options["bellStyle"])) ? "bordered" : WunHelper::wunKsesPost($options["bellStyle"]);
            $options["containerAnimationInMs"] = absint($options["containerAnimationInMs"]);
            $options["bellFillColor"] = empty(trim($options["bellFillColor"])) ? "" : WunHelper::wunKsesPost($options["bellFillColor"]);
            $options["counterTextColor"] = empty(trim($options["counterTextColor"])) ? "" : WunHelper::wunKsesPost($options["counterTextColor"]);
            $options["counterBgColor"] = empty(trim($options["counterBgColor"])) ? "" : WunHelper::wunKsesPost($options["counterBgColor"]);
            $options["counterShadowColor"] = empty(trim($options["counterShadowColor"])) ? "" : WunHelper::wunKsesPost($options["counterShadowColor"]);
            $options["barBellFillColor"] = empty(trim($options["barBellFillColor"])) ? "" : WunHelper::wunKsesPost($options["barBellFillColor"]);
            $options["barCounterTextColor"] = empty(trim($options["barCounterTextColor"])) ? "" : WunHelper::wunKsesPost($options["barCounterTextColor"]);
            $options["barCounterBgColor"] = empty(trim($options["barCounterBgColor"])) ? "" : WunHelper::wunKsesPost($options["barCounterBgColor"]);
            $options["barCounterShadowColor"] = empty(trim($options["barCounterShadowColor"])) ? "" : WunHelper::wunKsesPost($options["barCounterShadowColor"]);
            // notifications messages
            $options["ntfTitleCommentLike"] = empty(trim($options["ntfTitleCommentLike"])) ? "" : WunHelper::wunKsesPost($options["ntfTitleCommentLike"]);
            $options["ntfMessageCommentLike"] = empty(trim($options["ntfMessageCommentLike"])) ? WunHelper::wunKsesPost($defaultOptions["ntfMessageCommentLike"]) : WunHelper::wunKsesPost($options["ntfMessageCommentLike"]);
            $options["ntfTitleCommentDislike"] = empty(trim($options["ntfTitleCommentDislike"])) ? "" : WunHelper::wunKsesPost($options["ntfTitleCommentDislike"]);
            $options["ntfMessageCommentDislike"] = empty(trim($options["ntfMessageCommentDislike"])) ? WunHelper::wunKsesPost($defaultOptions["ntfMessageCommentDislike"]) : WunHelper::wunKsesPost($options["ntfMessageCommentDislike"]);
            $options["ntfTitleNewFollower"] = empty(trim($options["ntfTitleNewFollower"])) ? "" : WunHelper::wunKsesPost($options["ntfTitleNewFollower"]);
            $options["ntfMessageNewFollower"] = empty(trim($options["ntfMessageNewFollower"])) ? WunHelper::wunKsesPost($defaultOptions["ntfMessageNewFollower"]) : WunHelper::wunKsesPost($options["ntfMessageNewFollower"]);
            $options["ntfTitleMyPostRate"] = empty(trim($options["ntfTitleMyPostRate"])) ? "" : WunHelper::wunKsesPost($options["ntfTitleMyPostRate"]);
            $options["ntfMessageMyPostRate"] = empty(trim($options["ntfMessageMyPostRate"])) ? WunHelper::wunKsesPost($defaultOptions["ntfMessageMyPostRate"]) : WunHelper::wunKsesPost($options["ntfMessageMyPostRate"]);
            $options["ntfTitleMention"] = empty(trim($options["ntfTitleMention"])) ? "" : WunHelper::wunKsesPost($options["ntfTitleMention"]);
            $options["ntfMessageMention"] = empty(trim($options["ntfMessageMention"])) ? WunHelper::wunKsesPost($defaultOptions["ntfMessageMention"]) : WunHelper::wunKsesPost($options["ntfMessageMention"]);
            $options["ntfTitleMyCommentReply"] = empty(trim($options["ntfTitleMyCommentReply"])) ? "" : WunHelper::wunKsesPost($options["ntfTitleMyCommentReply"]);
            $options["ntfMessageMyCommentReply"] = empty(trim($options["ntfMessageMyCommentReply"])) ? WunHelper::wunKsesPost($defaultOptions["ntfMessageMyCommentReply"]) : WunHelper::wunKsesPost($options["ntfMessageMyCommentReply"]);
            $options["ntfTitleMyPostComment"] = empty(trim($options["ntfTitleMyPostComment"])) ? "" : WunHelper::wunKsesPost($options["ntfTitleMyPostComment"]);
            $options["ntfMessageMyPostComment"] = empty(trim($options["ntfMessageMyPostComment"])) ? WunHelper::wunKsesPost($defaultOptions["ntfMessageMyPostComment"]) : WunHelper::wunKsesPost($options["ntfMessageMyPostComment"]);
            $options["ntfTitleSubscribedPostComment"] = empty(trim($options["ntfTitleSubscribedPostComment"])) ? "" : WunHelper::wunKsesPost($options["ntfTitleSubscribedPostComment"]);
            $options["ntfMessageSubscribedPostComment"] = empty(trim($options["ntfMessageSubscribedPostComment"])) ? WunHelper::wunKsesPost($defaultOptions["ntfMessageSubscribedPostComment"]) : WunHelper::wunKsesPost($options["ntfMessageSubscribedPostComment"]);
            $options["ntfTitleFollowingUserComment"] = empty(trim($options["ntfTitleFollowingUserComment"])) ? "" : WunHelper::wunKsesPost($options["ntfTitleFollowingUserComment"]);
            $options["ntfMessageFollowingUserComment"] = empty(trim($options["ntfMessageFollowingUserComment"])) ? WunHelper::wunKsesPost($defaultOptions["ntfMessageFollowingUserComment"]) : WunHelper::wunKsesPost($options["ntfMessageFollowingUserComment"]);
            $options["ntfTitleMyCommentApprove"] = empty(trim($options["ntfTitleMyCommentApprove"])) ? "" : WunHelper::wunKsesPost($options["ntfTitleMyCommentApprove"]);
            $options["ntfMessageMyCommentApprove"] = empty(trim($options["ntfMessageMyCommentApprove"])) ? WunHelper::wunKsesPost($defaultOptions["ntfMessageMyCommentApprove"]) : WunHelper::wunKsesPost($options["ntfMessageMyCommentApprove"]);
            $options["ntfContainerTitle"] = WunHelper::wunKsesPost($options["ntfContainerTitle"]);
            $options["ntfLoadMore"] = WunHelper::wunKsesPost($options["ntfLoadMore"]);
            $options["ntfDeleteAll"] = WunHelper::wunKsesPost($options["ntfDeleteAll"]);
            $options["ntfNoNotifications"] = WunHelper::wunKsesPost($options["ntfNoNotifications"]);
            update_option(self::OPTION_MAIN, $options);
            $this->initOptions($options);
        }
    }

    public function resetOptions($tab) {
        if ($tab === $this->tabKey || $tab === "all") {
            delete_option(self::OPTION_MAIN);
            $defaultOptions = $this->getDefaultOptions();
            add_option(self::OPTION_MAIN, $defaultOptions, "", "no");
            $this->initOptions($defaultOptions);
        }
    }

    public function notificationSettings($options) {
        $options["addons"][$this->tabKey] = [
            "title" => __("User Notifications", "wpdiscuz-user-notifications"),
            "title_original" => "User Notifications",
            "icon" => "",
            "icon-height" => "",
            "file_path" => WPDUN_DIR_PATH . "/options/html-options.php",
            "values" => $this,
            "options" => [
                "loadMethod" => [
                    "label" => __("Notifications loading method:", "wpdiscuz-user-notifications"),
                    "label_original" => "Notifications loading method:",
                    "description" => __("By default, notifications are loaded by WordPress's REST API. If the REST API is not available or if it works with errors, we recommend you change the notification loading method to AJAX.", "wpdiscuz-user-notifications"),
                    "description_original" => "By default, notifications are loaded by WordPress's REST API. If the REST API is not available or if it works with errors, we recommend you change the notification loading method to AJAX.",
                    "docurl" => "#"
                ],
                "notifications" => [
                    "label" => __("Notify me when:", "wpdiscuz-user-notifications"),
                    "label_original" => "Notify when:",
                    "description" => __("There are many types of user notifications coming from the comment section. Here you can manage them for all users.", "wpdiscuz-user-notifications"),
                    "description_original" => "There are many types of user notifications coming from the comment section. Here you can manage them for all users.",
                    "docurl" => "#"
                ],
                "adminBarBell" => [
                    "label" => __("Show notification bell in the top admin bar", "wpdiscuz-user-notifications"),
                    "label_original" => "Show notification bell in the top admin bar",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#"
                ],
                "browserNotifications" => [
                    "label" => __("Web Push Notifications", "wpdiscuz-user-notifications"),
                    "label_original" => "Web Push Notifications",
                    "description" => __("Web Push allows websites to notify you of new messages or updated content. While the browser is open, websites which have been granted permission can send notifications to your browser, which displays them on the screen.", "wpdiscuz-user-notifications"),
                    "description_original" => "Web Push allows websites to notify you of new messages or updated content. While the browser is open, websites which have been granted permission can send notifications to your browser, which displays them on the screen.",
                    "docurl" => "#"
                ],
                "setReadOnLoad" => [
                    "label" => __("Set notifications as [READ] on load", "wpdiscuz-user-notifications"),
                    "label_original" => "Set notifications as [READ] on load",
                    "description" => __("If you enable this option the notifications will be set as [READ] once they have been loaded.", "wpdiscuz-user-notifications"),
                    "description_original" => "If you enable this option the notifications will be set as [READ] once they have been loaded.",
                    "docurl" => "#"
                ],
                "bellForRoles" => [
                    "label" => __("Display notification bell for roles", "wpdiscuz-user-notifications"),
                    "label_original" => "Display bell for roles",
                    "description" => __("User roles who are allowed to see the bell and receive notifications.", "wpdiscuz-user-notifications"),
                    "description_original" => "User roles who are allowed to see the bell and receive notifications.",
                    "docurl" => "#"
                ],
                "bellForGuests" => [
                    "label" => __("Display notification bell for guests", "wpdiscuz-user-notifications"),
                    "label_original" => "Display bell for guests",
                    "description" => __("Display the bell and allow to receive notifications for non-logged-in users.", "wpdiscuz-user-notifications"),
                    "description_original" => "Display the bell and allow to receive notifications for non-logged-in users.",
                    "docurl" => "#"
                ],
                "bellForVisitors" => [
                    "label" => __("Display notification bell for new visitors", "wpdiscuz-user-notifications"),
                    "label_original" => "Display bell for new visitors",
                    "description" => __("New visitors have never commented on your website and do not have commenter name and email information in cookies. This kind of visitors cannot be tracked for notifications.", "wpdiscuz-user-notifications"),
                    "description_original" => "New visitors have never commented on your website and do not have commenter name and email information in cookies. This kind of visitors cannot be tracked for notifications.",
                    "docurl" => "#"
                ],
                "lastXDays" => [
                    "label" => __("Show notifications for last X days", "wpdiscuz-user-notifications"),
                    "label_original" => "Show notifications for last X days",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#"
                ],
                "liveUpdate" => [
                    "label" => __("Live update", "wpdiscuz-user-notifications"),
                    "label_original" => "Live update",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#"
                ],
                "updateTimer" => [
                    "label" => __("Update every", "wpdiscuz-user-notifications"),
                    "label_original" => "Update every",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#"
                ],
                "perLoad" => [
                    "label" => __("Notifications count per load", "wpdiscuz-user-notifications"),
                    "label_original" => "Notifications count per load",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#"
                ],
                "showCountOfNotLoaded" => [
                    "label" => __("Display the count of not loaded notifications on load more button.", "wpdiscuz-user-notifications"),
                    "label_original" => "Display the count of not loaded notifications on load more button.",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#"
                ],
                "soundUrl" => [
                    "label" => __("Notification sound URL", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification sound URL",
                    "description" => __("Please note that notifications sound will work with .mp3 files only and when the user already interacted with the website.", "wpdiscuz-user-notifications"),
                    "description_original" => "Please note that notifications sound will work with .mp3 files only and when the user already interacted with the website.",
                    "docurl" => "#"
                ],
                "playSoundWhen" => [
                    "label" => __("Play the sound when notification is:", "wpdiscuz-user-notifications"),
                    "label_original" => "Play the sound when notification is:",
                    "description" => __("[NEW] plays the sound on every new notification. [UNREAD] plays the sound if there is an unread notification on every page loading and notification checking action.", "wpdiscuz-user-notifications"),
                    "description_original" => "[NEW] plays the sound on every new notification. [UNREAD] plays the sound if there is an unread notification on every page loading and notification checking action.",
                    "docurl" => "#"
                ],
                "bellStyle" => [
                    "label" => __("Bell Style", "wpdiscuz-user-notifications"),
                    "label_original" => "Bell Style",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#"
                ],
                "containerAnimationInMs" => [
                    "label" => __("Notifications' container animation speed in milliseconds.", "wpdiscuz-user-notifications"),
                    "label_original" => "Notifications' container animation speed in milliseconds.",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#"
                ],
                "colors" => [
                    "label" => __("Colors", "wpdiscuz-user-notifications"),
                    "label_original" => "Colors",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#"
                ],
                "deleteAllNotifications" => [
                    "label" => __("Delete all notifications", "wpdiscuz-user-notifications"),
                    "label_original" => "Delete all notifications",
                    "description" => __("This button deletes all notifications from the database", "wpdiscuz-user-notifications"),
                    "description_original" => "This button deletes all notifications in the database",
                    "docurl" => "#"
                ],
                "deleteExpiredNotifications" => [
                    "label" => __("Delete expired notifications", "wpdiscuz-user-notifications"),
                    "label_original" => "Delete expired notifications",
                    "description" => __("This button deletes expired notifications from the database. It uses the 'Show notifications for last X Days' option for detecting the expired notifications.", "wpdiscuz-user-notifications"),
                    "description_original" => "This button deletes expired notifications in the database. It uses the 'Show notifications for last X Days' option for detecting the expired notifications.",
                    "docurl" => "#"
                ],
                "deleteReadNotifications" => [
                    "label" => __("Delete read notifications", "wpdiscuz-user-notifications"),
                    "label_original" => "Delete read notifications",
                    "description" => __("This button deletes read notifications from the database.", "wpdiscuz-user-notifications"),
                    "description_original" => "This button deletes expired notifications in the database.",
                    "docurl" => "#"
                ],
                "ntfTitleCommentLike" => [
                    "label" => __("Notification Title - New Like", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Title - New Like",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfMessageCommentLike" => [
                    "label" => __("Notification Message - New Like", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Message - New Like",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfTitleCommentDislike" => [
                    "label" => __("Notification Title - New Dislike", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Title - New Dislike",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfMessageCommentDislike" => [
                    "label" => __("Notification Message - New Dislike", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Message - New Dislike",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfTitleNewFollower" => [
                    "label" => __("Notification Title - New Follower", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Title - New Follower",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfMessageNewFollower" => [
                    "label" => __("Notification Message - New Follower", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Message - New Follower",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfTitleMyPostRate" => [
                    "label" => __("Notification Title - New Rate", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Title - New Rate",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfMessageMyPostRate" => [
                    "label" => __("Notification Message - New Rate", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Message - New Rate",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfTitleMention" => [
                    "label" => __("Notification Title - New Mention", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Title - New Mention",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfMessageMention" => [
                    "label" => __("Notification Message - New Mention", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Message - New Mention",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfTitleMyCommentReply" => [
                    "label" => __("Notification Title - New Reply", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Title - New Reply",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfMessageMyCommentReply" => [
                    "label" => __("Notification Message - New Reply", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Message - New Reply",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfTitleMyPostComment" => [
                    "label" => __("Notification Title - New Comment", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Title - New Comment",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfMessageMyPostComment" => [
                    "label" => __("Notification Message - New Comment", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Message - New Comment",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfTitleSubscribedPostComment" => [
                    "label" => __("Notification Title - New Comment", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Title - New Comment",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfMessageSubscribedPostComment" => [
                    "label" => __("Notification Message - New Comment", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Message - New Comment",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfTitleFollowingUserComment" => [
                    "label" => __("Notification Title - New Comment", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Title - New Comment",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfMessageFollowingUserComment" => [
                    "label" => __("Notification Message - New Comment", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Message - New Comment",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfTitleMyCommentApprove" => [
                    "label" => __("Notification Title - Comment Approved", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Title - Comment Approved",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfMessageMyCommentApprove" => [
                    "label" => __("Notification Message - Comment Approved", "wpdiscuz-user-notifications"),
                    "label_original" => "Notification Message - Comment Approved",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-notifications-texts"
                ],
                "ntfContainerTitle" => [
                    "label" => __("Container Title", "wpdiscuz-user-notifications"),
                    "label_original" => "Container Title",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-container-texts"
                ],
                "ntfLoadMore" => [
                    "label" => __("Load More Button Text", "wpdiscuz-user-notifications"),
                    "label_original" => "Load More Button Text",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-container-texts"
                ],
                "ntfDeleteAll" => [
                    "label" => __("Delete All Button Text", "wpdiscuz-user-notifications"),
                    "label_original" => "Delete All Button Text",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-container-texts"
                ],
                "ntfNoNotifications" => [
                    "label" => __("No New Notifications Text", "wpdiscuz-user-notifications"),
                    "label_original" => "No New Notifications Text",
                    "description" => "",
                    "description_original" => "",
                    "docurl" => "#",
                    "accordion" => "wun-container-texts"
                ],
            ],
        ];
        return $options;
    }

    private function getDefaultRoles() {
        global $wp_roles;
        $roles = [];
        $blogRoles = empty($wp_roles->roles) ? [] : $wp_roles->roles;

        foreach ($blogRoles as $role => $info) {
            if (!isset($info["capabilities"])) {
                continue;
            }
            $roles[] = $role;
        }
        return $roles;
    }

    public function getActions() {
        $actions = [];
        if (!empty($this->data["notifications"]["myCommentVote"])) {
            $actions[] = self::ACTION_VOTE;
        }

        if (!empty($this->data["notifications"]["newFollower"])) {
            $actions[] = self::ACTION_FOLLOWER;
        }

        if (!empty($this->data["notifications"]["myPostRate"])) {
            $actions[] = self::ACTION_MY_POST_RATE;
        }

        if (!empty($this->data["notifications"]["mention"])) {
            $actions[] = self::ACTION_MENTION;
        }

        if (!empty($this->data["notifications"]["myCommentReply"])) {
            $actions[] = self::ACTION_MY_COMMENT_REPLY;
        }

        if (!empty($this->data["notifications"]["myPostComment"])) {
            $actions[] = self::ACTION_MY_POST_COMMENT;
        }

        if (!empty($this->data["notifications"]["subscribedPostComment"])) {
            $actions[] = self::ACTION_SUBSCRIBED_POST_COMMENT;
        }

        if (!empty($this->data["notifications"]["followingUserComment"])) {
            $actions[] = self::ACTION_FOLLOWING_USER_COMMENT;
        }

        if (!empty($this->data["notifications"]["myCommentApprove"])) {
            $actions[] = self::ACTION_MY_COMMENT_APPROVE;
        }
        return $actions;
    }

    public function isNotificationsActive($emailInCookie = "") {
        $isActive = false;

        $currentUser = WunHelper::getCurrentUser();

        if (empty($currentUser->ID)) {
            if (empty($emailInCookie)) {
                $emailInCookie = filter_input(INPUT_COOKIE, "comment_author_email_" . COOKIEHASH, FILTER_SANITIZE_EMAIL);
            }

            if (empty($emailInCookie) && $this->data["bellForVisitors"]) {
                $isActive = true;
            } else if (!empty($emailInCookie) && $this->data["bellForGuests"]) {
                $isActive = true;
            }
        } else if (!empty($currentUser->roles) && is_array($currentUser->roles) && !empty($this->data["bellForRoles"])) {
            foreach ($currentUser->roles as $role) {
                if (in_array($role, $this->data["bellForRoles"])) {
                    $isActive = true;
                    break;
                }
            }
        }

        $isActive = apply_filters("wpdiscuz_un_restrict_notifications_access", $isActive, $emailInCookie, $currentUser);

        return $isActive;
    }

}
