<?php

if ( ! defined( "ABSPATH" ) ) {
	exit();
}

class WunHelperActions implements WunConstants {

	/**
	 * @var $dbManager WunDBManager
	 */
	private $dbManager;
	/**
	 * @var $options WunOptions
	 */
	private $options;
	private $subscriptions;
	private $follows;

	public function __construct( $dbManager, $options ) {
		$this->dbManager = $dbManager;
		$this->options   = $options;

		add_action( "wpdiscuz_add_vote", [ $this, "addVoteNotification" ], 10, 2 );
		add_action( "wpdiscuz_update_vote", [ $this, "updateVoteNotification" ], 10, 3 );
		add_action( "wpdiscuz_remove_vote_data", [ $this, "deleteVoteNotifications" ], 10 );

		add_action( "wpdiscuz_follow_added", [ $this, "addFollowNotification" ], 10 );
		add_action( "wpdiscuz_follow_cancelled", [ $this, "updateFollowNotification" ], 10 );

		add_action( "wpdiscuz_add_rating", [ $this, "addRateNotification" ], 10, 2 );

		add_action( "comment_post", [ $this, "addGroupedNotification" ], 10, 3 );

		add_action( "wp_ajax_wunAddSubscriptionsNotifications", [ $this, "addSubscriptionsNotifications" ] );
		add_action( "wp_ajax_nopriv_wunAddSubscriptionsNotifications", [ &$this, "addSubscriptionsNotifications" ] );

		add_action( "wp_ajax_wunAddFollowsNotifications", [ $this, "addFollowsNotifications" ] );
		add_action( "wp_ajax_nopriv_wunAddFollowsNotifications", [ &$this, "addFollowsNotifications" ] );

		add_action( "transition_comment_status", [ &$this, "addApprovedNotification" ], 10, 3 );

		// todo
		add_action( "deleted_user", [ $this, "deleteUserRelatedNotifications" ], 10, 3 );
		add_action( "deleted_post", [ $this, "deletePostRelatedNotifications" ], 10, 2 );
		add_action( "transition_post_status", [ $this, "postStatusChanged" ], 10, 3 );
		add_action( "deleted_comment", [ $this, "deleteCommentRelatedNotifications" ], 10, 2 );

		add_action( "wp_ajax_wunDeleteAllNotifications", [ $this, "deleteAllNotifications" ] );
		add_action( "wp_ajax_nopriv_wunDeleteAllNotifications", [ &$this, "deleteAllNotifications" ] );

//        add_action("admin_post_" . self::ACTION_MARK_READ, [&$this, "markRead"]);
		add_action( "wp_loaded", [ &$this, "markRead" ] );

		add_action( "wp_ajax_wunUpdateStatusAjax", [ $this, "updateStatusAjax" ] );
	}

	/* === VOTES START === */

	public function addVoteNotification( $voteType, $comment ) {
		if ( empty( $this->options->data["notifications"]["myCommentVote"] ) ) {
			return;
		}

		$voteType = (int) $voteType;

		if ( $voteType === 0 ) {
			return;
		}

		$currentUser               = WunHelper::getNotifyer();
		$data                      = [];
		$data["recipient_id"]      = (int) $comment->user_id;
		$data["recipient_email"]   = sanitize_text_field( $comment->comment_author_email );
		$data["recipient_name"]    = sanitize_text_field( $comment->comment_author );
		$data["user_id"]           = (int) $currentUser["user_id"];
		$data["user_email"]        = sanitize_text_field( $currentUser["user_email"] );
		$data["user_name"]         = sanitize_text_field( $currentUser["user_name"] );
		$data["user_ip"]           = WunHelper::getRealIPAddr();
		$data["item_id"]           = (int) $comment->comment_post_ID;
		$data["secondary_item_id"] = (int) $comment->comment_ID;
		$data["component_name"]    = "wpdiscuz";
		$data["component_action"]  = self::ACTION_VOTE;
		$data["action_date"]       = current_time( "mysql" );
		$data["action_timestamp"]  = current_time( "timestamp" );
		$data["is_new"]            = 1;
		$data["extras"]            = (int) $voteType;

		if ( $data["recipient_email"] === $data["user_email"] || $this->dbManager->isNotificationExists( $data ) ) {
			return;
		}

		$this->dbManager->addNotification( $data );
	}

