<?php

if (!defined("ABSPATH")) {
    exit();
}

class WunRoute implements WunConstants {

    private $dbManager;
    private $options;
    private $helper;

    public function __construct($dbManager, $options) {
        $this->dbManager = $dbManager;
        $this->options = $options;

        if ($this->options->isNotificationsActive()) {
            if ($this->options->data["loadMethod"] === "rest") {
                add_filter("wpdiscuz_rest_routes", [&$this, "notificationRoute"], 10, 2);
            } else {
                add_action("wp_ajax_wunUpdate", [$this, "ajaxGetNotifications"]);
                add_action("wp_ajax_nopriv_wunUpdate", [$this, "ajaxGetNotifications"]);
            }
        }
    }

    public function notificationRoute($routes, $namespace) {

        $routes["wun"] = [
            "namespace" => $namespace,
            "resource_name" => "wunUpdate",
            "data" => [
                [
                    "methods" => "GET",
                    "callback" => [&$this, "restGetNotifications"],
                    "permission_callback" => [&$this, "restHasPermission"],
                    "args" => [
                        "request_type" => [
                            "required" => true,
                            "type" => "string",
                            "validate_callback" => function ($param, $request, $key) {
                                return is_string($param) && in_array(sanitize_text_field($param), [self::REQUEST_TYPE_CHECK, self::REQUEST_TYPE_LOAD]);
                            }
                        ],
                        "last_id" => [
                            "required" => true,
                            "type" => "number",
                            "validate_callback" => function ($param, $request, $key) {
                                return ((int)$param) >= 0;
                            }
                        ],
                        "load_raw" => [
                            "required" => false,
                            "type" => "number",
                            "validate_callback" => function ($param, $request, $key) {
                                return ((int)$param);
                            }
                        ],
                        "nonce" => [
                            "required" => false,
                            "type" => "string",
                            "validate_callback" => function ($param, $request, $key) {
                                return is_string($param);
                            }
                        ],
                        "load_time" => [
                            "required" => false,
                            "type" => "number",
                            "validate_callback" => function ($param, $request, $key) {
                                return ((int)$param);
                            }
                        ],
                    ],
                ],
            ]
        ];
        return $routes;
    }

    public function restHasPermission() {
        return true;
    }

    public function restGetNotifications($data) {
        $start = WunHelper::getMicrotime();
        $restParams = $data->get_params();
        $params = ["start_time" => $start];

        $params["request_type"] = $restParams["request_type"];
        $params["last_id"] = absint($restParams["last_id"]);
        $params["email"] = defined("COOKIEHASH") ? filter_input(INPUT_COOKIE, "comment_author_email_" . COOKIEHASH, FILTER_SANITIZE_EMAIL) : "";
        $params["user"] = null;
        $params["load_raw"] = empty($restParams["load_raw"]) ? 0 : (int)$restParams["load_raw"];
        $params["nonce"] = empty($restParams["nonce"]) ? WunHelper::uniqueNonce() : sanitize_text_field($restParams["nonce"]);
        $params["load_time"] = empty($restParams["load_time"]) ? "" : (int)$restParams["load_time"];

        $currentUser = WunHelper::getCurrentUser();
        if (!empty($currentUser->ID)) {
            $params["email"] = $currentUser->user_email;
            $params["user"] = $currentUser;
        }

        $response = $this->getNotificationsData($params);
        return $response;
    }

    public function ajaxGetNotifications() {
        $start = WunHelper::getMicrotime();
        $params = ["start_time" => $start];

        $params["request_type"] = ($rType = filter_input(INPUT_POST, "request_type", FILTER_SANITIZE_STRING)) ? $rType : self::REQUEST_TYPE_CHECK;
        $params["last_id"] = ($v = absint(filter_input(INPUT_POST, "last_id", FILTER_SANITIZE_NUMBER_INT))) ? $v : 0;
        $params["email"] = defined("COOKIEHASH") ? filter_input(INPUT_COOKIE, "comment_author_email_" . COOKIEHASH, FILTER_SANITIZE_EMAIL) : "";
        $params["user"] = null;
        $params["load_raw"] = empty(($lPush = filter_input(INPUT_POST, "load_raw", FILTER_SANITIZE_NUMBER_INT))) ? 0 : (int)$lPush;
        $params["nonce"] = empty(($nonce = filter_input(INPUT_POST, "nonce", FILTER_SANITIZE_STRING))) ? WunHelper::uniqueNonce() : $nonce;
        $params["load_time"] = empty(($lTime = filter_input(INPUT_POST, "load_time", FILTER_SANITIZE_NUMBER_INT))) ? "" : (int)$lTime;

        $currentUser = WunHelper::getCurrentUser();
        if (!empty($currentUser->ID)) {
            $params["email"] = $currentUser->user_email;
            $params["user"] = $currentUser;
        }

        $response = $this->getNotificationsData($params);
        return wp_send_json($response);
    }

