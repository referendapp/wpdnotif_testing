<?php

if (!defined("ABSPATH")) {
    exit();
}

class WunDBManager implements WunConstants {

    private $tblUsersNotifications;

    public function __construct() {
        global $wpdb;
        $this->tblUsersNotifications = $wpdb->prefix . "wc_users_notifications";
    }

    /**
     * create tables in db on activation if not exists
     */
    public function createTables($networkWide) {
        global $wpdb;
        if (is_multisite() && $networkWide) {
            $blogIds = $wpdb->get_col("SELECT `blog_id` FROM {$wpdb->blogs}");
            foreach ($blogIds as $blogId) {
                switch_to_blog($blogId);
                $this->_createTables();
                restore_current_blog();
            }
        } else {
            $this->_createTables();
        }
    }

    private function _createTables() {
        global $wpdb;
        require_once(ABSPATH . "wp-admin/includes/upgrade.php");
        $engine = version_compare($wpdb->db_version(), "5.5.0", ">=") ? "InnoDB" : "MyISAM";
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->tblUsersNotifications}` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `recipient_id` bigint(20) NOT NULL DEFAULT 0,
                `recipient_email` varchar(100) NOT NULL,
                `recipient_name` varchar(100) NOT NULL,
                `user_id` bigint(20) NOT NULL DEFAULT 0,
                `user_email` varchar(100) NOT NULL,
                `user_name` varchar(100) NOT NULL,
                `user_ip` varchar(100) NOT NULL,
                `item_id` bigint(20) NOT NULL DEFAULT 0,
                `secondary_item_id` bigint(20) NOT NULL DEFAULT 0,
                `component_name` varchar(100) NOT NULL,
                `component_action` varchar(100) NOT NULL,
                `action_date` datetime NOT NULL,
                `action_timestamp` bigint(20) NOT NULL,
                `is_new` tinyint(1) NOT NULL DEFAULT 0,
                `extras` text DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `recipient_id` (`user_id`),
                KEY `recipient_email` (`user_id`),
                KEY `user_id` (`user_id`),
                KEY `user_email` (`user_email`),
                KEY `user_ip` (`user_ip`),
                KEY `item_id` (`item_id`),
                KEY `secondary_item_id` (`secondary_item_id`),
                KEY `action_timestamp` (`action_timestamp`),
                KEY `is_new` (`is_new`)
              ) ENGINE=$engine DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";
        maybe_create_table($this->tblUsersNotifications, $sql);
    }

    /**
     * create tables on new blog
     */
    public function onNewBlog($blogId) {
        if (is_plugin_active_for_network(WPDUN_INDEX)) {
            switch_to_blog($blogId);
            $this->_createTables();
            restore_current_blog();
        }
    }

    /**
     * delete tables on blog delete
     */
    public function onDeleteBlog($tables) {
        $tables[] = $this->tblUsersNotifications;
        return $tables;
    }

    public function isNotificationExists($args) {
        global $wpdb;

        if (empty($args)) {
            return false;
        }

        $sql = "SELECT `id` FROM `{$this->tblUsersNotifications}` WHERE 1";
        $sql .= $this->whereId($args);
        $sql .= $this->whereRecipientIdOrEmail($args);
        $sql .= $this->whereUserIdOrEmailOrIp($args);
        $sql .= $this->whereItemId($args);
        $sql .= $this->whereSecondaryItemId($args);
        $sql .= $this->whereComponentName($args);
        $sql .= $this->whereComponentAction($args);
        $sql .= " LIMIT 1";
        return $wpdb->get_var($sql);
    }

    /**
     * removes a notification
     */
    public function deleteNotifications($args) {
        global $wpdb;

        if (empty($args)) {
            return false;
        }

        do_action("wpdiscuz_un_delete", $args);
        $sql = "DELETE FROM `{$this->tblUsersNotifications}` WHERE 1";
        $sql .= $this->whereId($args);
        $sql .= $this->whereRecipientIdOrEmail($args);
        $sql .= $this->whereUserIdOrEmailOrIp($args);
        $sql .= $this->whereItemId($args);
        $sql .= $this->whereSecondaryItemId($args);
        $sql .= $this->whereComponentName($args);
        $sql .= $this->whereComponentAction($args);
        $sql .= $this->whereIsNew($args);
        $result = $wpdb->query($sql);
        do_action("wpdiscuz_un_deleted", $args);
        return $result;
    }

    /**
     * adds a new notification
     */
    public function addNotification($data) {
        global $wpdb;

        do_action("wpdiscuz_un_add", $data);
        $sql = "INSERT INTO `{$this->tblUsersNotifications}` 
                (`id`, `recipient_id`, `recipient_email`, `recipient_name`, `user_id`, 
                 `user_email`, `user_name`, `user_ip`, `item_id`, `secondary_item_id`, 
                 `component_name`, `component_action`, `action_date`, `action_timestamp`, `is_new`, `extras`) VALUES
                (NULL, %d, %s, %s, %d, 
                 %s, %s, %s, %d, %d, 
                 %s, %s, %s, %d, %d, %s);";
        $sql = $wpdb->prepare($sql,
                $data["recipient_id"], $data["recipient_email"], $data["recipient_name"], $data["user_id"],
                $data["user_email"], $data["user_name"], $data["user_ip"], $data["item_id"], $data["secondary_item_id"],
                $data["component_name"], $data["component_action"], $data["action_date"], $data["action_timestamp"], $data["is_new"], $data["extras"]);
        $wpdb->query($sql);
        do_action("wpdiscuz_un_added", $data);
        return (int) $wpdb->insert_id;
    }

    /**
     * get notifications
     */
    public function getNotifications($args) {
        global $wpdb;

        $sql = "SELECT * FROM `{$this->tblUsersNotifications}`WHERE 1";
        $sql .= $this->whereRecipientIdOrEmail($args);
        $sql .= $this->whereComponentAction($args);
        $sql .= $this->whereLastXDays($args);
        $sql .= $this->whereIsNew($args);
        $sql .= $this->whereLastId($args);
        $sql .= $this->orderBy($args);
        $sql .= $this->limitClause($args);        
        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * get notifications count
     */
    public function getNotificationsCount($args) {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM `{$this->tblUsersNotifications}` WHERE 1";
        $sql .= $this->whereRecipientIdOrEmail($args);
        $sql .= $this->whereComponentAction($args);
        $sql .= $this->whereLastXDays($args);
        $sql .= $this->whereIsNew($args);
        $sql .= $this->whereLastId($args);
        $sql .= $this->limitClause($args);
        return (int) $wpdb->get_var($sql);
    }

    /**
     * get single notification
     */
    public function getNotification($args) {
        global $wpdb;

        if (empty($args)) {
            return false;
        }

        $sql = "SELECT * FROM `{$this->tblUsersNotifications}` WHERE 1";
        $sql .= $this->whereId($args);
        $sql .= $this->whereRecipientIdOrEmail($args);
        $sql .= $this->whereUserIdOrEmailOrIp($args);
        $sql .= $this->whereItemId($args);
        $sql .= $this->whereSecondaryItemId($args);
        $sql .= $this->whereComponentName($args);
        $sql .= $this->whereComponentAction($args);
        $sql .= " LIMIT 1";
        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * set notifications as read
     */
    public function setAsRead($args) {
        global $wpdb;

        if (empty($args)) {
            return false;
        }

        $sql = "UPDATE `{$this->tblUsersNotifications}` SET `is_new` = 0 WHERE 1";
        $sql .= $this->whereId($args);
        $sql .= $this->whereRecipientIdOrEmail($args);
        $sql .= $this->whereUserIdOrEmailOrIp($args);
        $sql .= $this->whereItemId($args);
        $sql .= $this->whereSecondaryItemId($args);
        $sql .= $this->whereComponentName($args);
        $sql .= $this->whereComponentAction($args);

        $result = $wpdb->query($sql);
        do_action("wpdiscuz_un_read", $args);
        return $result;
    }

	/**
	 * set notifications as read
	 */
	public function setAsUnread($args) {
		global $wpdb;

		if (empty($args)) {
			return false;
		}

		$sql = "UPDATE `{$this->tblUsersNotifications}` SET `is_new` = 1 WHERE 1";
		$sql .= $this->whereId($args);
		$sql .= $this->whereRecipientIdOrEmail($args);
		$sql .= $this->whereUserIdOrEmailOrIp($args);
		$sql .= $this->whereItemId($args);
		$sql .= $this->whereSecondaryItemId($args);
		$sql .= $this->whereComponentName($args);
		$sql .= $this->whereComponentAction($args);

		$result = $wpdb->query($sql);
		do_action("wpdiscuz_un_unread", $args);
		return $result;
	}
    
    public function getLastId($args = []) {
        global $wpdb;

        $sql = "SELECT `id` FROM `{$this->tblUsersNotifications}` WHERE `is_new` = 1";
        
        if (empty($args["order"])) {
            $sql .= " ORDER BY `id` DESC";
        } else {
            $sql .= " ORDER BY `id` ORDER " . esc_sql($args["order"]);
        }
        
        $sql .= " LIMIT 1";
        return $wpdb->get_var($sql);
    }

    private function whereId($args) {
        $sql = "";
        if (!empty($args["id"])) {
            if (is_array($args["id"])) {
                $idStr = WunHelper::arrayAsMySQLIn($args["id"]);
                if ($idStr) {
                    $sql .= " AND `id` IN (" . $idStr . ")";
                }
            } else {
                $sql .= " AND `id` = " . (int) $args["id"];
            }
        }
        return $sql;
    }

    private function whereRecipientIdOrEmail($args) {
        $sql = "";
        if (!empty($args["recipient_id"])) {
            $sql .= " AND `recipient_id` = " . (int) $args["recipient_id"];
        } else if (!empty($args["recipient_email"])) {
            $sql .= " AND `recipient_email` = '" . esc_sql($args["recipient_email"]) . "'";
        }
        return $sql;
    }

    private function whereUserIdOrEmailOrIp($args) {
        $sql = "";
        if (!empty($args["user_id"])) {
            $sql .= " AND `user_id` = " . (int) $args["user_id"];
        } else if (!empty($args["user_email"])) {
            $sql .= " AND `user_email` = '" . esc_sql($args["user_email"]) . "'";
        } else if (!empty($args["user_ip"])) {
            $sql .= " AND `user_ip` = '" . esc_sql($args["user_ip"]) . "'";
        }
        return $sql;
    }

    private function whereItemId($args) {
        $sql = "";
        if (!empty($args["item_id"])) {
            $sql .= " AND `item_id` = " . (int) $args["item_id"];
        }
        return $sql;
    }

    private function whereSecondaryItemId($args) {
        $sql = "";
        if (!empty($args["secondary_item_id"])) {
            $sql .= " AND `secondary_item_id` = " . (int) $args["secondary_item_id"];
        }
        return $sql;
    }

    private function whereComponentName($args) {
        $sql = "";
        if (!empty($args["component_name"])) {
            $sql .= " AND `component_name` = '" . esc_sql($args["component_name"]) . "'";
        }
        return $sql;
    }

    private function whereComponentAction($args) {
        $sql = "";
        if (!empty($args["component_action"])) {
            $actionStr = WunHelper::arrayAsMySQLIn($args["component_action"]);
            if ($actionStr) {
                $sql .= " AND `component_action` IN (" . $actionStr . ")";
            }
        }
        return $sql;
    }

    private function whereLastXDays($args) {
        $sql = "";
        if (!empty($args["lastXDays"])) {
            $currentTime = current_time("timestamp");
            $sql .= " AND `action_timestamp` > (" . $currentTime . " - (" . (int) $args["lastXDays"] . " * " . DAY_IN_SECONDS . "))";
        }
        return $sql;
    }
    
    private function whereIsNew($args) {
        $sql = "";
        if (isset($args["is_new"])) {
            $sql .= " AND `is_new` = " . (int) $args["is_new"];
        }
        return $sql;
    }
    
    private function whereLastId($args) {
        $sql = "";
        if (!empty($args["last_id"])) {
            $sql .= " AND `id` < " . (int) $args["last_id"];
        }
        return $sql;
    }

    private function orderBy($args) {
        $sql = " ORDER BY `id` DESC";
        return $sql;
    }

    private function limitClause($args) {
        $sql = "";
        if (!empty($args["limit"])) {
            $sql .= " LIMIT " . (int) $args["limit"];
        }
        return $sql;
    }

    /**
     * **********************************************************
     * **********************************************************
     * SETTINGS PAGE DELETE BUTTONS --START
     * ***********************************************************
     * ***********************************************************
     */
    public function deleteAllOrExpiredNotifications($expired = 0) {
        global $wpdb;

        if (empty($expired)) {
            $sql = "TRUNCATE `{$this->tblUsersNotifications}`;";
        } else {
            $currentTime = current_time("timestamp");
            $sql = "DELETE FROM `{$this->tblUsersNotifications}` 
                    WHERE `action_timestamp` < (" . $currentTime . " - (" . (int) $expired . " * " . DAY_IN_SECONDS . "))";
        }
        $wpdb->query($sql);
    }

    public function allNotificationsCount($args = []) {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM `{$this->tblUsersNotifications}` WHERE 1";
        if (!empty($args["lastXDays"])) {
            $currentTime = current_time("timestamp");
            $sql .= " AND `action_timestamp` < (" . $currentTime . " - (" . (int) $args["lastXDays"] . " * " . DAY_IN_SECONDS . "))";
        }
        $sql .= $this->whereIsNew($args);
        return (int) $wpdb->get_var($sql);
    }

    /**
     * **********************************************************
     * **********************************************************
     * SETTINGS PAGE DELETE BUTTONS --END
     * ***********************************************************
     * ***********************************************************
     */
}