	public function updateVoteNotification( $voteType, $isUserVoted, $comment ) {

		$voteType    = (int) $voteType;
		$isUserVoted = (int) $isUserVoted;

		$currentUser               = WunHelper::getNotifyer();
		$data                      = [];
		$data["recipient_id"]      = (int) $comment->user_id;
		$data["recipient_email"]   = sanitize_text_field( $comment->comment_author_email );
		$data["user_id"]           = (int) $currentUser["user_id"];
		$data["user_email"]        = sanitize_text_field( $currentUser["user_email"] );
		$data["user_ip"]           = WunHelper::getRealIPAddr();
		$data["item_id"]           = (int) $comment->comment_post_ID;
		$data["secondary_item_id"] = (int) $comment->comment_ID;
		$data["component_name"]    = "wpdiscuz";
		$data["component_action"]  = self::ACTION_VOTE;

		$voteResult = $voteType + $isUserVoted;
		if ( $voteResult === 0 ) {
			$this->dbManager->deleteNotifications( $data );
		}

		$this->addVoteNotification( $voteResult, $comment );
	}

	public function deleteVoteNotifications() {
		$this->dbManager->deleteNotifications( [ "component_action" => self::ACTION_VOTE ] );
	}

	/* === VOTES END === */

	/* === FOLLOWS START === */

	public function addFollowNotification( $args ) {

		if ( empty( $this->options->data["notifications"]["newFollower"] ) ) {
			return;
		}

		if ( empty( $args["confirm"] ) ) {
			return;
		}

		$data                      = [];
		$data["recipient_id"]      = (int) $args["user_id"];
		$data["recipient_email"]   = sanitize_text_field( $args["user_email"] );
		$data["recipient_name"]    = sanitize_text_field( $args["user_name"] );
		$data["user_id"]           = (int) $args["follower_id"];
		$data["user_email"]        = sanitize_text_field( $args["follower_email"] );
		$data["user_name"]         = sanitize_text_field( $args["follower_name"] );
		$data["user_ip"]           = WunHelper::getRealIPAddr();
		$data["item_id"]           = (int) $args["post_id"];
		$data["secondary_item_id"] = 0;
		$data["component_name"]    = "wpdiscuz";
		$data["component_action"]  = self::ACTION_FOLLOWER;
		$data["action_date"]       = current_time( "mysql" );
		$data["action_timestamp"]  = current_time( "timestamp" );
		$data["is_new"]            = 1;
		$data["extras"]            = "";

		if ( $data["recipient_email"] === $data["user_email"] || $this->dbManager->isNotificationExists( $data ) ) {
			return;
		}

		$this->dbManager->addNotification( $data );
	}

	public function updateFollowNotification( $args ) {
		$data                      = [];
		$data["recipient_id"]      = (int) $args["user_id"];
		$data["recipient_email"]   = sanitize_text_field( $args["user_email"] );
		$data["user_id"]           = (int) $args["follower_id"];
		$data["user_email"]        = sanitize_text_field( $args["follower_email"] );
		$data["user_ip"]           = WunHelper::getRealIPAddr();
		$data["item_id"]           = (int) $args["post_id"];
		$data["secondary_item_id"] = 0;
		$data["component_name"]    = "wpdiscuz";
		$data["component_action"]  = self::ACTION_FOLLOWER;
		$this->dbManager->deleteNotifications( $data );
	}

	/* === FOLLOWS END === */

	/* === RATES START === */

