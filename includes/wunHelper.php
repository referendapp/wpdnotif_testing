<?php

if (!defined("ABSPATH")) {
    exit();
}

class WunHelper implements WunConstants {

    private $options;
    private static $allowedTags;
    private static $cipher;
    private static $key;
    private static $opt;
    private static $iv;
    private static $POST_TYPES_NATIVE = ["post", "page", "attachment"];
    private static $CURRENT_USER = null;

    public function __construct($options) {
        $this->options = $options;
        self::$allowedTags = array_merge(wp_kses_allowed_html("post"),
                ["time" => [
                        "class" => true,
                        "style" => true
                    ]
                ]
        );

        add_filter("wp_get_nav_menu_items", [$this, "shortcodeMenu"], 10, 3);
        add_action("admin_bar_menu", [$this, "barMenu"], 999);
        add_shortcode("wpdiscuz_bell", [$this, "getBellHtml"]);
        if ($this->options->isNotificationsActive()) {
            add_action("wp_footer", [$this, "notificationContainer"], 10, 3);
            add_action("admin_footer", [$this, "notificationContainer"], 10, 3);
        }
    }

    public function shortcodeMenu($items, $menu, $args) {

        if (is_admin() || empty($items) || !is_array($items)) {
            return $items;
        }

        $emailInCookie = filter_input(INPUT_COOKIE, "comment_author_email_" . COOKIEHASH, FILTER_SANITIZE_EMAIL);
        $isActive = $this->options->isNotificationsActive($emailInCookie);

        foreach ($items as $key => $item) {
            if ($item->type === "custom" && !empty($item->url)) {
                $shortcode = trim(str_replace(["https://", "http://", "www.", "/", "%"], "", $item->url));
                if ($shortcode === "wpdiscuz-bell") {

                    if (!$isActive) {
                        unset($items[$key]);
                        continue;
                    }

                    $item->url = "#!";
                    $item->title = $this->getBell();
                    $item->classes = array_filter(array_unique(["menu-item-wun-bell"]));
                }
            }
        }
        return $items;
    }

    public function barMenu($bar) {
        if (!$this->options->data["adminBarBell"]) {
            return;
        }

        if ($this->options->isNotificationsActive()) {
            $bar->add_menu([
                "id" => $this->options->tabKey,
                "parent" => "top-secondary",
                "title" => $this->getBell(true),
                "href" => "#!",
                "meta" => [
                    "title" => "wpDiscuz",
                    "target" => "_blank",
                    "class" => "menu-item-wun-bell menupop",
                ],
            ]);
        }
    }

    private function getBell($isAdminBarBell = false) {
        if ($isAdminBarBell) {
            $fillKey = "barBellFillColor";
            $counterTextColorKey = "barCounterTextColor";
            $counterBgColorKey = "barCounterBgColor";
            $counterShadowColorKey = "barCounterShadowColor";
        } else {
            $fillKey = "bellFillColor";
            $counterTextColorKey = "counterTextColor";
            $counterBgColorKey = "counterBgColor";
            $counterShadowColorKey = "counterShadowColor";
        }


        $fill = empty($this->options->data[$fillKey]) ? "" : "fill:" . $this->options->data[$fillKey] . "!important;";
        $counterTextColor = empty($this->options->data[$counterTextColorKey]) ? "" : "color:" . $this->options->data[$counterTextColorKey] . "!important;";
        $counterBgColor = empty($this->options->data[$counterBgColorKey]) ? "" : "background-color:" . $this->options->data[$counterBgColorKey] . "!important;";
        $counterShadowColor = empty($this->options->data[$counterShadowColorKey]) ? "" : "box-shadow: 0px 1px 8px " . $this->options->data[$counterShadowColorKey] . "!important;";

        $countContainer = "<div class='wun-count' style='{$counterTextColor}{$counterBgColor}{$counterShadowColor}'></div>";

        if ($this->options->data["bellStyle"] === "bordered") { // bordered
            $bell = "<svg id='icon-bell-o' class='wun-bell' viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg' style='{$fill}'><path d='M14.25 26.5c0-0.141-0.109-0.25-0.25-0.25-1.234 0-2.25-1.016-2.25-2.25 0-0.141-0.109-0.25-0.25-0.25s-0.25 0.109-0.25 0.25c0 1.516 1.234 2.75 2.75 2.75 0.141 0 0.25-0.109 0.25-0.25zM3.844 22h20.312c-2.797-3.156-4.156-7.438-4.156-13 0-2.016-1.906-5-6-5s-6 2.984-6 5c0 5.563-1.359 9.844-4.156 13zM27 22c0 1.094-0.906 2-2 2h-7c0 2.203-1.797 4-4 4s-4-1.797-4-4h-7c-1.094 0-2-0.906-2-2 2.312-1.953 5-5.453 5-13 0-3 2.484-6.281 6.625-6.891-0.078-0.187-0.125-0.391-0.125-0.609 0-0.828 0.672-1.5 1.5-1.5s1.5 0.672 1.5 1.5c0 0.219-0.047 0.422-0.125 0.609 4.141 0.609 6.625 3.891 6.625 6.891 0 7.547 2.688 11.047 5 13z'></path></svg>";
        } else { // filled
            $bell = "<svg id='icon-bell' class='wun-bell' viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg' style='{$fill}'><path d='M14.25 26.5c0-0.141-0.109-0.25-0.25-0.25-1.234 0-2.25-1.016-2.25-2.25 0-0.141-0.109-0.25-0.25-0.25s-0.25 0.109-0.25 0.25c0 1.516 1.234 2.75 2.75 2.75 0.141 0 0.25-0.109 0.25-0.25zM27 22c0 1.094-0.906 2-2 2h-7c0 2.203-1.797 4-4 4s-4-1.797-4-4h-7c-1.094 0-2-0.906-2-2 2.312-1.953 5-5.453 5-13 0-3 2.484-6.281 6.625-6.891-0.078-0.187-0.125-0.391-0.125-0.609 0-0.828 0.672-1.5 1.5-1.5s1.5 0.672 1.5 1.5c0 0.219-0.047 0.422-0.125 0.609 4.141 0.609 6.625 3.891 6.625 6.891 0 7.547 2.688 11.047 5 13z'></path></svg>";
        }
        return $bell . $countContainer;
    }

