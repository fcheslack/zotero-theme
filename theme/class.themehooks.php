<?php
/**
 * Sets config variables on enabling MyThemeName, adds locale data to the view,
 * and adds a respond button to the discussion page.
 */
class zoteroThemeHooks implements Gdn_IPlugin {

    /**
     * Sets some config settings for a modern layout with top-level
     * categories displayed as headings.
     *
     * @return boolean Whether setup was successful.
     */
    public function setup() {
    }

    public function LogController_Render_Before($Sender, $args){
        $Sender->AddJsFile('moderation.js', 'themes/zotero-default');
    }

    public function logModel_AfterRestore_handler($sender, $args){
        //$a = print_r($args, true);
        //error_log($a);
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
                //$moderationEmail = arrayValue('Email.Moderation', $Preferences, Gdn::config('Preferences.Email.Moderation'));
                $moderationEmail = arrayValue('Email.Moderation', $Preferences, false);
                if($moderationEmail){
                    $Email = new Gdn_Email();
                    $Email->subject('Moderation pending on Zotero forums');
                    $Email->to($user);
                    
                    $Message = sprintf(
                        $Story == '' ? t('EmailNotification', "%1\$s\n\n%2\$s") : t('EmailStoryNotification', "%3\$s\n\n%2\$s"),
                        $ActivityHeadline,
                        ExternalUrl($Activity->Route == '' ? '/' : $Activity->Route),
                        $Story
                    );
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
            $msg = '<p>All new user posts are moderated. Your message will appear after it has been approved</p>';
            $msg .= '<p><a href="/discussions">Return to discussions</a></p>';
            $Sender->InformMessage($msg, 'ModerationPending');
        }
    }

    /**
     * Let users with permission choose to receive Moderation notification emails.
     */
    public function profileController_afterPreferencesDefined_handler($Sender) {
        if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            $Sender->Preferences['Notifications']['Email.Moderation'] = t('Notify me when moderation is required.');
        }
    }
}

function getPendingPostCount(){
    $logModel = new LogModel();
    //$count = $logModel->getCountWhere("Operation = 'Pending' AND (RecordType = 'Discussion' OR RecordType = 'Comment')");
    $count = $logModel->getCountWhere(['Operation'=>'Pending', 'RecordType'=>['Discussion', 'Comment']]);
    return $count;
}

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
    $time = $Timestamp;

    $NOW = time();
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

    // today
    if ($sod_now == $sod) {
        if ($time > $NOW - (ONE_MINUTE)) {
            return t('just now');
        } elseif ($time > $NOW - (ONE_HOUR)) {
            $MinutesAgo = ceil($SecondsAgo / 60);
            return sprintf(t('%s minutes ago'), $MinutesAgo);
        }
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
    return ZoteroDiscussionDate($Timestamp);
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

    $Now = time();
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
        $Result = ZoteroRelativeTime($Timestamp);
    } else {
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
            $SecondsOffset = $HourOffset * 3600;
            $Timestamp += $SecondsOffset;
            $Now += $SecondsOffset;
        }

        $Result = strftime($Format, $Timestamp);
    }

    if ($Html) {
        $Result = wrap($Result, 'time', array('title' => strftime($FullFormat, $Timestamp), 'datetime' => gmdate('c', $GmTimestamp)));
    }
    return $Result;
}

// ==== End Custom Date Formatting Functions ====