    public function getNotificationsData($params) {
        $data = [
            "itemsTotal" => 0,
            "itemsLoaded" => 0,
            "itemsLeft" => 0,
            "defaultIcon" => plugins_url(WPDUN_DIR_NAME . "/assets/img/chat-icon.png"),
            "itemsRaw" => null,
            "itemsHtml" => "",
            "lastId" => $params["last_id"],
        ];

        $actions = $this->options->getActions();

        $noNotification = "<dd class='wun-item'>";
        $noNotification .= "<div class='wun-no-notifications'>";
        $noNotification .= $this->options->data["ntfNoNotifications"];
        $noNotification .= "</div>";
        $noNotification .= "</dd>";

        if (empty($actions) || (empty($params["email"]) && empty($params["user"]->ID))) {
            $data["itemsHtml"] = $noNotification;
            return $data;
        }

        $dateFormat = get_option("date_format");
        $timeFormat = get_option("time_format");

        $args = [
            "recipient_id" => empty($params["user"]->ID) ? "" : (int)$params["user"]->ID,
            "recipient_email" => $params["email"],
            "lastXDays" => (int)$this->options->data["lastXDays"],
            "dateTimeFormat" => $dateFormat . " " . $timeFormat,
            "component_action" => $actions,
            "request_type" => $params["request_type"],
            "load_raw" => $params["load_raw"],
            "is_new" => 1,
        ];

        $data["itemsTotal"] = (int)$this->dbManager->getNotificationsCount($args);

        if (empty($data["itemsTotal"]) && empty($params["last_id"])) {
            $data["itemsHtml"] = $noNotification;
            return $data;
        }

        // if request type is CHECK and web push notification have been denied, return only items total count 
        if ($params["request_type"] === self::REQUEST_TYPE_CHECK && !$params["load_raw"]) {
            return $data;
        }

        $args["limit"] = (int)$this->options->data["perLoad"];
        $args["last_id"] = $params["last_id"];
        $items = $this->dbManager->getNotifications($args);

        if (!empty($items) && is_array($items)) {

            $data["itemsLoaded"] = count($items);

            // set last id = the last item id from loaded items
            $data["lastId"] = $args["last_id"] = (int)$items[count($items) - 1]["id"];
            $data["itemsLeft"] = (int)$this->dbManager->getNotificationsCount($args);

            $itemsToSetAsRead = [];

            foreach ($items as $item) {

                $itemData = $this->getData($item, $args);

                $data["itemsHtml"] .= $itemData["itemHtml"];

                if (!empty($itemData["itemRaw"])) {
                    $data["itemsRaw"][] = $itemData["itemRaw"];
                }

                if ($params["request_type"] === self::REQUEST_TYPE_LOAD && $this->options->data["setReadOnLoad"]) {
                    $itemsToSetAsRead[] = $item["id"];
                }
            }

            if (!empty($itemsToSetAsRead)) {
                $this->dbManager->setAsRead(["id" => $itemsToSetAsRead]);
                // decrease total items count if read
                $data["itemsTotal"] = $data["itemsLeft"];
            }
        }

        if (!empty($params["nonce"])) {
            $data["nonce"] = $params["nonce"];
        }

        if ($params["load_time"]) {
            $end = WunHelper::getMicrotime();

            $data["itemsHtml"] .= "<dd class='wun-item'>";
            $data["itemsHtml"] .= "<div class='wun-load-time'>";
            $data["itemsHtml"] .= "<strong style='font-size:15px;'>" . ($end - $params["start_time"]) . "</strong>";
            $data["itemsHtml"] .= "</div>";
            $data["itemsHtml"] .= "</dd>";
        }
        return $data;
    }

    private function getData($item, $args) {

        $data = ["itemHtml" => "", "itemRaw" => ""];
        $itemData = [];

        if ($item["component_action"] === self::ACTION_VOTE) {
            $itemData = $this->getMyCommentsVotesData($item, $args);
        } else if ($item["component_action"] === self::ACTION_FOLLOWER) {
            $itemData = $this->getMyFollowersData($item, $args);
        } else if ($item["component_action"] === self::ACTION_MY_POST_RATE) {
            $itemData = $this->getMyPostsRatesData($item, $args);
        } else if ($item["component_action"] === self::ACTION_MENTION) {
            $itemData = $this->getMentionsData($item, $args);
        } else if ($item["component_action"] === self::ACTION_MY_COMMENT_REPLY) {
            $itemData = $this->getMyCommentsRepliesData($item, $args);
        } else if ($item["component_action"] === self::ACTION_MY_POST_COMMENT) {
            $itemData = $this->getMyPostsCommentsData($item, $args);
        } else if ($item["component_action"] === self::ACTION_SUBSCRIBED_POST_COMMENT) {
            $itemData = $this->getSubscribedPostsCommentsData($item, $args);
        } else if ($item["component_action"] === self::ACTION_FOLLOWING_USER_COMMENT) {
            $itemData = $this->getFollowingUserCommentsData($item, $args);
        } else if ($item["component_action"] === self::ACTION_MY_COMMENT_APPROVE) {
            $itemData = $this->getApprovedCommentsData($item, $args);
        }

        if (!empty($itemData)) {
            if ($args["request_type"] === self::REQUEST_TYPE_LOAD) {
                $data["itemHtml"] = $this->getNotificationHtml($itemData);
            }

            if ($args["load_raw"]) {
                $data["itemRaw"] = $this->getRawNotification($itemData);
            }
        }
        return $data;
    }

