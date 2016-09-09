<?php
/**
 * Mobile Theme hooks.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Mobile Theme
 * @since 2.0
 */

/**
 * Customizations for the mobile theme.
 */
class MobileThemeHooks implements Gdn_IPlugin {

    /**
     * No setup required.
     */
    public function setup() {
    }

    /* Begin Zotero specific themehooks */
    public function logModel_AfterRestore_handler($sender, $args){
        if($args['Log']['Operation'] == 'Pending'){
            if($args['Log']['RecordType'] == 'Discussion' || $args['Log']['RecordType'] == 'Comment'){
                $userID = $args['Log']['InsertUserID'];
                Gdn::userModel()->setField($userID, 'Verified', 1);
            }
        }
    }

    //when a post goes to moderation after the queue was previously empty, send notifications
    //to moderators
    public function logModel_AfterInsert_handler($Sender, $args){
        $count = getPendingPostCount();
        if($count == 1){
            //get moderator users
            $moderators = Gdn::userModel()->getByRole('Moderator')->resultArray();
            $admins = Gdn::userModel()->getByRole('Administrator')->resultArray();

            $modUsers = array_merge($admins, $moderators);

            foreach($modUsers as $user){
                Gdn::userModel()->setCalculatedFields($user);
                $Preferences = val('Preferences', $user);
                $moderationEmail = arrayValue('Email.Moderation', $Preferences, false);
                if($moderationEmail){
                    $Email = new Gdn_Email();
                    $Email->subject('Moderation pending on Zotero forums');
                    $Email->to($user);
                    $Email->message('A new post is awaiting moderator approval on the Zotero forums.');

                    try {
                        $Email->send();
                    } catch (phpmailerException $pex) {
                        if ($pex->getCode() == PHPMailer::STOP_CRITICAL) {
                            error_log('Failure sending moderation email');
                        } else {
                            error_log('Error sending moderation email');
                        }
                    } catch (Exception $ex) {
                        error_log('Failure sending moderation email');
                    }
                }
            }
        }
    }

    public function discussionController_BeforeCommentRender_handler($Sender, $args) {
        //if the comment has gone to moderation, it hasn't been created as an actual comment yet, only an
        //activity log, so the event arguments will not have 'Comment' set.
        if(!isset($args['Comment'])){
            $msg = '<p>Posts from new users are moderated. Your message will appear after it has been approved.</p>';
            $msg .= '<p><a href="/discussions">Return to discussions</a></p>';
            $Sender->InformMessage($msg, 'ModerationPending');
        }
    }

    /* End Zotero specific themehooks */

    /**
     * Remove plugins that are not mobile friendly!
     */
    public function gdn_dispatcher_afterAnalyzeRequest_handler($Sender) {
        // Remove plugins so they don't mess up layout or functionality.
        $inPublicDashboard = ($Sender->application() == 'dashboard' && in_array($Sender->controller(), array('Activity', 'Profile', 'Search')));
        if (in_array($Sender->application(), array('vanilla', 'conversations')) || $inPublicDashboard) {
            Gdn::pluginManager()->removeMobileUnfriendlyPlugins();
        }
        saveToConfig('Garden.Format.EmbedSize', '240x135', false);
    }