    public function notificationContainer() {
        ob_start();
        include "html/container.php";
        echo ob_get_clean();
    }

    public function getBellHtml() {
        if (!$this->options->isNotificationsActive()) {
            return "";
        }
        
        ob_start();
        include "html/bell.php";
        return ob_get_clean();
    }

    public static function wunKsesPost($value) {
        return wp_kses(stripslashes_deep(trim($value)), self::$allowedTags);
    }

    public static function arrayAsMySQLIn($data) {

        if (empty($data)) {
            return "";
        }

        if (is_string($data)) {
            return "'" . esc_sql($data) . "'";
        }

        $inStr = "";
        if (is_array($data)) {
            foreach ($data as $str) {
                $inStr .= "'" . esc_sql($str) . "',";
            }
        }
        return rtrim($inStr, ",");
    }

    public static function getNotifyer() {
        $currentUser = WunHelper::getCurrentUser();
        $notifier = [
            "user_id" => 0,
            "user_email" => "",
            "user_name" => "",
        ];
        if (empty($currentUser->ID)) {
            if (!empty($_COOKIE["comment_author_" . COOKIEHASH])) {
                $notifier["user_name"] = trim(sanitize_text_field($_COOKIE["comment_author_" . COOKIEHASH]));
            }

            if (!empty($_COOKIE["comment_author_email_" . COOKIEHASH])) {
                $notifier["user_email"] = trim(sanitize_text_field($_COOKIE["comment_author_email_" . COOKIEHASH]));
            }
        } else {
            $notifier["user_id"] = $currentUser->ID;
            $notifier["user_email"] = $currentUser->user_email;
            $notifier["user_name"] = $currentUser->display_name;
        }
        return $notifier;
    }

    /**
     * return client real ip
     */
    public static function getRealIPAddr() {
        $ip = $_SERVER["REMOTE_ADDR"];

        if ($ip === "::1") {
            $ip = "127.0.0.1";
        }

        return $ip;
    }

    public static function getPermalink($postId) {
        return in_array(get_post_type($postId), self::$POST_TYPES_NATIVE) ? get_permalink($postId) : get_post_permalink($postId);
    }

    public static function initEncryptionArgs() {
        if (extension_loaded("openssl")) {
            self::$cipher = "AES-128-CTR";
            self::$key = openssl_digest(ABSPATH . php_uname(), "MD5", true);
            self::$opt = 0;
            $ivLength = openssl_cipher_iv_length(self::$cipher);
            self::$iv = substr(bin2hex(ABSPATH . php_uname()), 0, $ivLength);
        }
    }