	public function addRateNotification( $rating, $postId ) {

		if ( empty( $this->options->data["notifications"]["myPostRate"] ) ) {
			return;
		}

		$post = get_post( $postId );

		if ( empty( $post ) ) {
			return;
		}

		$recipient = get_user_by( "id", $post->post_author );

		if ( empty( $recipient ) ) {
			return;
		}

		$currentUser               = WunHelper::getNotifyer();
		$data                      = [];
		$data["recipient_id"]      = (int) $recipient->ID;
		$data["recipient_email"]   = sanitize_text_field( $recipient->user_email );
		$data["recipient_name"]    = sanitize_text_field( $recipient->display_name );
		$data["user_id"]           = (int) $currentUser["user_id"];
		$data["user_email"]        = sanitize_text_field( $currentUser["user_email"] );
		$data["user_name"]         = sanitize_text_field( $currentUser["user_name"] );
		$data["user_ip"]           = WunHelper::getRealIPAddr();
		$data["item_id"]           = (int) $postId;
		$data["secondary_item_id"] = 0;
		$data["component_name"]    = "wpdiscuz";
		$data["component_action"]  = self::ACTION_MY_POST_RATE;
		$data["action_date"]       = current_time( "mysql" );
		$data["action_timestamp"]  = current_time( "timestamp" );
		$data["is_new"]            = 1;
		$data["extras"]            = (int) $rating;

		if ( $data["recipient_email"] === $data["user_email"] || $this->dbManager->isNotificationExists( $data ) ) {
			return;
		}

		$this->dbManager->addNotification( $data );
	}

	/* === RATES END === */

	/* === GROUPED START === */

	public function addGroupedNotification( $comment, $commentApproved, $commentData ) {

		if ( is_numeric( $comment ) ) {
			$comment = get_comment( $comment );
		}

		if ( ( (int) $comment->comment_approved ) !== 1 ) {
			return;
		}

		$data                      = [];
		$data["user_id"]           = (int) $comment->user_id;
		$data["user_email"]        = sanitize_text_field( $comment->comment_author_email );
		$data["user_name"]         = sanitize_text_field( $comment->comment_author );
		$data["user_ip"]           = WunHelper::getRealIPAddr();
		$data["item_id"]           = (int) $comment->comment_post_ID;
		$data["secondary_item_id"] = (int) $comment->comment_ID;
		$data["component_name"]    = "wpdiscuz";
		$data["component_action"]  = self::ACTION_MENTION;
		$data["action_date"]       = current_time( "mysql" );
		$data["action_timestamp"]  = current_time( "timestamp" );
		$data["is_new"]            = 1;
		$data["extras"]            = "";

		// mention
		$this->addMentionNotification( $comment, $data );

		// my comment reply
		$this->addMyCommentReplyNotification( $comment, $data );

		// my post comment
		$this->addMyPostCommentNotification( $comment, $data );

		// subscribed post comment
		$this->addSubscribedPostCommentsNotification( $comment, $data );

		// following user comment
		$this->addFollowingUserCommentsNotification( $comment, $data );
	}

	/* === GROUPED END === */

	/* === MENTIONS START === */

	public function addMentionNotification( $comment, $data ) {

		if ( empty( $this->options->data["notifications"]["mention"] ) ) {
			return;
		}

		$wpDiscuz = wpDiscuz();

		if ( ! $wpDiscuz->options->subscription["enableUserMentioning"] ) {
			return;
		}

		$users = $wpDiscuz->helper->getMentionedUsers( $comment->comment_content );

		if ( empty( $users ) || ! is_array( $users ) ) {
			return;
		}

		if ( empty( $data ) ) {
			$data = WunHelper::getNotificationDataFromComment( $comment, self::ACTION_MENTION );
		}

		foreach ( $users as $user ) {
			$data["recipient_id"]    = (int) $user["u_id"];
			$data["recipient_email"] = sanitize_text_field( $user["email"] );
			$data["recipient_name"]  = sanitize_text_field( $user["name"] );

			if ( $data["recipient_email"] === $data["user_email"] || $this->dbManager->isNotificationExists( $data ) ) {
				continue;
			}

			$this->dbManager->addNotification( $data );
		}
	}

	/* === MENTIONS END === */

	/* === NEW COMMENT REPLY START === */