    /**
     * Add mobile meta info. Add script to hide iPhone browser bar on pageload.
     */
    public function base_render_before($Sender) {
        if (isMobile() && is_object($Sender->Head)) {
            $Sender->Head->addTag('meta', array('name' => 'viewport', 'content' => "width=device-width,minimum-scale=1.0,maximum-scale=1.0"));
            $Sender->Head->addString('
<script type="text/javascript">
   // If not looking for a specific comment, hide the address bar in iphone
   var hash = window.location.href.split("#")[1];
   if (typeof(hash) == "undefined") {
      setTimeout(function () {
        window.scrollTo(0, 1);
      }, 1000);
   }
</script>');
        }
    }

    /**
     * Add button, remove options, increase click area on discussions list.
     */
    public function categoriesController_render_before($Sender) {
        $Sender->ShowOptions = false;
        saveToConfig('Vanilla.AdminCheckboxes.Use', false, false);
        $this->addButton($Sender, 'Discussion');
        $this->discussionsClickable($Sender);
    }

    /**
     * Add button, remove options, increase click area on discussions list.
     */
    public function discussionsController_render_before($Sender) {
        $Sender->ShowOptions = false;
        saveToConfig('Vanilla.AdminCheckboxes.Use', false, false);
        $this->addButton($Sender, 'Discussion');
        $this->discussionsClickable($Sender);
    }

    /**
     * Add New Discussion button.
     */
    public function discussionController_render_before($Sender) {
        $this->addButton($Sender, 'Discussion');
    }

    /**
     * Add New Discussion button.
     */
    public function draftsController_render_before($Sender) {
        $this->addButton($Sender, 'Discussion');
    }

    /**
     * Add New Conversation button.
     */
    public function messagesController_render_before($Sender) {
        $this->addButton($Sender, 'Conversation');
    }

    /**
     * Add New Discussion button.
     */
    public function postController_render_before($Sender) {
        $this->addButton($Sender, 'Discussion');
    }

    /**
     * Add a button to the navbar.
     */
    private function addButton($Sender, $ButtonType) {
        if (is_object($Sender->Menu)) {
            if ($ButtonType == 'Discussion') {
                $Sender->Menu->addLink(
                    'NewDiscussion',
                    img('themes/mobile/design/images/new.png', array('alt' => t('New Discussion'))),
                    '/post/discussion'.(array_key_exists('CategoryID', $Sender->Data) ? '/'.$Sender->Data['CategoryID'] : ''),
                    array('Garden.SignIn.Allow'),
                    array('class' => 'NewDiscussion')
                );
            } elseif ($ButtonType == 'Conversation')
                $Sender->Menu->addLink(
                    'NewConversation',
                    img('themes/mobile/design/images/new.png', array('alt' => t('New Conversation'))),
                    '/messages/add',
                    '',
                    array('class' => 'NewConversation')
                );
        }
    }

    /**
     * Increases clickable area on a discussions list.
     */
    private function discussionsClickable($Sender) {
        // Make sure that discussion clicks (anywhere in a discussion row) take the user to the discussion.
        if (property_exists($Sender, 'Head') && is_object($Sender->Head)) {
            $Sender->Head->addString('
<script type="text/javascript">
   jQuery(document).ready(function($) {
      $("ul.DataList li.Item").click(function() {
         var href = $(this).find(".Title a").attr("href");
         if (typeof href != "undefined")
            document.location = href;
      });
   });
</script>');
        }
    }

    /**
     * Add the user photo before the user Info on the profile page.
     */
    public function profileController_beforeUserInfo_handler($Sender) {
        $UserPhoto = new UserPhotoModule();
        echo $UserPhoto->toString();
    }
}

/* BEGIN ZOTERO SPECIFIC OVERRIDES */

/**
 * Output comment form.
 * Overrides writeCommentForm in applications/vanilla/views/discussion/helper_functions.php
 *
 * @since 2.1
 */
if (!function_exists('WriteCommentForm')){
    function writeCommentForm() {
        $Session = Gdn::session();
        $Controller = Gdn::controller();

        $Discussion = $Controller->data('Discussion');
        $PermissionCategoryID = val('PermissionCategoryID', $Discussion);
        $UserCanClose = $Session->checkPermission('Vanilla.Discussions.Close', TRUE, 'Category', $PermissionCategoryID);
        $UserCanComment = $Session->checkPermission('Vanilla.Comments.Add', TRUE, 'Category', $PermissionCategoryID);

        // Closed notification
        if ($Discussion->Closed == '1') {
            ?>
            <div class="Foot Closed">
                <div class="Note Closed"><?php echo t('This discussion has been closed.'); ?></div>
                <?php //echo anchor(t('All Discussions'), 'discussions', 'TabLink'); ?>
            </div>
        <?php
        } else if (!$UserCanComment) {
            if (!Gdn::session()->isValid()) {
                ?>
                <div class="Foot Closed">
                    <div class="Note Closed SignInOrRegister"><?php
                        $Popup = (c('Garden.SignIn.Popup')) ? ' class="Popup"' : '';
                        $ReturnUrl = Gdn::request()->PathAndQuery();
                        echo formatString(
                            t('Sign In or Register to Comment.', '<a href="{SignInUrl,html}">Sign In</a> or <a href="{RegisterUrl,html}">Register</a> to comment.'),
                            array(
                                'SignInUrl' => Gdn::Config("Zotero.BaseUrl", "") . "/user/login",
                                'RegisterUrl' => Gdn::Config("Zotero.BaseUrl", "") . "/user/register",
                            )
                        ); ?>
                    </div>
                    <?php //echo anchor(t('All Discussions'), 'discussions', 'TabLink'); ?>
                </div>
            <?php
            }
        }

        if (($Discussion->Closed == '1' && $UserCanClose) || ($Discussion->Closed == '0' && $UserCanComment))
            echo $Controller->fetchView('comment', 'post', 'vanilla');
    }
}


// ==== Begin Custom Date Formatting Functions ====
function SecondsOffset() {
    // Alter the timestamp based on the user's hour offset
    $Session = Gdn::session();
    $HourOffset = 0;

    if ($Session->UserID > 0) {
        $HourOffset = $Session->User->HourOffset;
    } elseif (class_exists('DateTimeZone')) {
        if (!isset($GuestHourOffset)) {
            $GuestTimeZone = c('Garden.GuestTimeZone');
            if ($GuestTimeZone) {
                try {
                    $TimeZone = new DateTimeZone($GuestTimeZone);
                    $Offset = $TimeZone->getOffset(new DateTime('now', new DateTimeZone('UTC')));
                    $GuestHourOffset = floor($Offset / 3600);
                } catch (Exception $Ex) {
                    $GuestHourOffset = 0;
                    // Do nothing, but don't set the timezone.
                    logException($Ex);
                }
            }
        }
        $HourOffset = $GuestHourOffset;
    }

    if ($HourOffset <> 0) {
        return $HourOffset * 3600;
    }

    return 0;
}
/**
 * Show times relative to now
 *
 * e.g. "4 hours ago"
 *
 * Credit goes to: http://byteinn.com/res/426/Fuzzy_Time_function/
 *
 * @param int optional $Timestamp, otherwise time() is used
 * @return string
 */
function ZoteroRelativeTime($Timestamp = null) {
    $secondsOffset = SecondsOffset();
    $time = $Timestamp + $secondsOffset;
    $NOW = time() + $secondsOffset;

    if (!defined('ONE_MINUTE')) {
        define('ONE_MINUTE', 60);
    }
    if (!defined('ONE_HOUR')) {
        define('ONE_HOUR', 3600);
    }
    if (!defined('ONE_DAY')) {
        define('ONE_DAY', 86400);
    }
    
    $SecondsAgo = $NOW - $time;

    // sod = start of day :)
    $sod = mktime(0, 0, 0, date('m', $time), date('d', $time), date('Y', $time));
    $sod_now = mktime(0, 0, 0, date('m', $NOW), date('d', $NOW), date('Y', $NOW));

    // < 4 hours ago
    if ($time > $NOW - (ONE_MINUTE)) {
        return t('just now');
    } elseif ($time > $NOW - (ONE_HOUR)) {
        $MinutesAgo = ceil($SecondsAgo / 60);
        return sprintf(t('%s minutes ago'), $MinutesAgo);
    } elseif ($time > $NOW - (ONE_HOUR * 4)) {
        $HoursAgo = ($SecondsAgo / 60) /60;
        if($HoursAgo < 1.5) {
            return t('1 hour ago');
        } else {
            return sprintf(t('%s hours ago'), ceil($HoursAgo) );
        }
    }
    // > 4 hour ago, but still today
    if ($sod_now == $sod) {
        return sprintf(t('today at %s'), date('g:ia', $time));
    }

    // yesterday
    if (($sod_now - $sod) <= ONE_DAY) {
        if (date('i', $time) > (ONE_MINUTE + 30)) {
            $time += ONE_HOUR / 2;
        }
        return t('1 day ago');
    }

    //within 30 days
    if (($sod_now - $sod) <= (ONE_DAY * 30)) {
        for($i = (ONE_DAY), $d = 1; $i < (ONE_DAY * 30); $i += ONE_DAY, $d++) {
            if (($sod_now - $sod) <= $i) {
                return sprintf(t('%d days ago'), $d);
            }
        }
    }

    //more than 30 days, just print the date
    $Format = t('Date.DefaultFormat', '%B %e, %Y');
    return strftime($Format, $Timestamp);
}

function formatDateCustom($Timestamp = '', $Format = '') {
    static $GuestHourOffset;

    if ($Timestamp === null) {
        return T('Null Date', '-');
    }

    if (!$Timestamp) {
        $Timestamp = time(); // return '&#160;'; Apr 22, 2009 - found a bug where "Draft Saved At X" returned a nbsp here instead of the formatted current time.
    }
    $GmTimestamp = $Timestamp;
    $secondsOffset = SecondsOffset();
    $time = $Timestamp + $secondsOffset;
    $Now = time() + $secondsOffset;

    if (!defined('ONE_DAY')) {
        define('ONE_DAY', 86400);
    }
    
    $Html = false;
    if (strcasecmp($Format, 'html') == 0) {
        $Format = '';
        $Html = true;
    }

    if ($Format == '') {
        // If the timestamp was during the current day
        if (date('Y m d', $Timestamp) == date('Y m d', $Now)) {
            // Use the time format
            $Format = t('Date.DefaultTimeFormat', '%l:%M%p');
        } else {
            // Otherwise, use the date format
            $Format = t('Date.DefaultFormat', '%B %e, %Y');
        }
    }

    $FullFormat = t('Date.DefaultDateTimeFormat', '%c');

    // Emulate %l and %e for Windows.
    if (strpos($Format, '%l') !== false) {
        $Format = str_replace('%l', ltrim(strftime('%I', $Timestamp), '0'), $Format);
    }
    if (strpos($Format, '%e') !== false) {
        $Format = str_replace('%e', ltrim(strftime('%d', $Timestamp), '0'), $Format);
    }

    //return a relative time string if within 30 days
    if(($Now - $Timestamp) < (ONE_DAY * 30)){
        $Result = ZoteroRelativeTime($GmTimestamp);
    } else {
        $Result = strftime($Format, $Timestamp);
    }

    if ($Html) {
        $Result = wrap($Result, 'time', array('title' => strftime($FullFormat, $time), 'datetime' => date('c', $time)));
    }
    return $Result;
}

// ==== End Custom Date Formatting Functions ====

/* END ZOTERO SPECIFIC OVERRIDES */