    // my comments votes notifications
    private function getMyCommentsVotesData($item, $args) {

        if (empty($args["recipient_email"]) || !($url = get_comment_link($item["secondary_item_id"]))) {
            return "";
        }

        $notifier = empty($item["user_name"]) ? __("Someone", "wpdiscuz-user-notifications") : esc_html($item["user_name"]);
        $notifierAvatarImg = empty($item["user_email"]) ? "" : get_avatar($item["user_email"], 24, '', '', ['wpdiscuz_un' => 1]);
        $notifierAvatarUrl = empty($item["user_email"]) ? "" : get_avatar_url($item["user_email"], ['size' => 24, 'wpdiscuz_un' => 1]);
        $commentContent = $this->getCommentContent($item["secondary_item_id"]);
        $date = $this->getDatei18n($args, $item);

        $search = ["[NOTIFIER]", "[NOTIFIER_AVATAR_IMG]", "[NOTIFIER_AVATAR_URL]", "[COMMENT_URL]", "[COMMENT_CONTENT]", "[DATE]"];

        $replace = [$notifier, $notifierAvatarImg, $notifierAvatarUrl, $url, $commentContent, $date];

        if (((int)$item["extras"]) === 1) {
            $icon = "<svg class='wun-item-icon' viewBox='0 0 32 32' xmlns='http://www.w3.org/2000/svg'><path d='M4 21c0-0.547-0.453-1-1-1s-1 0.453-1 1 0.453 1 1 1 1-0.453 1-1zM22 12c0-1.062-0.953-2-2-2h-5.5c0-1.828 1.5-3.156 1.5-5 0-1.828-0.359-3-2.5-3-1 1.016-0.484 3.406-2 5-0.438 0.453-0.812 0.938-1.203 1.422-0.703 0.906-2.562 3.578-3.797 3.578h-0.5v10h0.5c0.875 0 2.312 0.562 3.156 0.859 1.719 0.594 3.5 1.141 5.344 1.141h1.891c1.766 0 3-0.703 3-2.609 0-0.297-0.031-0.594-0.078-0.875 0.656-0.359 1.016-1.25 1.016-1.969 0-0.375-0.094-0.75-0.281-1.078 0.531-0.5 0.828-1.125 0.828-1.859 0-0.5-0.219-1.234-0.547-1.609 0.734-0.016 1.172-1.422 1.172-2zM24 11.984c0 0.906-0.266 1.797-0.766 2.547 0.094 0.344 0.141 0.719 0.141 1.078 0 0.781-0.203 1.563-0.594 2.25 0.031 0.219 0.047 0.453 0.047 0.672 0 1-0.328 2-0.938 2.781 0.031 2.953-1.984 4.688-4.875 4.688h-2.016c-2.219 0-4.281-0.656-6.344-1.375-0.453-0.156-1.719-0.625-2.156-0.625h-4.5c-1.109 0-2-0.891-2-2v-10c0-1.109 0.891-2 2-2h4.281c0.609-0.406 1.672-1.813 2.141-2.422 0.531-0.688 1.078-1.359 1.672-2 0.938-1 0.438-3.469 2-5 0.375-0.359 0.875-0.578 1.406-0.578 1.625 0 3.187 0.578 3.953 2.094 0.484 0.953 0.547 1.859 0.547 2.906 0 1.094-0.281 2.031-0.75 3h2.75c2.156 0 4 1.828 4 3.984z'></path></svg>";
            $text = $this->options->data["ntfMessageCommentLike"];
            $title = $this->options->data["ntfTitleCommentLike"];
        } else {
            $icon = "<svg class='wun-item-icon' viewBox='0 0 32 32' xmlns='http://www.w3.org/2000/svg'><path d='M4 7c0-0.547-0.453-1-1-1s-1 0.453-1 1 0.453 1 1 1 1-0.453 1-1zM22 16c0-0.578-0.438-1.984-1.172-2 0.328-0.375 0.547-1.109 0.547-1.609 0-0.734-0.297-1.359-0.828-1.859 0.187-0.328 0.281-0.703 0.281-1.078 0-0.719-0.359-1.609-1.016-1.969 0.047-0.281 0.078-0.578 0.078-0.875 0-1.828-1.156-2.609-2.891-2.609h-2c-1.844 0-3.625 0.547-5.344 1.141-0.844 0.297-2.281 0.859-3.156 0.859h-0.5v10h0.5c1.234 0 3.094 2.672 3.797 3.578 0.391 0.484 0.766 0.969 1.203 1.422 1.516 1.594 1 3.984 2 5 2.141 0 2.5-1.172 2.5-3 0-1.844-1.5-3.172-1.5-5h5.5c1.047 0 2-0.938 2-2zM24 16.016c0 2.156-1.844 3.984-4 3.984h-2.75c0.469 0.969 0.75 1.906 0.75 3 0 1.031-0.063 1.969-0.547 2.906-0.766 1.516-2.328 2.094-3.953 2.094-0.531 0-1.031-0.219-1.406-0.578-1.563-1.531-1.078-4-2-5.016-0.594-0.625-1.141-1.297-1.672-1.984-0.469-0.609-1.531-2.016-2.141-2.422h-4.281c-1.109 0-2-0.891-2-2v-10c0-1.109 0.891-2 2-2h4.5c0.438 0 1.703-0.469 2.156-0.625 2.25-0.781 4.203-1.375 6.609-1.375h1.75c2.844 0 4.891 1.687 4.875 4.609v0.078c0.609 0.781 0.938 1.781 0.938 2.781 0 0.219-0.016 0.453-0.047 0.672 0.391 0.688 0.594 1.469 0.594 2.25 0 0.359-0.047 0.734-0.141 1.078 0.5 0.75 0.766 1.641 0.766 2.547z'></path></svg>";
            $text = $this->options->data["ntfMessageCommentDislike"];
            $title = $this->options->data["ntfTitleCommentDislike"];
        }


        $message = str_replace($search, $replace, $text);

        return [
            (int)$item["id"],
            "title" => $title,
            "message" => $message,
            "icon" => $icon,
            "iconWebPush" => "",
            "url" => $url,
            "tag" => $item["component_action"],
            "item" => $item,
        ];
    }