	public function addMyCommentReplyNotification( $comment, $data ) {

		if ( empty( $this->options->data["notifications"]["myCommentReply"] ) || ! $comment->comment_parent ) {
			return;
		}

		if ( empty( $data ) ) {
			$data = WunHelper::getNotificationDataFromComment( $comment );
		}

		$parentComment = get_comment( $comment->comment_parent );

		$data["recipient_id"]    = (int) $parentComment->user_id;
		$data["recipient_email"] = sanitize_text_field( $parentComment->comment_author_email );
		$data["recipient_name"]  = sanitize_text_field( $parentComment->comment_author );

		$args                     = $data;
		$args["component_action"] = [ self::ACTION_MENTION, self::ACTION_MY_COMMENT_REPLY ];

		if ( $data["recipient_email"] === $data["user_email"] || $this->dbManager->isNotificationExists( $args ) ) {
			return;
		}

		$data["component_action"] = self::ACTION_MY_COMMENT_REPLY;
		$this->dbManager->addNotification( $data );
	}

	/* === NEW COMMENT REPLY END === */

	/* === NEW POST COMMENT START === */

	public function addMyPostCommentNotification( $comment, $data ) {

		if ( empty( $this->options->data["notifications"]["myPostComment"] ) || ! $comment->comment_post_ID ) {
			return;
		}

		$post = get_post( $comment->comment_post_ID );

		if ( empty( $post->post_author ) ) {
			return;
		}

		$user = get_user_by( "id", $post->post_author );

		if ( empty( $user->user_email ) ) {
			return;
		}

		if ( empty( $data ) ) {
			$data = WunHelper::getNotificationDataFromComment( $comment );
		}

		$data["recipient_id"]    = (int) $user->ID;
		$data["recipient_email"] = sanitize_text_field( $user->user_email );
		$data["recipient_name"]  = sanitize_text_field( $user->display_name );

		$args                     = $data;
		$args["component_action"] = [
			self::ACTION_MENTION,
			self::ACTION_MY_COMMENT_REPLY,
			self::ACTION_MY_POST_COMMENT
		];

		if ( $data["recipient_email"] === $data["user_email"] || $this->dbManager->isNotificationExists( $args ) ) {
			return;
		}
		$data["component_action"] = self::ACTION_MY_POST_COMMENT;
		$this->dbManager->addNotification( $data );
	}

	/* === NEW POST COMMENT END === */

	/* === SUBSCRIBED POST COMMENT START === */

	public function addSubscribedPostCommentsNotification( $comment, $data ) {

		if ( empty( $this->options->data["notifications"]["subscribedPostComment"] ) ||
		     empty( $comment->comment_post_ID ) ||
		     empty( $comment->comment_author_email ) ) {
			return;
		}

		$wpDiscuz = wpDiscuz();

		$args          = [
			"post_id"           => (int) $comment->comment_post_ID,
			"subscribtion_type" => "post",
		];
		$subscriptions = $wpDiscuz->dbManager->getAllSubscriptions( $args );

		if ( empty( $subscriptions ) || ! is_array( $subscriptions ) ) {
			return;
		}

		if ( empty( $data ) ) {
			$data = WunHelper::getNotificationDataFromComment( $comment );
		}

		$this->subscriptions = [];

		foreach ( $subscriptions as $subscription ) {
			$user = get_user_by( "email", $subscription["email"] );

			$data["recipient_id"]    = empty( $user->ID ) ? 0 : (int) $user->ID;
			$data["recipient_email"] = sanitize_text_field( $subscription["email"] );
			$data["recipient_name"]  = sanitize_text_field( $user->display_name );

			$data["component_action"] = [
				self::ACTION_MENTION,
				self::ACTION_MY_COMMENT_REPLY,
				self::ACTION_MY_POST_COMMENT,
				self::ACTION_SUBSCRIBED_POST_COMMENT
			];
			if ( $data["recipient_email"] === $data["user_email"] || $this->dbManager->isNotificationExists( $data ) ) {
				continue;
			}

			$data["component_action"] = self::ACTION_SUBSCRIBED_POST_COMMENT;

			if ( WunHelper::addNotificationsOnApprove() ) {
				$this->dbManager->addNotification( $data );
			}

			$this->subscriptions[] = $data;
		}

		add_filter( "wpdiscuz_ajax_callbacks", function ( $response ) {
			WunHelper::initEncryptionArgs();
			$response["callbackFunctions"][] = "wunAddSubscriptionsNotifications"; // function name in js
			$response["subscriptions"]       = json_encode( WunHelper::wunEncryptDecrypt( $this->subscriptions, "e" ) );
			$this->subscriptions             = null;

			return $response;
		} );
	}