    public static function wunEncryptDecrypt($data, $type = "e") {
        if ($type === "e") { // encrypt
            if (is_array($data)) {
                array_walk_recursive($data, ["WunHelper", "wunEncryptCallback"]);
            } else if (is_object($data)) {
                $data = (array) $data;
                array_walk_recursive($data, ["WunHelper", "wunEncryptCallback"]);
            } else {
                return extension_loaded("openssl") ? openssl_encrypt($data, self::$cipher, self::$key, self::$opt, self::$iv) : base64_encode($data);
            }
        } else { // decrypt
            if (is_array($data)) {
                array_walk_recursive($data, ["WunHelper", "wunDecryptCallback"]);
            } else if (is_object($data)) {
                $data = (array) $data;
                array_walk_recursive($data, ["WunHelper", "wunDecryptCallback"]);
            } else {
                return extension_loaded("openssl") ? openssl_decrypt($data, self::$cipher, self::$key, self::$opt, self::$iv) : base64_decode($data);
            }
        }
        return $data;
    }

    public static function wunEncryptCallback(&$item, $key) {
        $item = self::wunEncryptDecrypt($item, "e");
    }

    public static function wunDecryptCallback(&$item, $key) {
        $item = self::wunEncryptDecrypt($item, "d");
    }

    public static function getNotificationArgs($data) {
        $args = [];
        if (!empty($data["id"]))
            $args["id"] = $data["id"];
        if (!empty($data["recipient_id"]))
            $args["recipient_id"] = $data["recipient_id"];
        if (!empty($data["recipient_email"]))
            $args["recipient_email"] = $data["recipient_email"];
        if (!empty($data["user_id"]))
            $args["user_id"] = $data["user_id"];
        if (!empty($data["user_email"]))
            $args["user_email"] = $data["user_email"];
        if (!empty($data["user_ip"]))
            $args["user_ip"] = $data["user_ip"];
        if (!empty($data["item_id"]))
            $args["item_id"] = $data["item_id"];
        if (!empty($data["secondary_item_id"]))
            $args["secondary_item_id"] = $data["secondary_item_id"];
        if (!empty($data["component_name"]))
            $args["component_name"] = $data["component_name"];
        if (!empty($data["component_action"]))
            $args["component_action"] = $data["component_action"];
        if (!empty($data["action_date"]))
            $args["action_date"] = $data["action_date"];
        if (!empty($data["action_timestamp"]))
            $args["action_timestamp"] = $data["action_timestamp"];
        if (!empty($data["is_new"]))
            $args["is_new"] = $data["is_new"];
        if (!empty($data["extras"]))
            $args["extras"] = $data["extras"];
        return $args;
    }

    public static function getNotificationDataFromComment($comment, $componentAction = "", $componentName = "wpdiscuz") {
        if (empty($comment->comment_ID)) {
            return [];
        }

        $data = [];
        $data["user_id"] = (int) $comment->user_id;
        $data["user_email"] = sanitize_text_field($comment->comment_author_email);
        $data["user_name"] = sanitize_text_field($comment->comment_author);
        $data["user_ip"] = WunHelper::getRealIPAddr();
        $data["item_id"] = (int) $comment->comment_post_ID;
        $data["secondary_item_id"] = (int) $comment->comment_ID;
        $data["component_name"] = $componentName;
        $data["component_action"] = $componentAction;
        $data["action_date"] = current_time("mysql");
        $data["action_timestamp"] = current_time("timestamp");
        $data["is_new"] = 1;
        $data["extras"] = "";
        return $data;
    }

    public static function addNotificationsOnApprove() {
        if (empty($_REQUEST["action"])) {
            return false;
        }

        $defaultActions = [
            "dim-comment",
            "replyto-comment",
        ];

        $ajaxActions = apply_filters("wun_ajax_actions_add_notifications_on_approve", $defaultActions);

        if (!is_array($ajaxActions)) {
            $ajaxActions = $defaultActions;
        }

        $action = trim(sanitize_text_field($_REQUEST["action"]));
        return in_array($action, $ajaxActions) || apply_filters("wun_add_notifications_on_approve", false);
    }

    public static function uniqueNonceKey() {
        return md5(ABSPATH . get_home_url());
    }

    public static function uniqueNonce() {
        return wp_create_nonce(self::uniqueNonceKey());
    }

    public static function getMicrotime() {
        list($pfx_usec, $pfx_sec) = explode(" ", microtime());
        return ((float) $pfx_usec + (float) $pfx_sec);
    }

    public static function getCurrentUser() {
        if (is_null(self::$CURRENT_USER)) {
            self::$CURRENT_USER = wp_get_current_user();
        }
        return self::$CURRENT_USER;
    }

}