    // my new followers notifications
    private function getMyFollowersData($item, $args) {

        if (empty($args["recipient_id"])) {
            return "";
        }

        $notifier = empty($item["user_name"]) ? __("Someone", "wpdiscuz-user-notifications") : esc_html($item["user_name"]);
        $notifierAvatarImg = empty($item["user_email"]) ? "" : get_avatar($item["user_email"], 24, '', '', ['wpdiscuz_un' => 1]);
        $notifierAvatarUrl = empty($item["user_email"]) ? "" : get_avatar_url($item["user_email"], ['size' => 24, 'wpdiscuz_un' => 1]);
        $date = $this->getDatei18n($args, $item);

        $url = filter_input(INPUT_COOKIE, "wunCurrentURL", FILTER_SANITIZE_URL);

        $icon = "<svg class='wun-item-icon' viewBox='0 0 32 32' xmlns='http://www.w3.org/2000/svg'><path d='M6 21c0 1.656-1.344 3-3 3s-3-1.344-3-3 1.344-3 3-3 3 1.344 3 3zM14 22.922c0.016 0.281-0.078 0.547-0.266 0.75-0.187 0.219-0.453 0.328-0.734 0.328h-2.109c-0.516 0-0.938-0.391-0.984-0.906-0.453-4.766-4.234-8.547-9-9-0.516-0.047-0.906-0.469-0.906-0.984v-2.109c0-0.281 0.109-0.547 0.328-0.734 0.172-0.172 0.422-0.266 0.672-0.266h0.078c3.328 0.266 6.469 1.719 8.828 4.094 2.375 2.359 3.828 5.5 4.094 8.828zM22 22.953c0.016 0.266-0.078 0.531-0.281 0.734-0.187 0.203-0.438 0.313-0.719 0.313h-2.234c-0.531 0-0.969-0.406-1-0.938-0.516-9.078-7.75-16.312-16.828-16.844-0.531-0.031-0.938-0.469-0.938-0.984v-2.234c0-0.281 0.109-0.531 0.313-0.719 0.187-0.187 0.438-0.281 0.688-0.281h0.047c5.469 0.281 10.609 2.578 14.484 6.469 3.891 3.875 6.188 9.016 6.469 14.484z'></path></svg>";
        $search = ["[NOTIFIER]", "[NOTIFIER_AVATAR_IMG]", "[NOTIFIER_AVATAR_URL]", "[DATE]"];
        $replace = [$notifier, $notifierAvatarImg, $notifierAvatarUrl, $date];
        $text = $this->options->data["ntfMessageNewFollower"];

        $title = $this->options->data["ntfTitleNewFollower"];
        $message = str_replace($search, $replace, $text);

        return [
            (int)$item["id"],
            "title" => $title,
            "message" => $message,
            "icon" => $icon,
            "iconWebPush" => "",
            "url" => $url,
            "tag" => $item["component_action"],
            "item" => $item,
        ];
    }

// my posts rates notifications
    private function getMyPostsRatesData($item, $args) {

        if (empty($args["recipient_id"]) || !($url = WunHelper::getPermalink($item["item_id"]))) {
            return "";
        }

        $notifier = empty($item["user_name"]) ? __("Someone", "wpdiscuz-user-notifications") : esc_html($item["user_name"]);
        $notifierAvatarImg = empty($item["user_email"]) ? "" : get_avatar($item["user_email"], 24, '', '', ['wpdiscuz_un' => 1]);
        $notifierAvatarUrl = empty($item["user_email"]) ? "" : get_avatar_url($item["user_email"], ['size' => 24, 'wpdiscuz_un' => 1]);
        $postTitle = get_the_title($item["item_id"]);
        $date = $this->getDatei18n($args, $item);

        $icon = "<svg class='wun-item-icon' viewBox='0 0 32 32' xmlns='http://www.w3.org/2000/svg'><path d='M17.766 15.687l4.781-4.641-6.594-0.969-2.953-5.969-2.953 5.969-6.594 0.969 4.781 4.641-1.141 6.578 5.906-3.109 5.891 3.109zM26 10.109c0 0.281-0.203 0.547-0.406 0.75l-5.672 5.531 1.344 7.812c0.016 0.109 0.016 0.203 0.016 0.313 0 0.422-0.187 0.781-0.641 0.781-0.219 0-0.438-0.078-0.625-0.187l-7.016-3.687-7.016 3.687c-0.203 0.109-0.406 0.187-0.625 0.187-0.453 0-0.656-0.375-0.656-0.781 0-0.109 0.016-0.203 0.031-0.313l1.344-7.812-5.688-5.531c-0.187-0.203-0.391-0.469-0.391-0.75 0-0.469 0.484-0.656 0.875-0.719l7.844-1.141 3.516-7.109c0.141-0.297 0.406-0.641 0.766-0.641s0.625 0.344 0.766 0.641l3.516 7.109 7.844 1.141c0.375 0.063 0.875 0.25 0.875 0.719z'></path></svg>";
        $search = ["[NOTIFIER]", "[NOTIFIER_AVATAR_IMG]", "[NOTIFIER_AVATAR_URL]", "[POST_TITLE]", "[POST_URL]", "[RATING]", "[DATE]"];
        $replace = [$notifier, $notifierAvatarImg, $notifierAvatarUrl, $postTitle, $url, $item["extras"], $date];
        $text = $this->options->data["ntfMessageMyPostRate"];

        $title = $this->options->data["ntfTitleMyPostRate"];
        $message = str_replace($search, $replace, $text);

        return [
            (int)$item["id"],
            "title" => $title,
            "message" => $message,
            "icon" => $icon,
            "iconWebPush" => "",
            "url" => $url,
            "tag" => $item["component_action"],
            "item" => $item,
        ];
    }

// get mentions
    private function getMentionsData($item, $args) {

        if (empty($args["recipient_email"]) || !($url = get_comment_link($item["secondary_item_id"]))) {
            return "";
        }

        $notifier = empty($item["user_name"]) ? __("Someone", "wpdiscuz-user-notifications") : esc_html($item["user_name"]);
        $notifierAvatarImg = empty($item["user_email"]) ? "" : get_avatar($item["user_email"], 24, '', '', ['wpdiscuz_un' => 1]);
        $notifierAvatarUrl = empty($item["user_email"]) ? "" : get_avatar_url($item["user_email"], ['size' => 24, 'wpdiscuz_un' => 1]);
        $commentContent = $this->getCommentContent($item["secondary_item_id"]);
        $date = $this->getDatei18n($args, $item);

        $icon = "<svg class='wun-item-icon' viewBox='0 0 32 32' xmlns='http://www.w3.org/2000/svg'><path d='M15.188 12.109c0-2.25-1.172-3.594-3.141-3.594-2.594 0-5.375 2.578-5.375 6.75 0 2.328 1.156 3.656 3.187 3.656 3.141 0 5.328-3.594 5.328-6.813zM24 14c0 4.859-3.469 6.687-6.438 6.781-0.203 0-0.281 0.016-0.5 0.016-0.969 0-1.734-0.281-2.219-0.828-0.297-0.344-0.469-0.781-0.516-1.297-0.969 1.219-2.656 2.406-4.766 2.406-3.359 0-5.281-2.078-5.281-5.703 0-4.984 3.453-9.031 7.672-9.031 1.828 0 3.297 0.781 4.078 2.109l0.031-0.297 0.172-0.875c0.016-0.125 0.125-0.281 0.234-0.281h1.844c0.078 0 0.156 0.109 0.203 0.172 0.047 0.047 0.063 0.172 0.047 0.25l-1.875 9.594c-0.063 0.297-0.078 0.531-0.078 0.75 0 0.844 0.25 1.016 0.891 1.016 1.062-0.031 4.5-0.469 4.5-4.781 0-6.078-3.922-10-10-10-5.516 0-10 4.484-10 10s4.484 10 10 10c2.297 0 4.547-0.797 6.328-2.25 0.219-0.187 0.531-0.156 0.703 0.063l0.641 0.766c0.078 0.109 0.125 0.234 0.109 0.375-0.016 0.125-0.078 0.25-0.187 0.344-2.125 1.734-4.828 2.703-7.594 2.703-6.609 0-12-5.391-12-12s5.391-12 12-12c7.172 0 12 4.828 12 12z'></path></svg>";
        $search = ["[NOTIFIER]", "[NOTIFIER_AVATAR_IMG]", "[NOTIFIER_AVATAR_URL]", "[COMMENT_URL]", "[COMMENT_CONTENT]", "[DATE]"];
        $replace = [$notifier, $notifierAvatarImg, $notifierAvatarUrl, $url, $commentContent, $date];
        $text = $this->options->data["ntfMessageMention"];

        $title = $this->options->data["ntfTitleMention"];
        $message = str_replace($search, $replace, $text);

        return [
            (int)$item["id"],
            "title" => $title,
            "message" => $message,
            "icon" => $icon,
            "iconWebPush" => "",
            "url" => $url,
            "tag" => $item["component_action"],
            "item" => $item,
        ];
    }

// get my comments replies
    private function getMyCommentsRepliesData($item, $args) {

        if (empty($args["recipient_email"]) || !($url = get_comment_link($item["secondary_item_id"]))) {
            return "";
        }

        $notifier = empty($item["user_name"]) ? __("Someone", "wpdiscuz-user-notifications") : esc_html($item["user_name"]);
        $notifierAvatarImg = empty($item["user_email"]) ? "" : get_avatar($item["user_email"], 24, '', '', ['wpdiscuz_un' => 1]);
        $notifierAvatarUrl = empty($item["user_email"]) ? "" : get_avatar_url($item["user_email"], ['size' => 24, 'wpdiscuz_un' => 1]);
        $commentContent = $this->getCommentContent($item["secondary_item_id"]);
        $date = $this->getDatei18n($args, $item);

        $icon = "<svg class='wun-item-icon' viewBox='0 0 32 32' xmlns='http://www.w3.org/2000/svg'><path d='M28 17.5c0 2.188-1.094 5.047-1.984 7.047-0.172 0.359-0.344 0.859-0.578 1.188-0.109 0.156-0.219 0.266-0.438 0.266-0.313 0-0.5-0.25-0.5-0.547 0-0.25 0.063-0.531 0.078-0.781 0.047-0.641 0.078-1.281 0.078-1.922 0-7.453-4.422-8.75-11.156-8.75h-3.5v4c0 0.547-0.453 1-1 1-0.266 0-0.516-0.109-0.703-0.297l-8-8c-0.187-0.187-0.297-0.438-0.297-0.703s0.109-0.516 0.297-0.703l8-8c0.187-0.187 0.438-0.297 0.703-0.297 0.547 0 1 0.453 1 1v4h3.5c5.125 0 11.5 0.906 13.672 6.297 0.656 1.656 0.828 3.453 0.828 5.203z'></path></svg>";
        $search = ["[NOTIFIER]", "[NOTIFIER_AVATAR_IMG]", "[NOTIFIER_AVATAR_URL]", "[COMMENT_URL]", "[COMMENT_CONTENT]", "[DATE]"];
        $replace = [$notifier, $notifierAvatarImg, $notifierAvatarUrl, $url, $commentContent, $date];
        $text = $this->options->data["ntfMessageMyCommentReply"];

        $title = $this->options->data["ntfTitleMyCommentReply"];
        $message = str_replace($search, $replace, $text);

        return [
            (int)$item["id"],
            "title" => $title,
            "message" => $message,
            "icon" => $icon,
            "iconWebPush" => "",
            "url" => $url,
            "tag" => $item["component_action"],
            "item" => $item,
        ];
    }

// get my posts comments
    private function getMyPostsCommentsData($item, $args) {

        if (empty($args["recipient_id"]) || !($url = get_comment_link($item["secondary_item_id"]))) {
            return "";
        }

        $notifier = empty($item["user_name"]) ? __("Someone", "wpdiscuz-user-notifications") : esc_html($item["user_name"]);
        $notifierAvatarImg = empty($item["user_email"]) ? "" : get_avatar($item["user_email"], 24, '', '', ['wpdiscuz_un' => 1]);
        $notifierAvatarUrl = empty($item["user_email"]) ? "" : get_avatar_url($item["user_email"], ['size' => 24, 'wpdiscuz_un' => 1]);
        $commentContent = $this->getCommentContent($item["secondary_item_id"]);
        $date = $this->getDatei18n($args, $item);
        $postTitle = get_the_title($item["item_id"]);
        $postUrl = WunHelper::getPermalink($item["item_id"]);

        $icon = "<svg class='wun-item-icon' viewBox='0 0 32 32' xmlns='http://www.w3.org/2000/svg'><path d='M10 14c0 1.109-0.891 2-2 2s-2-0.891-2-2 0.891-2 2-2 2 0.891 2 2zM16 14c0 1.109-0.891 2-2 2s-2-0.891-2-2 0.891-2 2-2 2 0.891 2 2zM22 14c0 1.109-0.891 2-2 2s-2-0.891-2-2 0.891-2 2-2 2 0.891 2 2zM14 6c-6.5 0-12 3.656-12 8 0 2.328 1.563 4.547 4.266 6.078l1.359 0.781-0.422 1.5c-0.297 1.109-0.688 1.969-1.094 2.688 1.578-0.656 3.016-1.547 4.297-2.672l0.672-0.594 0.891 0.094c0.672 0.078 1.359 0.125 2.031 0.125 6.5 0 12-3.656 12-8s-5.5-8-12-8zM28 14c0 5.531-6.266 10-14 10-0.766 0-1.531-0.047-2.266-0.125-2.047 1.813-4.484 3.094-7.187 3.781-0.562 0.156-1.172 0.266-1.781 0.344h-0.078c-0.313 0-0.594-0.25-0.672-0.594v-0.016c-0.078-0.391 0.187-0.625 0.422-0.906 0.984-1.109 2.109-2.047 2.844-4.656-3.219-1.828-5.281-4.656-5.281-7.828 0-5.516 6.266-10 14-10v0c7.734 0 14 4.484 14 10z'></path></svg>";
        $search = ["[NOTIFIER]", "[NOTIFIER_AVATAR_IMG]", "[NOTIFIER_AVATAR_URL]", "[COMMENT_URL]", "[COMMENT_CONTENT]", "[DATE]", "[POST_TITLE]", "[POST_URL]"];
        $replace = [$notifier, $notifierAvatarImg, $notifierAvatarUrl, $url, $commentContent, $date, $postTitle, $postUrl];
        $text = $this->options->data["ntfMessageMyPostComment"];

        $title = $this->options->data["ntfTitleMyPostComment"];
        $message = str_replace($search, $replace, $text);

        return [
            (int)$item["id"],
            "title" => $title,
            "message" => $message,
            "icon" => $icon,
            "iconWebPush" => "",
            "url" => $url,
            "tag" => $item["component_action"],
            "item" => $item,
        ];
    }

// get subscribed posts comments
    private function getSubscribedPostsCommentsData($item, $args) {

        if (empty($args["recipient_email"]) || !($url = get_comment_link($item["secondary_item_id"]))) {
            return "";
        }

        $notifier = empty($item["user_name"]) ? __("Someone", "wpdiscuz-user-notifications") : esc_html($item["user_name"]);
        $notifierAvatarImg = empty($item["user_email"]) ? "" : get_avatar($item["user_email"], 24, '', '', ['wpdiscuz_un' => 1]);
        $notifierAvatarUrl = empty($item["user_email"]) ? "" : get_avatar_url($item["user_email"], ['size' => 24, 'wpdiscuz_un' => 1]);
        $commentContent = $this->getCommentContent($item["secondary_item_id"]);
        $date = $this->getDatei18n($args, $item);
        $postTitle = get_the_title($item["item_id"]);
        $postUrl = WunHelper::getPermalink($item["item_id"]);

        $icon = "<svg class='wun-item-icon' viewBox='0 0 22 28'><path d='M6 21c0 1.656-1.344 3-3 3s-3-1.344-3-3 1.344-3 3-3 3 1.344 3 3zM14 22.922c0.016 0.281-0.078 0.547-0.266 0.75-0.187 0.219-0.453 0.328-0.734 0.328h-2.109c-0.516 0-0.938-0.391-0.984-0.906-0.453-4.766-4.234-8.547-9-9-0.516-0.047-0.906-0.469-0.906-0.984v-2.109c0-0.281 0.109-0.547 0.328-0.734 0.172-0.172 0.422-0.266 0.672-0.266h0.078c3.328 0.266 6.469 1.719 8.828 4.094 2.375 2.359 3.828 5.5 4.094 8.828zM22 22.953c0.016 0.266-0.078 0.531-0.281 0.734-0.187 0.203-0.438 0.313-0.719 0.313h-2.234c-0.531 0-0.969-0.406-1-0.938-0.516-9.078-7.75-16.312-16.828-16.844-0.531-0.031-0.938-0.469-0.938-0.984v-2.234c0-0.281 0.109-0.531 0.313-0.719 0.187-0.187 0.438-0.281 0.688-0.281h0.047c5.469 0.281 10.609 2.578 14.484 6.469 3.891 3.875 6.188 9.016 6.469 14.484z'></path></svg>";
        $search = ["[NOTIFIER]", "[NOTIFIER_AVATAR_IMG]", "[NOTIFIER_AVATAR_URL]", "[COMMENT_URL]", "[COMMENT_CONTENT]", "[DATE]", "[POST_TITLE]", "[POST_URL]"];
        $replace = [$notifier, $notifierAvatarImg, $notifierAvatarUrl, $url, $commentContent, $date, $postTitle, $postUrl];
        $text = $this->options->data["ntfMessageSubscribedPostComment"];

        $title = $this->options->data["ntfTitleSubscribedPostComment"];
        $message = str_replace($search, $replace, $text);

        return [
            (int)$item["id"],
            "title" => $title,
            "message" => $message,
            "icon" => $icon,
            "iconWebPush" => "",
            "url" => $url,
            "tag" => $item["component_action"],
            "item" => $item,
        ];
    }

// get following user comments
    private function getFollowingUserCommentsData($item, $args) {

        if (empty($args["recipient_id"]) || !($url = get_comment_link($item["secondary_item_id"]))) {
            return "";
        }

        $notifier = empty($item["user_name"]) ? __("Someone", "wpdiscuz-user-notifications") : esc_html($item["user_name"]);
        $notifierAvatarImg = empty($item["user_email"]) ? "" : get_avatar($item["user_email"], 24, '', '', ['wpdiscuz_un' => 1]);
        $notifierAvatarUrl = empty($item["user_email"]) ? "" : get_avatar_url($item["user_email"], ['size' => 24, 'wpdiscuz_un' => 1]);
        $commentContent = $this->getCommentContent($item["secondary_item_id"]);
        $date = $this->getDatei18n($args, $item);
        $postTitle = get_the_title($item["item_id"]);
        $postUrl = WunHelper::getPermalink($item["item_id"]);

        $icon = "<svg class='wun-item-icon' viewBox='0 0 22 28'><path d='M6 21c0 1.656-1.344 3-3 3s-3-1.344-3-3 1.344-3 3-3 3 1.344 3 3zM14 22.922c0.016 0.281-0.078 0.547-0.266 0.75-0.187 0.219-0.453 0.328-0.734 0.328h-2.109c-0.516 0-0.938-0.391-0.984-0.906-0.453-4.766-4.234-8.547-9-9-0.516-0.047-0.906-0.469-0.906-0.984v-2.109c0-0.281 0.109-0.547 0.328-0.734 0.172-0.172 0.422-0.266 0.672-0.266h0.078c3.328 0.266 6.469 1.719 8.828 4.094 2.375 2.359 3.828 5.5 4.094 8.828zM22 22.953c0.016 0.266-0.078 0.531-0.281 0.734-0.187 0.203-0.438 0.313-0.719 0.313h-2.234c-0.531 0-0.969-0.406-1-0.938-0.516-9.078-7.75-16.312-16.828-16.844-0.531-0.031-0.938-0.469-0.938-0.984v-2.234c0-0.281 0.109-0.531 0.313-0.719 0.187-0.187 0.438-0.281 0.688-0.281h0.047c5.469 0.281 10.609 2.578 14.484 6.469 3.891 3.875 6.188 9.016 6.469 14.484z'></path></svg>";
        $search = ["[NOTIFIER]", "[NOTIFIER_AVATAR_IMG]", "[NOTIFIER_AVATAR_URL]", "[COMMENT_URL]", "[COMMENT_CONTENT]", "[DATE]", "[POST_TITLE]", "[POST_URL]"];
        $replace = [$notifier, $notifierAvatarImg, $notifierAvatarUrl, $url, $commentContent, $date, $postTitle, $postUrl];
        $text = $this->options->data["ntfMessageFollowingUserComment"];

        $title = $this->options->data["ntfTitleFollowingUserComment"];
        $message = str_replace($search, $replace, $text);

        return [
            (int)$item["id"],
            "title" => $title,
            "message" => $message,
            "icon" => $icon,
            "iconWebPush" => "",
            "url" => $url,
            "tag" => $item["component_action"],
            "item" => $item,
        ];
    }

// get approved comments
    private function getApprovedCommentsData($item, $args) {

        if (empty($args["recipient_email"]) || !($url = get_comment_link($item["secondary_item_id"]))) {
            return "";
        }

        $commentContent = $this->getCommentContent($item["secondary_item_id"]);
        $date = $this->getDatei18n($args, $item);

        $icon = "<svg class='wun-item-icon' viewBox='0 0 26 28'><path d='M22 14.531v4.969c0 2.484-2.016 4.5-4.5 4.5h-13c-2.484 0-4.5-2.016-4.5-4.5v-13c0-2.484 2.016-4.5 4.5-4.5h13c0.625 0 1.25 0.125 1.828 0.391 0.141 0.063 0.25 0.203 0.281 0.359 0.031 0.172-0.016 0.328-0.141 0.453l-0.766 0.766c-0.094 0.094-0.234 0.156-0.359 0.156-0.047 0-0.094-0.016-0.141-0.031-0.234-0.063-0.469-0.094-0.703-0.094h-13c-1.375 0-2.5 1.125-2.5 2.5v13c0 1.375 1.125 2.5 2.5 2.5h13c1.375 0 2.5-1.125 2.5-2.5v-3.969c0-0.125 0.047-0.25 0.141-0.344l1-1c0.109-0.109 0.234-0.156 0.359-0.156 0.063 0 0.125 0.016 0.187 0.047 0.187 0.078 0.313 0.25 0.313 0.453zM25.609 6.891l-12.719 12.719c-0.5 0.5-1.281 0.5-1.781 0l-6.719-6.719c-0.5-0.5-0.5-1.281 0-1.781l1.719-1.719c0.5-0.5 1.281-0.5 1.781 0l4.109 4.109 10.109-10.109c0.5-0.5 1.281-0.5 1.781 0l1.719 1.719c0.5 0.5 0.5 1.281 0 1.781z'></path></svg>";
        $search = ["[COMMENT_URL]", "[COMMENT_CONTENT]", "[DATE]"];
        $replace = [$url, $commentContent, $date];
        $text = $this->options->data["ntfMessageMyCommentApprove"];

        $title = $this->options->data["ntfTitleMyCommentApprove"];
        $message = str_replace($search, $replace, $text);

        return [
            (int)$item["id"],
            "title" => $title,
            "message" => $message,
            "icon" => $icon,
            "iconWebPush" => "",
            "url" => $url,
            "tag" => $item["component_action"],
            "item" => $item,
        ];
    }