	public function addSubscriptionsNotifications() {

		if ( empty( $_POST["subscriptions"] ) ) {
			wp_die( "" );
		}

		WunHelper::initEncryptionArgs();

		$arr           = json_decode( stripslashes( sanitize_text_field( $_POST["subscriptions"] ) ), ARRAY_A );
		$subscriptions = WunHelper::wunEncryptDecrypt( $arr, "d" );

		if ( empty( $subscriptions ) || ! is_array( $subscriptions ) ) {
			wp_die( "" );
		}

		foreach ( $subscriptions as $data ) {
			$this->dbManager->addNotification( $data );
		}
		wp_die( "" );
	}

	/* === SUBSCRIBED POST COMMENT END === */

	/* === FOLLOWING USER COMMENT START === */

	public function addFollowingUserCommentsNotification( $comment, $data ) {

		if ( empty( $this->options->data["notifications"]["followingUserComment"] ) || empty( $comment->user_id ) ) {
			return;
		}

		$wpDiscuz = wpDiscuz();

		$followers = $wpDiscuz->dbManager->getUserFollowers( $comment->comment_author_email );

		if ( empty( $followers ) || ! is_array( $followers ) ) {
			return;
		}

		if ( empty( $data ) ) {
			$data = WunHelper::getNotificationDataFromComment( $comment );
		}

		$this->follows = [];

		foreach ( $followers as $follower ) {
			$user = get_user_by( "email", $follower["follower_email"] );

			$data["recipient_id"]    = empty( $user->ID ) ? 0 : (int) $user->ID;
			$data["recipient_email"] = sanitize_text_field( $follower["follower_email"] );
			$data["recipient_name"]  = sanitize_text_field( $follower["follower_name"] );

			$data["component_action"] = [
				self::ACTION_MENTION,
				self::ACTION_MY_COMMENT_REPLY,
				self::ACTION_MY_POST_COMMENT,
				self::ACTION_SUBSCRIBED_POST_COMMENT,
				self::ACTION_FOLLOWING_USER_COMMENT
			];
			if ( $data["recipient_email"] === $data["user_email"] || $this->dbManager->isNotificationExists( $data ) ) {
				continue;
			}

			$data["component_action"] = self::ACTION_FOLLOWING_USER_COMMENT;

			if ( WunHelper::addNotificationsOnApprove() ) {
				$this->dbManager->addNotification( $data );
			}

			$this->follows[] = $data;
		}

		add_filter( "wpdiscuz_ajax_callbacks", function ( $response ) {
			WunHelper::initEncryptionArgs();
			$response["callbackFunctions"][] = "wunAddFollowsNotifications"; // function name in js
			$response["follows"]             = json_encode( WunHelper::wunEncryptDecrypt( $this->follows, "e" ) );
			$this->follows                   = null;

			return $response;
		} );
	}

	public function addFollowsNotifications() {

		if ( empty( $_POST["follows"] ) ) {
			wp_die( "" );
		}

		WunHelper::initEncryptionArgs();

		$arr     = json_decode( stripslashes( sanitize_text_field( $_POST["follows"] ) ), ARRAY_A );
		$follows = WunHelper::wunEncryptDecrypt( $arr, "d" );

		if ( empty( $follows ) || ! is_array( $follows ) ) {
			wp_die( "" );
		}

		foreach ( $follows as $data ) {
			$this->dbManager->addNotification( $data );
		}
		wp_die( "" );
	}

	/* === FOLLOWING USER COMMENT END === */


	/* === APPROVED USER COMMENT START === */

