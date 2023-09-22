<?php
if (!defined("ABSPATH")) {
    exit();
}
$wpdiscuz = wpDiscuz();
$mainPage = WpDiscuzConstants::PAGE_SETTINGS;
$tab = esc_attr($setting["values"]->tabKey);
$nonce = WunHelper::uniqueNonce();
$mainUrl = admin_url("admin.php?page={$mainPage}&wpd_tab={$tab}");
$redirectTo = urlencode_deep($mainUrl);
$deleteAllNotificationsUrl = $mainUrl . "&wun_delete=all&_nonce={$nonce}&redirect_to={$redirectTo}";
$deleteExpiredNotificationsUrl = $mainUrl . "&wun_delete=expired&_nonce={$nonce}&redirect_to={$redirectTo}";
$deleteReadNotificationsUrl = $mainUrl . "&wun_delete=read&_nonce={$nonce}&redirect_to={$redirectTo}";

$allNotificationsCount = $setting["values"]->dbManager->allNotificationsCount();
$expiredNotificationsCount = $setting["values"]->dbManager->allNotificationsCount(["lastXDays" => $setting["values"]->data["lastXDays"]]);
$readNotificationsCount = $setting["values"]->dbManager->allNotificationsCount(["is_new" => 0]);


$allDisabled = $allNotificationsCount ? "" : "disabled='disabled'";
$expiredDisabled = $expiredNotificationsCount ? "" : "disabled='disabled'";
$readDisabled = $readNotificationsCount ? "" : "disabled='disabled'";
?>

<div class="wpd-opt-row">
    <div class="wpd-opt-intro" style="text-align: left;">
        <?php _e("Two options of user notifications are available. You can put the notification bell in the main menu using <code>%wpdiscuz-bell%</code> shortocde as a Custom Link URL and any phrase as the Link Text. This menu item will be replaced to a notification bell on the website front-end menu:", "wpdiscuz-user-notifications"); ?>
        <img src="<?php echo trim(plugins_url(WPDUN_DIR_NAME), '/') ?>/assets/img/wpdiscuz-notification-bell.png" style="max-width: 100%; margin: 10px 0 15px; border: 1px dashed #ccc; padding-bottom: 5px;"><br/>
        <?php _e("As a second option, you can add the notification bell using <code>[wpdiscuz_bell]</code> shortcode in blocks and widgets which support shortcodes.", "wpdiscuz-user-notifications"); ?><br/>
        <?php _e("Finally, you can enable Web Push Notification generated and controlled by browsers. This kind of notifications are displayed on the screen even if you switched to another tab or minimized the browser window.", "wpdiscuz-user-notifications"); ?>
        <a href="https://support.mozilla.org/en-US/kb/push-notifications-firefox" target="_blank"><?php _e('More information about the Web Push notifications...', 'wpdiscuz-user-notifications') ?></a>
    </div>