    private function getNotificationHtml($data) {

        if (empty($data["message"]) || empty($data["icon"]) || empty($data["url"]) || empty($data["item"])) {
            return "";
        }

        $readUrl = $this->createReadUrl($data["url"], $data["item"]);
        $message = str_replace("[MARK_READ_URL]", $readUrl, $data["message"]);
        $message = preg_replace("#(href=[\'\"][^\'\"]+action=wun_read[^\'\"]+[\'\"])#", '$1 class="wun-mark-read"', $message);

        $html = "<dd class='wun-item' data-wunid='{$data["item"]["id"]}'>";
        $html .= "<div class='wun-nleft'>";
        $html .= $data["icon"];
        $html .= "</div>";
        $html .= "<div class='wun-nright'>";
        $html .= $message;
        $html .= "</div>";
        $html .= "</dd>";
        return apply_filters("wpdiscuz_un_item_html", $html, $data["item"]);
    }

    private function getRawNotification($data) {

        if (empty($data["title"]) || empty($data["message"]) || empty($data["url"]) || empty($data["item"])) {
            return null;
        }

        $itemData = [
            "id" => (int)$data["item"]["id"],
            "title" => sanitize_text_field($data["title"]),
            "message" => sanitize_text_field(preg_replace("#<!\-\-wundelete\-\->[\s\t\r\n]*.*?[\s\t\r\n]*<!\-\-/wundelete\-\->#isu", "", $data["message"])),
            "icon" => sanitize_text_field($data["iconWebPush"]),
            "url" => $this->createReadUrl($data["url"], $data["item"]),
            "tag" => $data["item"]["component_action"],
        ];
        $itemRaw = apply_filters("wpdiscuz_un_item_raw", $itemData, $data["item"]);
        return $itemRaw;
    }

