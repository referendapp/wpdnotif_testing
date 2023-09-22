<?php

if (!defined("ABSPATH")) {
    exit();
}

interface WunConstants {
        
    const OPTION_MAIN                       = "wpdiscuz_un_options";
    const OPTION_VERSION                    = "wpdiscuz_un_version";
    
    // NOTIFICATION ACTIONS
    const ACTION_VOTE                       = "wpdiscuz_vote";
    const ACTION_FOLLOWER                   = "wpdiscuz_new_follower";
    const ACTION_MY_POST_RATE               = "wpdiscuz_my_post_rate";
    const ACTION_MENTION                    = "wpdiscuz_mention";
    const ACTION_MY_COMMENT_REPLY           = "wpdiscuz_my_comment_reply";
    const ACTION_MY_POST_COMMENT            = "wpdiscuz_my_post_comment";
    const ACTION_SUBSCRIBED_POST_COMMENT    = "wpdiscuz_subscribed_post_comment";
    const ACTION_FOLLOWING_USER_COMMENT     = "wpdiscuz_following_user_comment";
    const ACTION_MY_COMMENT_APPROVE         = "wpdiscuz_my_comment_approve";
    
    // MARK READ ACTION
    const ACTION_MARK_READ                  = "wun_read";
    
    // REQUEST TYPES
    const REQUEST_TYPE_CHECK                = "check";
    const REQUEST_TYPE_LOAD                 = "load";
}