	public function addApprovedNotification( $newStatus, $oldStatus, $comment ) {

		$currentUser = WunHelper::getCurrentUser();

		$data                      = [];
		$data["recipient_id"]      = (int) $comment->user_id;
		$data["recipient_email"]   = sanitize_text_field( $comment->comment_author_email );
		$data["recipient_name"]    = sanitize_text_field( $comment->comment_author );
		$data["user_id"]           = $currentUser->ID;
		$data["user_email"]        = sanitize_text_field( $currentUser->user_email );
		$data["user_name"]         = sanitize_text_field( $currentUser->display_name );
		$data["user_ip"]           = WunHelper::getRealIPAddr();
		$data["item_id"]           = (int) $comment->comment_post_ID;
		$data["secondary_item_id"] = (int) $comment->comment_ID;
		$data["component_name"]    = "wpdiscuz";
		$data["component_action"]  = self::ACTION_MY_COMMENT_APPROVE;
		$data["action_date"]       = current_time( "mysql" );
		$data["action_timestamp"]  = current_time( "timestamp" );
		$data["is_new"]            = 1;
		$data["extras"]            = "";

		if ( $newStatus === "approved" ) {
			if ( empty( $this->options->data["notifications"]["myCommentApprove"] ) || $this->dbManager->isNotificationExists( $data ) ||
			     $comment->comment_author_email === $currentUser->user_email ) {
				return;
			}

			$this->dbManager->addNotification( $data );

			$this->addGroupedNotification( $comment, $comment->comment_approved, null );
		} else {
			$args = [
				"secondary_item_id" => $data["secondary_item_id"],
				"component_action"  => [
					self::ACTION_VOTE,
					self::ACTION_MENTION,
					self::ACTION_MY_COMMENT_REPLY,
					self::ACTION_MY_POST_COMMENT,
					self::ACTION_SUBSCRIBED_POST_COMMENT,
					self::ACTION_FOLLOWING_USER_COMMENT,
					self::ACTION_MY_COMMENT_APPROVE
				]
			];
			$this->dbManager->deleteNotifications( $args );
		}
	}

	/* === APPROVED USER COMMENT END === */


	/* === DELETE USER RELATED NOTIFICATIONS START === */

	public function deleteUserRelatedNotifications( $id, $reassign, $user ) {
		$this->dbManager->deleteNotifications( [ "recipient_id" => $id ] );
		$this->dbManager->deleteNotifications( [ "user_id" => $id ] );
	}

	/* === DELETE USER RELATED NOTIFICATIONS END === */


	/* === DELETE POST RELATED NOTIFICATIONS START === */

	public function deletePostRelatedNotifications( $postId, $post ) {
		$this->dbManager->deleteNotifications( [ "item_id" => $postId ] );
	}

	public function postStatusChanged( $newStatus, $oldStatus, $post ) {
		if ( $newStatus !== "publish" && $newStatus !== "private" ) {
			$this->dbManager->deleteNotifications( [ "item_id" => $post->ID ] );
		}
	}

	/* === DELETE POST RELATED NOTIFICATIONS END === */

	/* === DELETE COMMENT RELATED NOTIFICATIONS START === */

	public function deleteCommentRelatedNotifications( $commentId, $comment ) {
		$this->dbManager->deleteNotifications( [
			"secondary_item_id" => $commentId,
			"component_action"  => [
				self::ACTION_VOTE,
				self::ACTION_MENTION,
				self::ACTION_MY_COMMENT_REPLY,
				self::ACTION_MY_POST_COMMENT,
				self::ACTION_SUBSCRIBED_POST_COMMENT,
				self::ACTION_FOLLOWING_USER_COMMENT,
				self::ACTION_MY_COMMENT_APPROVE
			]
		] );
	}

	/* === DELETE COMMENT RELATED NOTIFICATIONS END === */


	/* === DELETE ALL NOTIFICATIONS START === */