    private function createReadUrl($url, $item) {
        if (empty($url) || empty($item["id"])) {
            return $url;
        }

        $id = (int)$item["id"];
        $nonce = wp_create_nonce(md5(ABSPATH . $id));

        $encodedUrl = urlencode_deep($url);
        $parsedUrl = parse_url($url);
        $newUrl = $parsedUrl["scheme"] . "://" . $parsedUrl ["host"] . $parsedUrl ["path"];

        if (empty($parsedUrl["query"])) {
            $newUrl .= "?action=" . self::ACTION_MARK_READ . "&redirect_to={$encodedUrl}&_nonce={$nonce}&id={$id}";
        } else {
            $newUrl .= "?" . $parsedUrl ["query"] . "&action=" . self::ACTION_MARK_READ . "&redirect_to={$encodedUrl}&_nonce={$nonce}&id={$id}";
        }

        return $newUrl;
    }

    private function getCommentContent($id) {
        remove_filter("get_comment_excerpt", "get_comment_excerpt");
        $commentContent = get_comment_excerpt($id);
        add_filter("get_comment_excerpt", "get_comment_excerpt");
        return $commentContent;
    }

    private function getDatei18n($args, $item) {
        return date_i18n($args["dateTimeFormat"], $item["action_timestamp"]);
    }

}