</div>

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="loadMethod">
    <div class="wpd-opt-name">
        <label for="loadMethod"><?php esc_html_e($setting["options"]["loadMethod"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["loadMethod"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <div class="wpd-switch-field">
            <input type="radio" value="rest" <?php checked(($setting["values"]->data["loadMethod"]) === "rest"); ?> name="<?php echo $tab; ?>[loadMethod]" id="loadMethodOn" />
            <label for="loadMethodOn" style="min-width:60px;"><?php esc_html_e("REST", "wpdiscuz-user-notifications"); ?></label>
            <input type="radio" value="ajax" <?php checked(($setting["values"]->data["loadMethod"]) === "ajax"); ?> name="<?php echo $tab; ?>[loadMethod]" id="loadMethodOff" />
            <label for="loadMethodOff" style="min-width:60px;"><?php esc_html_e("AJAX", "wpdiscuz-user-notifications"); ?></label>
        </div>
    </div>

    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["loadMethod"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="notifications">
    <div class="wpd-opt-name">
        <label for="notifications"><?php esc_html_e($setting["options"]["notifications"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["notifications"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">

        <div class="wpd-opt-input-wtext">
            <div class="wpd-switcher wpd-switcher-wtext">
                <input type="hidden" value="0" name="<?php echo $tab; ?>[notifications][myCommentVote]"/>
                <input type="checkbox" <?php checked(((int) $setting["values"]->data["notifications"]["myCommentVote"]) === 1); ?> value="1" name="<?php echo $tab; ?>[notifications][myCommentVote]" id="ntfMyCommentVote"/>
                <label for="ntfMyCommentVote"></label>
            </div>
            <div class="wpd-switcher-label">
                <label for="ntfMyCommentVote"><?php esc_html_e("someone votes on my comment", "wpdiscuz-user-notifications"); ?></label>
            </div>
        </div>

        <div class="wpd-opt-input-wtext">
            <div class="wpd-switcher wpd-switcher-wtext">
                <input type="hidden" value="0" name="<?php echo $tab; ?>[notifications][newFollower]"/>
                <input type="checkbox" <?php checked(((int) $setting["values"]->data["notifications"]["newFollower"]) === 1); ?> value="1" name="<?php echo $tab; ?>[notifications][newFollower]" id="ntfNewFollower"/>
                <label for="ntfNewFollower"></label>
            </div>
            <div class="wpd-switcher-label">
                <label for="ntfNewFollower"><?php esc_html_e("someone follows me", "wpdiscuz-user-notifications"); ?></label>
            </div>
        </div>

        <div class="wpd-opt-input-wtext">
            <div class="wpd-switcher wpd-switcher-wtext">
                <input type="hidden" value="0" name="<?php echo $tab; ?>[notifications][myPostRate]"/>
                <input type="checkbox" <?php checked(((int) $setting["values"]->data["notifications"]["myPostRate"]) === 1); ?> value="1" name="<?php echo $tab; ?>[notifications][myPostRate]" id="ntfMyPostRate"/>
                <label for="ntfMyPostRate"></label>
            </div>
            <div class="wpd-switcher-label">
                <label for="ntfMyPostRate"><?php esc_html_e("someone rates my post", "wpdiscuz-user-notifications"); ?></label>
            </div>
        </div>        

        <div class="wpd-opt-input-wtext">
            <div class="wpd-switcher wpd-switcher-wtext">
                <input type="hidden" value="0" name="<?php echo $tab; ?>[notifications][mention]"/>
                <input type="checkbox" <?php checked(((int) $setting["values"]->data["notifications"]["mention"]) === 1); ?> value="1" name="<?php echo $tab; ?>[notifications][mention]" id="ntfMention"/>
                <label for="ntfMention"></label>
            </div>
            <div class="wpd-switcher-label">
                <label for="ntfMention"><?php esc_html_e("someone mentioned me", "wpdiscuz-user-notifications"); ?></label>
            </div>
        </div>

        <div class="wpd-opt-input-wtext">
            <div class="wpd-switcher wpd-switcher-wtext">
                <input type="hidden" value="0" name="<?php echo $tab; ?>[notifications][myCommentReply]"/>
                <input type="checkbox" <?php checked(((int) $setting["values"]->data["notifications"]["myCommentReply"]) === 1); ?> value="1" name="<?php echo $tab; ?>[notifications][myCommentReply]" id="ntfMyCommentReply"/>
                <label for="ntfMyCommentReply"></label>
            </div>
            <div class="wpd-switcher-label">
                <label for="ntfMyCommentReply"><?php esc_html_e("someone replied to my comment", "wpdiscuz-user-notifications"); ?></label>
            </div>
        </div>

        <div class="wpd-opt-input-wtext">
            <div class="wpd-switcher wpd-switcher-wtext">
                <input type="hidden" value="0" name="<?php echo $tab; ?>[notifications][myPostComment]"/>
                <input type="checkbox" <?php checked(((int) $setting["values"]->data["notifications"]["myPostComment"]) === 1); ?> value="1" name="<?php echo $tab; ?>[notifications][myPostComment]" id="ntfMyPostComment"/>
                <label for="ntfMyPostComment"></label>
            </div>
            <div class="wpd-switcher-label">
                <label for="ntfMyPostComment"><?php esc_html_e("someone commented on my post", "wpdiscuz-user-notifications"); ?></label>
            </div>
        </div>

        <div class="wpd-opt-input-wtext">
            <div class="wpd-switcher wpd-switcher-wtext">
                <input type="hidden" value="0" name="<?php echo $tab; ?>[notifications][subscribedPostComment]"/>
                <input type="checkbox" <?php checked(((int) $setting["values"]->data["notifications"]["subscribedPostComment"]) === 1); ?> value="1" name="<?php echo $tab; ?>[notifications][subscribedPostComment]" id="ntfSubscribedPostComment"/>
                <label for="ntfSubscribedPostComment"></label>
            </div>
            <div class="wpd-switcher-label">
                <label for="ntfSubscribedPostComment"><?php esc_html_e("new comment on subscribed post", "wpdiscuz-user-notifications"); ?></label>
            </div>
        </div>

        <div class="wpd-opt-input-wtext">
            <div class="wpd-switcher wpd-switcher-wtext">
                <input type="hidden" value="0" name="<?php echo $tab; ?>[notifications][followingUserComment]"/>
                <input type="checkbox" <?php checked(((int) $setting["values"]->data["notifications"]["followingUserComment"]) === 1); ?> value="1" name="<?php echo $tab; ?>[notifications][followingUserComment]" id="ntfFollowingUserComment"/>
                <label for="ntfFollowingUserComment"></label>
            </div>
            <div class="wpd-switcher-label">
                <label for="ntfFollowingUserComment"><?php esc_html_e("new comment by followed user", "wpdiscuz-user-notifications"); ?></label>
            </div>
        </div>

        <div class="wpd-opt-input-wtext">
            <div class="wpd-switcher wpd-switcher-wtext">
                <input type="hidden" value="0" name="<?php echo $tab; ?>[notifications][myCommentApprove]"/>
                <input type="checkbox" <?php checked(((int) $setting["values"]->data["notifications"]["myCommentApprove"]) === 1); ?> value="1" name="<?php echo $tab; ?>[notifications][myCommentApprove]" id="ntfMyCommentApprove"/>
                <label for="ntfMyCommentApprove"></label>
            </div>
            <div class="wpd-switcher-label">
                <label for="ntfMyCommentApprove"><?php esc_html_e("my comment is approved", "wpdiscuz-user-notifications"); ?></label>
            </div>
        </div>

    </div>

    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["notifications"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="adminBarBell">
    <div class="wpd-opt-name">
        <label for="adminBarBell"><?php esc_html_e($setting["options"]["adminBarBell"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["adminBarBell"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <div class="wpd-switch-field">
            <input type="radio" value="1" <?php checked(((int) $setting["values"]->data["adminBarBell"]) === 1); ?> name="<?php echo $tab; ?>[adminBarBell]" id="adminBarBellOn" />
            <label for="adminBarBellOn" style="min-width:60px;"><?php esc_html_e("Enable", "wpdiscuz-user-notifications"); ?></label>
            <input type="radio" value="0" <?php checked(((int) $setting["values"]->data["adminBarBell"]) === 0); ?> name="<?php echo $tab; ?>[adminBarBell]" id="adminBarBellOff" />
            <label for="adminBarBellOff" style="min-width:60px;"><?php esc_html_e("Disable", "wpdiscuz-user-notifications"); ?></label>
        </div>
    </div>

    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["adminBarBell"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="browserNotifications">
    <div class="wpd-opt-name">
        <label for="browserNotifications"><?php esc_html_e($setting["options"]["browserNotifications"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["browserNotifications"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <div class="wpd-switch-field">
            <input type="radio" value="1" <?php checked(((int) $setting["values"]->data["browserNotifications"]) === 1); ?> name="<?php echo $tab; ?>[browserNotifications]" id="browserNotificationsOn" />
            <label for="browserNotificationsOn" style="min-width:60px;"><?php esc_html_e("Enable", "wpdiscuz-user-notifications"); ?></label>
            <input type="radio" value="0" <?php checked(((int) $setting["values"]->data["browserNotifications"]) === 0); ?> name="<?php echo $tab; ?>[browserNotifications]" id="browserNotificationsOff" />
            <label for="browserNotificationsOff" style="min-width:60px;"><?php esc_html_e("Disable", "wpdiscuz-user-notifications"); ?></label>
        </div>
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["browserNotifications"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="setReadOnLoad">
    <div class="wpd-opt-name">
        <label for="setReadOnLoad"><?php esc_html_e($setting["options"]["setReadOnLoad"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["setReadOnLoad"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <div class="wpd-switch-field">
            <input type="radio" value="1" <?php checked(((int) $setting["values"]->data["setReadOnLoad"]) === 1); ?> name="<?php echo $tab; ?>[setReadOnLoad]" id="setReadOnLoadOn" />
            <label for="setReadOnLoadOn" style="min-width:60px;"><?php esc_html_e("Enable", "wpdiscuz-user-notifications"); ?></label>
            <input type="radio" value="0" <?php checked(((int) $setting["values"]->data["setReadOnLoad"]) === 0); ?> name="<?php echo $tab; ?>[setReadOnLoad]" id="setReadOnLoadOff" />
            <label for="setReadOnLoadOff" style="min-width:60px;"><?php esc_html_e("Disable", "wpdiscuz-user-notifications"); ?></label>
        </div>
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["setReadOnLoad"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="bellForRoles">
    <div class="wpd-opt-name">
        <label for="bellForRoles"><?php echo $setting["options"]["bellForRoles"]["label"] ?></label>
        <p class="wpd-desc"><?php echo $setting["options"]["bellForRoles"]["description"] ?></p>
    </div>
    <div class="wpd-opt-input">
        <?php
        $blogRoles = get_editable_roles();
        foreach ($blogRoles as $role => $info) {
            ?>
            <div class="wpd-mublock-inline" style="width: 45%;">
                <input type="checkbox" <?php checked(in_array($role, $setting["values"]->data["bellForRoles"])); ?> value="<?php echo $role; ?>" name="<?php echo esc_attr($setting["values"]->tabKey); ?>[bellForRoles][]" id="wun-<?php echo $role; ?>" style="margin:0px; vertical-align: middle;" />
                <label for="wun-<?php echo $role; ?>" style=""><?php echo $info["name"]; ?></label>
            </div>
            <?php
        }
        ?>
    </div>
    <div class="wpd-opt-doc">
        <?php echo $wpdiscuz->options->printDocLink($setting["options"]["bellForRoles"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="bellForGuests">
    <div class="wpd-opt-name">
        <label for="bellForGuests"><?php esc_html_e($setting["options"]["bellForGuests"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["bellForGuests"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <div class="wpd-switch-field">
            <input type="radio" value="1" <?php checked(((int) $setting["values"]->data["bellForGuests"]) === 1); ?> name="<?php echo $tab; ?>[bellForGuests]" id="bellForGuestsOn" />
            <label for="bellForGuestsOn" style="min-width:60px;"><?php esc_html_e("Enable", "wpdiscuz-user-notifications"); ?></label>
            <input type="radio" value="0" <?php checked(((int) $setting["values"]->data["bellForGuests"]) === 0); ?> name="<?php echo $tab; ?>[bellForGuests]" id="bellForGuestsOff" />
            <label for="bellForGuestsOff" style="min-width:60px;"><?php esc_html_e("Disable", "wpdiscuz-user-notifications"); ?></label>
        </div>
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["bellForGuests"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="bellForVisitors">
    <div class="wpd-opt-name">
        <label for="bellForVisitors"><?php esc_html_e($setting["options"]["bellForVisitors"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["bellForVisitors"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <div class="wpd-switch-field">
            <input type="radio" value="1" <?php checked(((int) $setting["values"]->data["bellForVisitors"]) === 1); ?> name="<?php echo $tab; ?>[bellForVisitors]" id="bellForVisitorsOn" />
            <label for="bellForVisitorsOn" style="min-width:60px;"><?php esc_html_e("Enable", "wpdiscuz-user-notifications"); ?></label>
            <input type="radio" value="0" <?php checked(((int) $setting["values"]->data["bellForVisitors"]) === 0); ?> name="<?php echo $tab; ?>[bellForVisitors]" id="bellForVisitorsOff" />
            <label for="bellForVisitorsOff" style="min-width:60px;"><?php esc_html_e("Disable", "wpdiscuz-user-notifications"); ?></label>
        </div>
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["bellForVisitors"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="lastXDays">
    <div class="wpd-opt-name">
        <label for="lastXDays"><?php esc_html_e($setting["options"]["lastXDays"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["lastXDays"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <input type="number" min="1" value="<?php esc_attr_e($setting["values"]->data["lastXDays"]); ?>" name="<?php echo $tab; ?>[lastXDays]" />
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["lastXDays"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="liveUpdate">
    <div class="wpd-opt-name">
        <label for="liveUpdate"><?php esc_html_e($setting["options"]["liveUpdate"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["liveUpdate"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <div class="wpd-switch-field">
            <input type="radio" value="1" <?php checked(((int) $setting["values"]->data["liveUpdate"]) === 1); ?> name="<?php echo $tab; ?>[liveUpdate]" id="liveUpdateOn" />
            <label for="liveUpdateOn" style="min-width:60px;"><?php esc_html_e("Enable", "wpdiscuz-user-notifications"); ?></label>
            <input type="radio" value="0" <?php checked(((int) $setting["values"]->data["liveUpdate"]) === 0); ?> name="<?php echo $tab; ?>[liveUpdate]" id="liveUpdateOff" />
            <label for="liveUpdateOff" style="min-width:60px;"><?php esc_html_e("Disable", "wpdiscuz-user-notifications"); ?></label>
        </div>
    </div>

    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["liveUpdate"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="updateTimer">
    <div class="wpd-opt-name">
        <label for="updateTimer"><?php esc_html_e($setting["options"]["updateTimer"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["updateTimer"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <select id="updateTimer" name="<?php esc_html_e($setting["values"]->tabKey); ?>[updateTimer]">            
            <option value="30" <?php selected(((int) $setting["values"]->data["updateTimer"]), 30); ?>>30 <?php esc_html_e("Seconds", "wpdiscuz-user-notifications"); ?></option>
            <option value="60" <?php selected(((int) $setting["values"]->data["updateTimer"]), 60); ?>>1 <?php esc_html_e("Minute", "wpdiscuz-user-notifications"); ?></option>
            <option value="180" <?php selected(((int) $setting["values"]->data["updateTimer"]), 180); ?>>3 <?php esc_html_e("Minutes", "wpdiscuz-user-notifications"); ?></option>
            <option value="300" <?php selected(((int) $setting["values"]->data["updateTimer"]), 300); ?>>5 <?php esc_html_e("Minutes", "wpdiscuz-user-notifications"); ?></option>
            <option value="600" <?php selected(((int) $setting["values"]->data["updateTimer"]), 600); ?>>10 <?php esc_html_e("Minutes", "wpdiscuz-user-notifications"); ?></option>
        </select>
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["updateTimer"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="perLoad">
    <div class="wpd-opt-name">
        <label for="perLoad"><?php esc_html_e($setting["options"]["perLoad"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["perLoad"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <input type="number" min="1" value="<?php esc_attr_e($setting["values"]->data["perLoad"]); ?>" name="<?php echo $tab; ?>[perLoad]" />
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["perLoad"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="showCountOfNotLoaded">
    <div class="wpd-opt-name">
        <label for="showCountOfNotLoaded"><?php esc_html_e($setting["options"]["showCountOfNotLoaded"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["showCountOfNotLoaded"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <div class="wpd-switch-field">
            <input type="radio" value="1" <?php checked(((int) $setting["values"]->data["showCountOfNotLoaded"]) === 1); ?> name="<?php echo $tab; ?>[showCountOfNotLoaded]" id="showCountOfNotLoadedOn" />
            <label for="showCountOfNotLoadedOn" style="min-width:60px;"><?php esc_html_e("Enable", "wpdiscuz-user-notifications"); ?></label>
            <input type="radio" value="0" <?php checked(((int) $setting["values"]->data["showCountOfNotLoaded"]) === 0); ?> name="<?php echo $tab; ?>[showCountOfNotLoaded]" id="showCountOfNotLoadedOff" />
            <label for="showCountOfNotLoadedOff" style="min-width:60px;"><?php esc_html_e("Disable", "wpdiscuz-user-notifications"); ?></label>
        </div>
    </div>

    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["showCountOfNotLoaded"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="soundUrl">
    <div class="wpd-opt-name">
        <label for="soundUrl"><?php esc_html_e($setting["options"]["soundUrl"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["soundUrl"]["description"]); ?></p>
        <p class="wpd-desc">
            <?php
            esc_html_e("Default URL:", "wpdiscuz-user-notifications");
            echo "&nbsp;" . plugins_url(WPDUN_DIR_NAME . "/assets/audio/pristine.mp3")
            ?>
        </p>
    </div>
    <div class="wpd-opt-input">
        <input class="wun-scroll-to-right" type="text" value="<?php esc_attr_e($setting["values"]->data["soundUrl"]); ?>" name="<?php echo $tab; ?>[soundUrl]" />
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["soundUrl"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="playSoundWhen">
    <div class="wpd-opt-name">
        <label for="playSoundWhen"><?php esc_html_e($setting["options"]["playSoundWhen"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["playSoundWhen"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <div class="wpd-switch-field">
            <input type="radio" value="new" <?php checked($setting["values"]->data["playSoundWhen"] === "new"); ?> name="<?php echo $tab; ?>[playSoundWhen]" id="playSoundWhenNew" />
            <label for="playSoundWhenNew">
                <?php esc_html_e("New", "wpdiscuz-user-notifications"); ?>
            </label>
            <input type="radio" value="unread" <?php checked($setting["values"]->data["playSoundWhen"] === "unread"); ?> name="<?php echo $tab; ?>[playSoundWhen]" id="playSoundWhenUnread" />
            <label for="playSoundWhenUnread">
                <?php esc_html_e("Unread", "wpdiscuz-user-notifications"); ?>
            </label>
        </div>
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["playSoundWhen"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="bellStyle">
    <div class="wpd-opt-name">
        <label for="bellStyle"><?php esc_html_e($setting["options"]["bellStyle"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["bellStyle"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <div class="wpd-switch-field">
            <input type="radio" value="bordered" <?php checked($setting["values"]->data["bellStyle"] === "bordered"); ?> name="<?php echo $tab; ?>[bellStyle]" id="bellStyleBordered" />
            <label for="bellStyleBordered" class="wun-bell-style-lbl">
                <svg class="wun-bell-style" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg"><path d="M14.25 26.5c0-0.141-0.109-0.25-0.25-0.25-1.234 0-2.25-1.016-2.25-2.25 0-0.141-0.109-0.25-0.25-0.25s-0.25 0.109-0.25 0.25c0 1.516 1.234 2.75 2.75 2.75 0.141 0 0.25-0.109 0.25-0.25zM3.844 22h20.312c-2.797-3.156-4.156-7.438-4.156-13 0-2.016-1.906-5-6-5s-6 2.984-6 5c0 5.563-1.359 9.844-4.156 13zM27 22c0 1.094-0.906 2-2 2h-7c0 2.203-1.797 4-4 4s-4-1.797-4-4h-7c-1.094 0-2-0.906-2-2 2.312-1.953 5-5.453 5-13 0-3 2.484-6.281 6.625-6.891-0.078-0.187-0.125-0.391-0.125-0.609 0-0.828 0.672-1.5 1.5-1.5s1.5 0.672 1.5 1.5c0 0.219-0.047 0.422-0.125 0.609 4.141 0.609 6.625 3.891 6.625 6.891 0 7.547 2.688 11.047 5 13z"></path></svg>
                <?php esc_html_e("Bordered", "wpdiscuz-user-notifications"); ?>
            </label>
            <input type="radio" value="filled" <?php checked($setting["values"]->data["bellStyle"] === "filled"); ?> name="<?php echo $tab; ?>[bellStyle]" id="bellStyleFilled" />
            <label for="bellStyleFilled" class="wun-bell-style-lbl">
                <svg class="wun-bell-style" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg"><path d="M14.25 26.5c0-0.141-0.109-0.25-0.25-0.25-1.234 0-2.25-1.016-2.25-2.25 0-0.141-0.109-0.25-0.25-0.25s-0.25 0.109-0.25 0.25c0 1.516 1.234 2.75 2.75 2.75 0.141 0 0.25-0.109 0.25-0.25zM27 22c0 1.094-0.906 2-2 2h-7c0 2.203-1.797 4-4 4s-4-1.797-4-4h-7c-1.094 0-2-0.906-2-2 2.312-1.953 5-5.453 5-13 0-3 2.484-6.281 6.625-6.891-0.078-0.187-0.125-0.391-0.125-0.609 0-0.828 0.672-1.5 1.5-1.5s1.5 0.672 1.5 1.5c0 0.219-0.047 0.422-0.125 0.609 4.141 0.609 6.625 3.891 6.625 6.891 0 7.547 2.688 11.047 5 13z"></path></svg>
                <?php esc_html_e("Filled", "wpdiscuz-user-notifications"); ?>
            </label>
        </div>
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["bellStyle"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="containerAnimationInMs">
    <div class="wpd-opt-name">
        <label for="containerAnimationInMs"><?php esc_html_e($setting["options"]["containerAnimationInMs"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["containerAnimationInMs"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <input type="number" min="1" value="<?php esc_attr_e($setting["values"]->data["containerAnimationInMs"]); ?>" name="<?php echo $tab; ?>[containerAnimationInMs]" />
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["containerAnimationInMs"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="colors">
    <div class="wpd-opt-input" style="width: calc(100% - 40px);">
        <h2 style="margin-bottom: 0px;font-size: 15px; color: #555;"><?php esc_html_e($setting["options"]["colors"]["label"]) ?></h2>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["colors"]["description"]) ?></p>
        <hr />
        <div class="wpd-un-bell-colors" style="float: left; width: 48%;">
            <h4 style="font-size: 14px; color: #0c8d71;"><?php esc_html_e("Default Bell", "wpdiscuz-user-notifications"); ?></h4>
            <div class="wpd-color-wrap">
                <input type="text" class="wpdiscuz-color-picker regular-text" value="<?php esc_attr_e($setting["values"]->data["bellFillColor"]); ?>" id="bellFillColor" name="<?php echo $tab; ?>[bellFillColor]" placeholder="<?php esc_attr_e("Example: #00FF00", "wpdiscuz-user-notifications"); ?>"/>
                <label for="bellFillColor"><?php esc_html_e("Bell icon", "wpdiscuz-user-notifications"); ?></label>
            </div>
            <div class="wpd-color-wrap">
                <input type="text" class="wpdiscuz-color-picker regular-text" value="<?php esc_attr_e($setting["values"]->data["counterTextColor"]); ?>" id="counterTextColor" name="<?php echo $tab; ?>[counterTextColor]" placeholder="<?php esc_attr_e("Example: #00FF00", "wpdiscuz-user-notifications"); ?>"/>
                <label for="counterTextColor"><?php esc_html_e("Counter text", "wpdiscuz-user-notifications"); ?></label>
            </div>
            <div class="wpd-color-wrap">
                <input type="text" class="wpdiscuz-color-picker regular-text" value="<?php esc_attr_e($setting["values"]->data["counterBgColor"]); ?>" id="counterBgColor" name="<?php echo $tab; ?>[counterBgColor]" placeholder="<?php esc_attr_e("Example: #00FF00", "wpdiscuz-user-notifications"); ?>"/>
                <label for="counterBgColor"><?php esc_html_e("Counter background", "wpdiscuz-user-notifications"); ?></label>
            </div>
            <div class="wpd-color-wrap">
                <input type="text" class="wpdiscuz-color-picker regular-text" value="<?php esc_attr_e($setting["values"]->data["counterShadowColor"]); ?>" id="counterShadowColor" name="<?php echo $tab; ?>[counterShadowColor]" placeholder="<?php esc_attr_e("Example: #00FF00", "wpdiscuz-user-notifications"); ?>"/>
                <label for="counterShadowColor"><?php esc_html_e("Counter box shadow", "wpdiscuz-user-notifications"); ?></label>
            </div>
        </div>
        <div class="wpd-un-bar-bell-colors" style="float: left; width: 48%;">
            <h4 style="font-size: 14px; color: #0c8d71;"><?php esc_html_e("Admin Bar Bell", "wpdiscuz-user-notifications"); ?></h4>
            <div class="wpd-color-wrap">
                <input type="text" class="wpdiscuz-color-picker regular-text" value="<?php esc_attr_e($setting["values"]->data["barBellFillColor"]); ?>" id="barBellFillColor" name="<?php echo $tab; ?>[barBellFillColor]" placeholder="<?php esc_attr_e("Example: #00FF00", "wpdiscuz-user-notifications"); ?>"/>
                <label for="barBellFillColor"><?php esc_html_e("Bell icon", "wpdiscuz-user-notifications"); ?></label>
            </div>
            <div class="wpd-color-wrap">
                <input type="text" class="wpdiscuz-color-picker regular-text" value="<?php esc_attr_e($setting["values"]->data["barCounterTextColor"]); ?>" id="barCounterTextColor" name="<?php echo $tab; ?>[barCounterTextColor]" placeholder="<?php esc_attr_e("Example: #00FF00", "wpdiscuz-user-notifications"); ?>"/>
                <label for="barCounterTextColor"><?php esc_html_e("Counter text", "wpdiscuz-user-notifications"); ?></label>
            </div>
            <div class="wpd-color-wrap">
                <input type="text" class="wpdiscuz-color-picker regular-text" value="<?php esc_attr_e($setting["values"]->data["barCounterBgColor"]); ?>" id="barCounterBgColor" name="<?php echo $tab; ?>[barCounterBgColor]" placeholder="<?php esc_attr_e("Example: #00FF00", "wpdiscuz-user-notifications"); ?>"/>
                <label for="barCounterBgColor"><?php esc_html_e("Counter background", "wpdiscuz-user-notifications"); ?></label>
            </div>
            <div class="wpd-color-wrap">
                <input type="text" class="wpdiscuz-color-picker regular-text" value="<?php esc_attr_e($setting["values"]->data["barCounterShadowColor"]); ?>" id="barCounterShadowColor" name="<?php echo $tab; ?>[barCounterShadowColor]" placeholder="<?php esc_attr_e("Example: #00FF00", "wpdiscuz-user-notifications"); ?>"/>
                <label for="barCounterShadowColor"><?php esc_html_e("Counter box shadow", "wpdiscuz-user-notifications"); ?></label>
            </div>
        </div>        
        <div style="clear: both"></div>
    </div>
    <div class="wpd-opt-doc" style="padding-top: 36px;">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["colors"]["docurl"]) ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="deleteAllNotifications">
    <div class="wpd-opt-name">
        <label for="deleteAllNotifications"><?php esc_html_e($setting["options"]["deleteAllNotifications"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["deleteAllNotifications"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <a <?php echo $allDisabled; ?> href="<?php echo $deleteAllNotificationsUrl; ?>" class="button button-secondary wun-delete-notifications" data-wundelete="all" style="text-decoration: none;">
            <?php esc_html_e("Delete All Notifications", "wpdiscuz-user-notifications"); ?> <?php echo "({$allNotificationsCount})"; ?>
        </a>
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["deleteAllNotifications"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="deleteExpiredNotifications">
    <div class="wpd-opt-name">
        <label for="deleteExpiredNotifications"><?php esc_html_e($setting["options"]["deleteExpiredNotifications"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["deleteExpiredNotifications"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <a <?php echo $expiredDisabled; ?> href="<?php echo $deleteExpiredNotificationsUrl; ?>" class="button button-secondary wun-delete-notifications" data-wundelete="expired" style="text-decoration: none;">
            <?php esc_html_e("Delete Expired Notifications", "wpdiscuz-user-notifications"); ?> <?php echo "({$expiredNotificationsCount})"; ?>
        </a>
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["deleteExpiredNotifications"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- Option start -->
<div class="wpd-opt-row" data-wpd-opt="deleteReadNotifications">
    <div class="wpd-opt-name">
        <label for="deleteReadNotifications"><?php esc_html_e($setting["options"]["deleteReadNotifications"]["label"]); ?></label>
        <p class="wpd-desc"><?php esc_html_e($setting["options"]["deleteReadNotifications"]["description"]); ?></p>
    </div>
    <div class="wpd-opt-input">
        <a <?php echo $readDisabled; ?> href="<?php echo $deleteReadNotificationsUrl; ?>" class="button button-secondary wun-delete-notifications" data-wundelete="read" style="text-decoration: none;">
            <?php esc_html_e("Delete Read Notifications", "wpdiscuz-user-notifications"); ?> <?php echo "({$readNotificationsCount})"; ?>
        </a>
    </div>
    <div class="wpd-opt-doc">
        <?php $wpdiscuz->options->printDocLink($setting["options"]["deleteReadNotifications"]["docurl"]); ?>
    </div>
</div>
<!-- Option end -->

<!-- phrases -->
<div id="wun-accordion">

    <div class="wun-accordion-item">

        <div class="wpd-subtitle fas wun-accordion-title" style="margin-top: 20px;" data-wun-selector="wun-notifications-texts">
            <p><?php esc_html_e("Notifications' Texts", "wpdiscuz-user-notifications") ?></p>
        </div>

        <div class="wun-accordion-content">

            <div class="wpd-subtitle wun-subtitle" style="margin-top: 20px;">
                <?php esc_html_e("The user's comment has been liked", "wpdiscuz-user-notifications") ?>
            </div>

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfTitleCommentLike">
                <div class="wpd-opt-name">
                    <label for="ntfTitleCommentLike"><?php esc_html_e($setting["options"]["ntfTitleCommentLike"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfTitleCommentLike"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfTitleCommentLike"]); ?>" name="<?php echo $tab; ?>[ntfTitleCommentLike]" id="ntfTitleCommentLike" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfTitleCommentLike"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfMessageCommentLike">
                <div class="wpd-opt-name">
                    <label for="ntfMessageCommentLike"><?php esc_html_e($setting["options"]["ntfMessageCommentLike"]["label"]); ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfMessageCommentLike"]["description"]); ?></p>
                    <p class="wpd-desc"><?php esc_html_e("Available shortcodes", "wpdiscuz-user-notifications"); ?>:
                    <p class="wpd-desc">
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER]">[NOTIFIER]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_IMG]">[NOTIFIER_AVATAR_IMG]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_URL]">[NOTIFIER_AVATAR_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_URL]">[COMMENT_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_CONTENT]">[COMMENT_CONTENT]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[DATE]">[DATE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[MARK_READ_URL]">[MARK_READ_URL]</span>
                    </p>
                    <p class="wpd-desc wun-note">
                        <?php esc_html_e("Please do not remove or change <!--wundelete--> and <!--/wundelete--> HTML comments from this text! It's used to remove unnecessary HTML from browser notifications.", "wpdiscuz-user-notifications"); ?>
                    </p>
                </div>
                <div class="wpd-opt-input">
                    <?php wp_editor($setting["values"]->data["ntfMessageCommentLike"], "ntfMessageCommentLike", ["textarea_name" => $tab . "[ntfMessageCommentLike]", "textarea_rows" => 5, "teeny" => true, "media_buttons" => false]); ?>
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfMessageCommentLike"]["docurl"]); ?>
                </div>
            </div>
            <!-- Option end -->

            <div class="wpd-subtitle wun-subtitle" style="margin-top: 20px;">
                <?php esc_html_e("The user's comment has been disliked", "wpdiscuz-user-notifications") ?>
            </div>

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfTitleCommentDislike">
                <div class="wpd-opt-name">
                    <label for="ntfTitleCommentDislike"><?php esc_html_e($setting["options"]["ntfTitleCommentDislike"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfTitleCommentDislike"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfTitleCommentDislike"]); ?>" name="<?php echo $tab; ?>[ntfTitleCommentDislike]" id="ntfTitleCommentDislike" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfTitleCommentDislike"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfMessageCommentDislike">
                <div class="wpd-opt-name">
                    <label for="ntfMessageCommentDislike"><?php esc_html_e($setting["options"]["ntfMessageCommentDislike"]["label"]); ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfMessageCommentDislike"]["description"]); ?></p>
                    <p class="wpd-desc"><?php esc_html_e("Available shortcodes", "wpdiscuz-user-notifications"); ?>:
                    <p class="wpd-desc">
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER]">[NOTIFIER]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_IMG]">[NOTIFIER_AVATAR_IMG]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_URL]">[NOTIFIER_AVATAR_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_URL]">[COMMENT_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_CONTENT]">[COMMENT_CONTENT]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[DATE]">[DATE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[MARK_READ_URL]">[MARK_READ_URL]</span>
                    </p>
                    <p class="wpd-desc wun-note">
                        <?php esc_html_e("Please do not remove or change <!--wundelete--> and <!--/wundelete--> HTML comments from this text! It's used to remove unnecessary HTML from browser notifications.", "wpdiscuz-user-notifications"); ?>
                    </p>
                </div>
                <div class="wpd-opt-input">
                    <?php wp_editor($setting["values"]->data["ntfMessageCommentDislike"], "ntfMessageCommentDislike", ["textarea_name" => $tab . "[ntfMessageCommentDislike]", "textarea_rows" => 5, "teeny" => true, "media_buttons" => false]); ?>
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfMessageCommentDislike"]["docurl"]); ?>
                </div>
            </div>
            <!-- Option end -->

            <div class="wpd-subtitle wun-subtitle" style="margin-top: 20px;">
                <?php esc_html_e("The user has gained a new follower", "wpdiscuz-user-notifications") ?>
            </div>

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfTitleNewFollower">
                <div class="wpd-opt-name">
                    <label for="ntfTitleNewFollower"><?php esc_html_e($setting["options"]["ntfTitleNewFollower"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfTitleNewFollower"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfTitleNewFollower"]); ?>" name="<?php echo $tab; ?>[ntfTitleNewFollower]" id="ntfTitleNewFollower" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfTitleNewFollower"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfMessageNewFollower">
                <div class="wpd-opt-name">
                    <label for="ntfMessageNewFollower"><?php esc_html_e($setting["options"]["ntfMessageNewFollower"]["label"]); ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfMessageNewFollower"]["description"]); ?></p>
                    <p class="wpd-desc"><?php esc_html_e("Available shortcodes", "wpdiscuz-user-notifications"); ?>:
                    <p class="wpd-desc">
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER]">[NOTIFIER]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_IMG]">[NOTIFIER_AVATAR_IMG]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_URL]">[NOTIFIER_AVATAR_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[DATE]">[DATE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[MARK_READ_URL]">[MARK_READ_URL]</span>
                    </p>
                    <p class="wpd-desc wun-note">
                        <?php esc_html_e("Please do not remove or change <!--wundelete--> and <!--/wundelete--> HTML comments from this text! It's used to remove unnecessary HTML from browser notifications.", "wpdiscuz-user-notifications"); ?>
                    </p>
                </div>
                <div class="wpd-opt-input">
                    <?php wp_editor($setting["values"]->data["ntfMessageNewFollower"], "ntfMessageNewFollower", ["textarea_name" => $tab . "[ntfMessageNewFollower]", "textarea_rows" => 5, "teeny" => true, "media_buttons" => false]); ?>
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfMessageNewFollower"]["docurl"]); ?>
                </div>
            </div>
            <!-- Option end -->

            <div class="wpd-subtitle wun-subtitle" style="margin-top: 20px;">
                <?php esc_html_e("The user's post has received a new rating", "wpdiscuz-user-notifications") ?>
            </div>

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfTitleMyPostRate">
                <div class="wpd-opt-name">
                    <label for="ntfTitleMyPostRate"><?php esc_html_e($setting["options"]["ntfTitleMyPostRate"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfTitleMyPostRate"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfTitleMyPostRate"]); ?>" name="<?php echo $tab; ?>[ntfTitleMyPostRate]" id="ntfTitleMyPostRate" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfTitleMyPostRate"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfMessageMyPostRate">
                <div class="wpd-opt-name">
                    <label for="ntfMessageMyPostRate"><?php esc_html_e($setting["options"]["ntfMessageMyPostRate"]["label"]); ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfMessageMyPostRate"]["description"]); ?></p>
                    <p class="wpd-desc"><?php esc_html_e("Available shortcodes", "wpdiscuz-user-notifications"); ?>:
                    <p class="wpd-desc">
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER]">[NOTIFIER]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_IMG]">[NOTIFIER_AVATAR_IMG]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_URL]">[NOTIFIER_AVATAR_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[POST_TITLE]">[POST_TITLE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[POST_URL]">[POST_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[RATING]">[RATING]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[DATE]">[DATE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[MARK_READ_URL]">[MARK_READ_URL]</span>
                    </p>
                    <p class="wpd-desc wun-note">
                        <?php esc_html_e("Please do not remove or change <!--wundelete--> and <!--/wundelete--> HTML comments from this text! It's used to remove unnecessary HTML from browser notifications.", "wpdiscuz-user-notifications"); ?>
                    </p>
                </div>
                <div class="wpd-opt-input">
                    <?php wp_editor($setting["values"]->data["ntfMessageMyPostRate"], "ntfMessageMyPostRate", ["textarea_name" => $tab . "[ntfMessageMyPostRate]", "textarea_rows" => 5, "teeny" => true, "media_buttons" => false]); ?>
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfMessageMyPostRate"]["docurl"]); ?>
                </div>
            </div>
            <!-- Option end -->

            <div class="wpd-subtitle wun-subtitle" style="margin-top: 20px;">
                <?php esc_html_e("The user has been mentioned", "wpdiscuz-user-notifications") ?>
            </div>

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfTitleMention">
                <div class="wpd-opt-name">
                    <label for="ntfTitleMention"><?php esc_html_e($setting["options"]["ntfTitleMention"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfTitleMention"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfTitleMention"]); ?>" name="<?php echo $tab; ?>[ntfTitleMention]" id="ntfTitleMention" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfTitleMention"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfMessageMention">
                <div class="wpd-opt-name">
                    <label for="ntfMessageMention"><?php esc_html_e($setting["options"]["ntfMessageMention"]["label"]); ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfMessageMention"]["description"]); ?></p>
                    <p class="wpd-desc"><?php esc_html_e("Available shortcodes", "wpdiscuz-user-notifications"); ?>:
                    <p class="wpd-desc">
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER]">[NOTIFIER]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_IMG]">[NOTIFIER_AVATAR_IMG]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_URL]">[NOTIFIER_AVATAR_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_URL]">[COMMENT_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_CONTENT]">[COMMENT_CONTENT]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[DATE]">[DATE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[MARK_READ_URL]">[MARK_READ_URL]</span>
                    </p>
                    <p class="wpd-desc wun-note">
                        <?php esc_html_e("Please do not remove or change <!--wundelete--> and <!--/wundelete--> HTML comments from this text! It's used to remove unnecessary HTML from browser notifications.", "wpdiscuz-user-notifications"); ?>
                    </p>
                </div>
                <div class="wpd-opt-input">
                    <?php wp_editor($setting["values"]->data["ntfMessageMention"], "ntfMessageMention", ["textarea_name" => $tab . "[ntfMessageMention]", "textarea_rows" => 5, "teeny" => true, "media_buttons" => false]); ?>
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfMessageMention"]["docurl"]); ?>
                </div>
            </div>
            <!-- Option end -->

            <div class="wpd-subtitle wun-subtitle" style="margin-top: 20px;">
                <?php esc_html_e("The user's comment has been replied to", "wpdiscuz-user-notifications") ?>
            </div>

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfTitleMyCommentReply">
                <div class="wpd-opt-name">
                    <label for="ntfTitleMyCommentReply"><?php esc_html_e($setting["options"]["ntfTitleMyCommentReply"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfTitleMyCommentReply"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfTitleMyCommentReply"]); ?>" name="<?php echo $tab; ?>[ntfTitleMyCommentReply]" id="ntfTitleMyCommentReply" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfTitleMyCommentReply"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfMessageMyCommentReply">
                <div class="wpd-opt-name">
                    <label for="ntfMessageMyCommentReply"><?php esc_html_e($setting["options"]["ntfMessageMyCommentReply"]["label"]); ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfMessageMyCommentReply"]["description"]); ?></p>
                    <p class="wpd-desc"><?php esc_html_e("Available shortcodes", "wpdiscuz-user-notifications"); ?>:
                    <p class="wpd-desc">
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER]">[NOTIFIER]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_IMG]">[NOTIFIER_AVATAR_IMG]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_URL]">[NOTIFIER_AVATAR_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_URL]">[COMMENT_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_CONTENT]">[COMMENT_CONTENT]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[DATE]">[DATE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[MARK_READ_URL]">[MARK_READ_URL]</span>
                    </p>
                    <p class="wpd-desc wun-note">
                        <?php esc_html_e("Please do not remove or change <!--wundelete--> and <!--/wundelete--> HTML comments from this text! It's used to remove unnecessary HTML from browser notifications.", "wpdiscuz-user-notifications"); ?>
                    </p>
                </div>
                <div class="wpd-opt-input">
                    <?php wp_editor($setting["values"]->data["ntfMessageMyCommentReply"], "ntfMessageMyCommentReply", ["textarea_name" => $tab . "[ntfMessageMyCommentReply]", "textarea_rows" => 5, "teeny" => true, "media_buttons" => false]); ?>
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfMessageMyCommentReply"]["docurl"]); ?>
                </div>
            </div>
            <!-- Option end -->

            <div class="wpd-subtitle wun-subtitle" style="margin-top: 20px;">
                <?php esc_html_e("The user's post has been commented on", "wpdiscuz-user-notifications") ?>
            </div>

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfTitleMyPostComment">
                <div class="wpd-opt-name">
                    <label for="ntfTitleMyPostComment"><?php esc_html_e($setting["options"]["ntfTitleMyPostComment"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfTitleMyPostComment"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfTitleMyPostComment"]); ?>" name="<?php echo $tab; ?>[ntfTitleMyPostComment]" id="ntfTitleMyPostComment" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfTitleMyPostComment"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfMessageMyPostComment">
                <div class="wpd-opt-name">
                    <label for="ntfMessageMyPostComment"><?php esc_html_e($setting["options"]["ntfMessageMyPostComment"]["label"]); ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfMessageMyPostComment"]["description"]); ?></p>
                    <p class="wpd-desc"><?php esc_html_e("Available shortcodes", "wpdiscuz-user-notifications"); ?>:
                    <p class="wpd-desc">
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER]">[NOTIFIER]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_IMG]">[NOTIFIER_AVATAR_IMG]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_URL]">[NOTIFIER_AVATAR_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_URL]">[COMMENT_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_CONTENT]">[COMMENT_CONTENT]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[DATE]">[DATE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[POST_TITLE]">[POST_TITLE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[POST_URL]">[POST_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[MARK_READ_URL]">[MARK_READ_URL]</span>
                    </p>
                    <p class="wpd-desc wun-note">
                        <?php esc_html_e("Please do not remove or change <!--wundelete--> and <!--/wundelete--> HTML comments from this text! It's used to remove unnecessary HTML from browser notifications.", "wpdiscuz-user-notifications"); ?>
                    </p>
                </div>
                <div class="wpd-opt-input">
                    <?php wp_editor($setting["values"]->data["ntfMessageMyPostComment"], "ntfMessageMyPostComment", ["textarea_name" => $tab . "[ntfMessageMyPostComment]", "textarea_rows" => 5, "teeny" => true, "media_buttons" => false]); ?>
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfMessageMyPostComment"]["docurl"]); ?>
                </div>
            </div>
            <!-- Option end -->

            <div class="wpd-subtitle wun-subtitle" style="margin-top: 20px;">
                <?php esc_html_e("The user's subscribed post has been commented on", "wpdiscuz-user-notifications") ?>
            </div>

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfTitleSubscribedPostComment">
                <div class="wpd-opt-name">
                    <label for="ntfTitleSubscribedPostComment"><?php esc_html_e($setting["options"]["ntfTitleSubscribedPostComment"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfTitleSubscribedPostComment"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfTitleSubscribedPostComment"]); ?>" name="<?php echo $tab; ?>[ntfTitleSubscribedPostComment]" id="ntfTitleSubscribedPostComment" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfTitleSubscribedPostComment"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfMessageSubscribedPostComment">
                <div class="wpd-opt-name">
                    <label for="ntfMessageSubscribedPostComment"><?php esc_html_e($setting["options"]["ntfMessageSubscribedPostComment"]["label"]); ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfMessageSubscribedPostComment"]["description"]); ?></p>
                    <p class="wpd-desc"><?php esc_html_e("Available shortcodes", "wpdiscuz-user-notifications"); ?>:
                    <p class="wpd-desc">
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER]">[NOTIFIER]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_IMG]">[NOTIFIER_AVATAR_IMG]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_URL]">[NOTIFIER_AVATAR_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_URL]">[COMMENT_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_CONTENT]">[COMMENT_CONTENT]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[DATE]">[DATE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[POST_TITLE]">[POST_TITLE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[POST_URL]">[POST_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[MARK_READ_URL]">[MARK_READ_URL]</span>
                    </p>
                    <p class="wpd-desc wun-note">
                        <?php esc_html_e("Please do not remove or change <!--wundelete--> and <!--/wundelete--> HTML comments from this text! It's used to remove unnecessary HTML from browser notifications.", "wpdiscuz-user-notifications"); ?>
                    </p>
                </div>
                <div class="wpd-opt-input">
                    <?php wp_editor($setting["values"]->data["ntfMessageSubscribedPostComment"], "ntfMessageSubscribedPostComment", ["textarea_name" => $tab . "[ntfMessageSubscribedPostComment]", "textarea_rows" => 5, "teeny" => true, "media_buttons" => false]); ?>
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfMessageSubscribedPostComment"]["docurl"]); ?>
                </div>
            </div>
            <!-- Option end -->

            <div class="wpd-subtitle wun-subtitle" style="margin-top: 20px;">
                <?php esc_html_e("New comment by following user", "wpdiscuz-user-notifications") ?>
            </div>

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfTitleFollowingUserComment">
                <div class="wpd-opt-name">
                    <label for="ntfTitleFollowingUserComment"><?php esc_html_e($setting["options"]["ntfTitleFollowingUserComment"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfTitleFollowingUserComment"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfTitleFollowingUserComment"]); ?>" name="<?php echo $tab; ?>[ntfTitleFollowingUserComment]" id="ntfTitleFollowingUserComment" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfTitleFollowingUserComment"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfMessageFollowingUserComment">
                <div class="wpd-opt-name">
                    <label for="ntfMessageFollowingUserComment"><?php esc_html_e($setting["options"]["ntfMessageFollowingUserComment"]["label"]); ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfMessageFollowingUserComment"]["description"]); ?></p>
                    <p class="wpd-desc"><?php esc_html_e("Available shortcodes", "wpdiscuz-user-notifications"); ?>:
                    <p class="wpd-desc">
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER]">[NOTIFIER]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_IMG]">[NOTIFIER_AVATAR_IMG]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[NOTIFIER_AVATAR_URL]">[NOTIFIER_AVATAR_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_URL]">[COMMENT_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_CONTENT]">[COMMENT_CONTENT]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[POST_TITLE]">[POST_TITLE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[DATE]">[DATE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[MARK_READ_URL]">[MARK_READ_URL]</span>
                    </p>
                    <p class="wpd-desc wun-note">
                        <?php esc_html_e("Please do not remove or change <!--wundelete--> and <!--/wundelete--> HTML comments from this text! It's used to remove unnecessary HTML from browser notifications.", "wpdiscuz-user-notifications"); ?>
                    </p>
                </div>
                <div class="wpd-opt-input">
                    <?php wp_editor($setting["values"]->data["ntfMessageFollowingUserComment"], "ntfMessageFollowingUserComment", ["textarea_name" => $tab . "[ntfMessageFollowingUserComment]", "textarea_rows" => 5, "teeny" => true, "media_buttons" => false]); ?>
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfMessageFollowingUserComment"]["docurl"]); ?>
                </div>
            </div>
            <!-- Option end -->

            <div class="wpd-subtitle wun-subtitle" style="margin-top: 20px;">
                <?php esc_html_e("The user's comment has been approved", "wpdiscuz-user-notifications") ?>
            </div>

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfTitleMyCommentApprove">
                <div class="wpd-opt-name">
                    <label for="ntfTitleMyCommentApprove"><?php esc_html_e($setting["options"]["ntfTitleMyCommentApprove"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfTitleMyCommentApprove"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfTitleMyCommentApprove"]); ?>" name="<?php echo $tab; ?>[ntfTitleMyCommentApprove]" id="ntfTitleMyCommentApprove" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfTitleMyCommentApprove"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfMessageMyCommentApprove">
                <div class="wpd-opt-name">
                    <label for="ntfMessageMyCommentApprove"><?php esc_html_e($setting["options"]["ntfMessageMyCommentApprove"]["label"]); ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfMessageMyCommentApprove"]["description"]); ?></p>        
                    <p class="wpd-desc"><?php esc_html_e("Available shortcodes", "wpdiscuz-user-notifications"); ?>:
                    <p class="wpd-desc">
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_URL]">[COMMENT_URL]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[COMMENT_CONTENT]">[COMMENT_CONTENT]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[DATE]">[DATE]</span>
                        <span class="wc_available_variable" data-wpd-clipboard="[MARK_READ_URL]">[MARK_READ_URL]</span>
                    </p>
                    <p class="wpd-desc wun-note">
                        <?php esc_html_e("Please do not remove or change <!--wundelete--> and <!--/wundelete--> HTML comments from this text! It's used to remove unnecessary HTML from browser notifications.", "wpdiscuz-user-notifications"); ?>
                    </p>
                </div>
                <div class="wpd-opt-input">
                    <?php wp_editor($setting["values"]->data["ntfMessageMyCommentApprove"], "ntfMessageMyCommentApprove", ["textarea_name" => $tab . "[ntfMessageMyCommentApprove]", "textarea_rows" => 5, "teeny" => true, "media_buttons" => false]); ?>
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfMessageMyCommentApprove"]["docurl"]); ?>
                </div>
            </div>
            <!-- Option end -->

        </div>
    </div>

    <div class="wun-accordion-item">

        <div class="wpd-subtitle fas wun-accordion-title" style="margin-top: 20px;" data-wun-selector="wun-container-texts">
            <p><?php esc_html_e("Notifications' Container Texts", "wpdiscuz-user-notifications") ?></p>
        </div>

        <div class="wun-accordion-content">

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfContainerTitle">
                <div class="wpd-opt-name">
                    <label for="ntfContainerTitle"><?php esc_html_e($setting["options"]["ntfContainerTitle"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfContainerTitle"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfContainerTitle"]); ?>" name="<?php echo $tab; ?>[ntfContainerTitle]" id="ntfContainerTitle" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfContainerTitle"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfLoadMore">
                <div class="wpd-opt-name">
                    <label for="ntfLoadMore"><?php esc_html_e($setting["options"]["ntfLoadMore"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfLoadMore"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfLoadMore"]); ?>" name="<?php echo $tab; ?>[ntfLoadMore]" id="ntfLoadMore" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfLoadMore"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfDeleteAll">
                <div class="wpd-opt-name">
                    <label for="ntfDeleteAll"><?php esc_html_e($setting["options"]["ntfDeleteAll"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfDeleteAll"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfDeleteAll"]); ?>" name="<?php echo $tab; ?>[ntfDeleteAll]" id="ntfDeleteAll" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfDeleteAll"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

            <!-- Option start -->
            <div class="wpd-opt-row" data-wpd-opt="ntfNoNotifications">
                <div class="wpd-opt-name">
                    <label for="ntfNoNotifications"><?php esc_html_e($setting["options"]["ntfNoNotifications"]["label"]) ?></label>
                    <p class="wpd-desc"><?php esc_html_e($setting["options"]["ntfNoNotifications"]["description"]) ?></p>
                </div>
                <div class="wpd-opt-input">
                    <input type="text" value="<?php esc_attr_e($setting["values"]->data["ntfNoNotifications"]); ?>" name="<?php echo $tab; ?>[ntfNoNotifications]" id="ntfNoNotifications" />
                </div>
                <div class="wpd-opt-doc">
                    <?php $wpdiscuz->options->printDocLink($setting["options"]["ntfNoNotifications"]["docurl"]) ?>
                </div>
            </div>
            <!-- Option end -->

        </div>
    </div>
</div>