	public function deleteAllNotifications() {
		$nonce = empty( $_POST["nonce"] ) ? null : trim( sanitize_text_field( $_POST["nonce"] ) );

		$response = [ "message" => "" ];

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, WunHelper::uniqueNonceKey() ) ) {
			$response["message"] = __( "Invalid nonce!", "wpdiscuz-user-notifications" );
			wp_send_json_error( $response );
		}
		$actions = $this->options->getActions();

		if ( empty( $actions ) ) {
			$response["message"] = __( "Notifications is disabled", "wpdiscuz-user-notifications" );
			wp_send_json_error( $response );
		}

		$recipient = WunHelper::getNotifyer();

		$data = [
			"recipient_id"     => $recipient["user_id"],
			"recipient_email"  => $recipient["user_email"],
			"component_action" => $actions
		];
		$this->dbManager->deleteNotifications( $data );
		$response["message"] = __( "All notifications has been deleted", "wpdiscuz-user-notifications" );
		wp_send_json_success( $response );
	}

	/* === DELETE ALL NOTIFICATIONS END === */

	/* === MARK NOTIFICATION AS READ START === */

	public function markRead() {
		if ( empty( $_GET["action"] ) || empty( $_GET["redirect_to"] ) || empty( $_GET["_nonce"] ) || empty( $_GET["id"] ) ) {
			return;
		}

		$action     = sanitize_text_field( $_GET["action"] );
		$redirectTo = urldecode_deep( $_GET["redirect_to"] );
		$nonce      = sanitize_text_field( $_GET["_nonce"] );
		$id         = (int) $_GET["id"];

		if ( $action !== self::ACTION_MARK_READ || ! $redirectTo || ! $id || ! wp_verify_nonce( $nonce, md5( ABSPATH . $id ) ) ) {
			return;
		}

		$currentUser = WunHelper::getNotifyer();

		if ( empty( $currentUser["user_id"] ) && empty( $currentUser["user_email"] ) ) {
			return;
		}

		$args = [
			"id"              => $id,
			"recipient_id"    => $currentUser["user_id"],
			"recipient_email" => $currentUser["user_email"],
		];

		$notification = $this->dbManager->getNotification( $args );

		if ( ! $notification ) {
			return;
		}

		//$this->dbManager->deleteNotifications($args);
		$this->dbManager->setAsRead( [ "id" => $id ] );

		exit( wp_safe_redirect( $redirectTo ) );
	}

	/* === MARK NOTIFICATION AS READ END === */

	/* === MARK NOTIFICATION AS READ AJAX START === */
	public function updateStatusAjax() {
		$id    = empty( $_POST["id"] ) ? null : (int) $_POST["id"];
		$nonce = empty( $_POST["nonce"] ) ? null : trim( sanitize_text_field( $_POST["nonce"] ) );

		$response = [ "message" => "" ];

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, md5( ABSPATH . $id ) ) ) {
			$response["message"] = __( "Invalid nonce!", "wpdiscuz-user-notifications" );
			wp_send_json_error( $response );
		}

		$currentUser = WunHelper::getNotifyer();

		if ( empty( $currentUser["user_id"] ) && empty( $currentUser["user_email"] ) ) {
			$response["message"] = __( "Permission denied!", "wpdiscuz-user-notifications" );
			wp_send_json_error( $response );
		}

		$args = [
			"id"              => $id,
			"recipient_id"    => $currentUser["user_id"],
			"recipient_email" => $currentUser["user_email"],
		];

		$notification = $this->dbManager->getNotification( $args );

		if ( ! $notification ) {
			$response["message"] = __( "Notification does not exist!", "wpdiscuz-user-notifications" );
			wp_send_json_error( $response );
		}

		if ( (int) $notification['is_new'] ) {
			$this->dbManager->setAsRead( [ "id" => $id ] );
			$response["message"]   = __( "The notification has been marked as read", "wpdiscuz-user-notifications" );
			$response["wunStatus"] = "read";
		} else {
			$this->dbManager->setAsUnread( [ "id" => $id ] );
			$response["message"]   = __( "The notification has been marked as unread", "wpdiscuz-user-notifications" );
			$response["wunStatus"] = "unread";
		}

		$actions    = $this->options->getActions();
		$dateFormat = get_option( "date_format" );
		$timeFormat = get_option( "time_format" );

		$args = [
			"recipient_id"     => $notification["recipient_id"],
			"recipient_email"  => $notification["recipient_email"],
			"lastXDays"        => (int) $this->options->data["lastXDays"],
			"dateTimeFormat"   => $dateFormat . " " . $timeFormat,
			"component_action" => $actions,
			"is_new"           => 1,
		];

		$response["itemsTotal"] = $this->dbManager->getNotificationsCount( $args );

		wp_send_json_success( $response );
	}
	/* === MARK NOTIFICATION AS READ AJAX END === */
}
