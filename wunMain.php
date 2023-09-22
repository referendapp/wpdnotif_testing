<?php

if (!defined("ABSPATH")) {
    exit();
}

class WpdiscuzUserNotifications implements WunConstants {

    private $dbManager;
    private $options;
    private $route;
    private $helper;
    private $helperActions;
    private $versionDB;
    private $versionPluginFile;
    public $apimanager;
    private static $INSTANCE = null;

    private function __construct() {
        add_action("init", [&$this, "wunDependencies"]);
    }

    public static function getInstance() {
        if (is_null(self::$INSTANCE)) {
            self::$INSTANCE = new self();
        }
        return self::$INSTANCE;
    }

    public function wunDependencies() {
        if (function_exists("wpDiscuz")) {
            add_option(self::OPTION_VERSION, "1.0.0");
            $this->versionDB = get_option(self::OPTION_VERSION, "1.0.0");
            $this->apimanager = new GVT_API_Manager(WPDUN_INDEX, WpdiscuzCore::PAGE_SETTINGS, "wpdiscuz_option_page");
            $this->dbManager = new WunDBManager();
            $this->options = new WunOptions($this->dbManager);
            $this->route = new WunRoute($this->dbManager, $this->options);
            $this->helper = new WunHelper($this->options);
            $this->helperActions = new WunHelperActions($this->dbManager, $this->options);

            add_action("wpdiscuz_check_version", [&$this, "newVersion"]);
            add_action("admin_enqueue_scripts", [&$this, "backendFiles"]);
            if ($this->options->isNotificationsActive()) {
                add_action("wp_enqueue_scripts", [&$this, "frontendFiles"]);
            }
            load_plugin_textdomain("wpdiscuz-user-notifications", false, WPDUN_DIR_PATH . "/languages/");
        }
    }

    public function newVersion() {
        $pluginData = get_plugin_data(__FILE__);
        $this->versionPluginFile = empty($pluginData["Version"]) ? "1.0.0" : $pluginData["Version"];
        $this->versionDB = get_option(self::OPTION_VERSION, $this->versionPluginFile);
        if (version_compare($this->versionPluginFile, $this->versionDB, ">")) {
            $options = get_option(self::OPTION_MAIN);
            $this->addNewOptions($options);
            update_option(self::OPTION_VERSION, $this->versionPluginFile);
        }
    }

    /**
     * merge old and new options
     */
    private function addNewOptions($options) {
        $newOptions = $this->options->initOptions($options);
        update_option(self::OPTION_MAIN, $newOptions);
    }

    public function backendFiles() {
        $suffix = is_rtl() ? "-rtl" : "";
        $args = $this->getJsArgs();

        $backendArgs = [];
        $backendArgs["wunMsgDeleteAllNotifications"] = esc_html__("Are you sure you want to delete all notifications", "wpdiscuz-user-notifications");
        $backendArgs["wunMsgDeleteExpiredNotifications"] = esc_html__("Are you sure you want to delete expired notifications", "wpdiscuz-user-notifications");
        $backendArgs["wunMsgDeleteReadNotifications"] = esc_html__("Are you sure you want to delete read notifications", "wpdiscuz-user-notifications");

        wp_register_style("wun-backend-css", plugins_url(WPDUN_DIR_NAME . "/assets/css/wun-backend{$suffix}.css"), null, $this->versionDB);
        wp_enqueue_style("wun-backend-css");

        wp_register_style("wun-frontend-css", plugins_url(WPDUN_DIR_NAME . "/assets/css/wun-frontend{$suffix}.css"), null, $this->versionDB);
        wp_enqueue_style("wun-frontend-css");

        wp_register_script("wun-jsc-js", plugins_url(WPDUN_DIR_NAME . "/assets/3rd-party/wun-jsc.js"), ["jquery"]);
        wp_enqueue_script("wun-jsc-js");

        wp_register_script("wun-backend-js", plugins_url(WPDUN_DIR_NAME . "/assets/js/wun-backend.js"), ["jquery"]);
        wp_localize_script("wun-backend-js", "wunBackendJsObj", $backendArgs);
        wp_enqueue_script("wun-backend-js");

        wp_register_script("wun-js-js", plugins_url(WPDUN_DIR_NAME . "/assets/js/wun-js.js"), ["jquery"]);
        wp_localize_script("wun-js-js", "wunJsObj", $args);
        wp_enqueue_script("wun-js-js");
    }

    public function frontendFiles() {
        $suffix = is_rtl() ? "-rtl" : "";
        $args = $this->getJsArgs();

        wp_register_style("wun-frontend-css", plugins_url(WPDUN_DIR_NAME . "/assets/css/wun-frontend{$suffix}.css"), null, $this->versionDB);
        wp_enqueue_style("wun-frontend-css");

        wp_register_script("wun-jsc-js", plugins_url(WPDUN_DIR_NAME . "/assets/3rd-party/wun-jsc.js"), ["jquery"]);
        wp_enqueue_script("wun-jsc-js");

        wp_register_script("wun-js-js", plugins_url(WPDUN_DIR_NAME . "/assets/js/wun-js.js"), ["jquery"]);
        wp_localize_script("wun-js-js", "wunJsObj", $args);
        wp_enqueue_script("wun-js-js");
    }

    private function getJsArgs() {
        $maxWdith = ($w = absint(apply_filters("wpdiscuz_un_centered_container_maxwidth", 600))) ? $w : 600;
        $sound = apply_filters("wpdiscuz_new_notification_sound", $this->options->data["soundUrl"]);

        if ($sound && is_string($sound)) {
            $info = pathinfo($sound);
            if (!empty($info["extension"]) && $info["extension"] !== "mp3") {
                $sound = "";
            }
        }
        
        $updateTimer = (int) apply_filters("wpdiscuz_un_update_timer", $this->options->data["updateTimer"]);

        return [
            "wunIsNotificationsActive" => (boolean) $this->options->isNotificationsActive(),
            "wunRestURL" => rest_url("wpdiscuz/v1/wunUpdate"),
            "wunAjaxUrl" => admin_url("admin-ajax.php"),
            "wunLoadMethod" => $this->options->data["loadMethod"],
            "wunLiveUpdate" => (boolean) $this->options->data["liveUpdate"],
            "wunUpdateTimer" => $updateTimer > 0 ? $updateTimer : $this->options->data["updateTimer"],
            "wunBrowserNotifications" => (boolean) $this->options->data["browserNotifications"],
            "wunRestNonce" => wp_create_nonce("wp_rest"),
            "wunUserIP" => WunHelper::getRealIPAddr(),
            "wunCookieHash" => defined("COOKIEHASH") ? COOKIEHASH : "not_defined",
            "wunCenteredContainerMaxWidth" => $maxWdith,
            "wunSoundUrl" => $sound,
            "wunPlaySoundWhen" => $this->options->data["playSoundWhen"],
            "wunContainerAnimationInMs" => $this->options->data["containerAnimationInMs"],
            "wunRequestTypeCheck" => self::REQUEST_TYPE_CHECK,
            "wunRequestTypeLoad" => self::REQUEST_TYPE_LOAD,
            "wunShowCountOfNotLoaded" => (boolean) $this->options->data["showCountOfNotLoaded"],            
            "wunSetReadOnLoad" => (boolean) $this->options->data["setReadOnLoad"],
            "wunUniqueNonce" => WunHelper::uniqueNonce(),
            "wunPhraseSetAsRead" => __("Mark as read", "wpdiscuz-user-notifications"),
            "wunPhraseSetAsUnread" => __("Mark as unread", "wpdiscuz-user-notifications"),
        ];
    }

}
