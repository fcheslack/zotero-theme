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

    public function base_render_before($Sender){
        unset($Sender->Assets['Panel']['GuestModule']);
        unset($Sender->Assets['Panel']['SignedInModule']);
        unset($Sender->Assets['Panel']['DiscussionFilterModule']);
        unset($Sender->Assets['Panel']['NewDiscussionModule']);
        unset($Sender->Assets['Panel']['CategoriesModule']);
        unset($Sender->Assets['Panel']['BookmarkedModule']);
        if(isset($Sender->ControllerName) && $Sender->ControllerName === 'profilecontroller'){
            unset($Sender->Assets['Panel']['SideMenuModule']);
        }

        if (Gdn::session()->isValid()) {
            Gdn::userModel()->updateVisit(Gdn::session()->UserID);
        }
    }

    public function LogController_Render_Before($Sender, $args){
        $Sender->AddJsFile('moderation.js', 'themes/zotero-default');
    }

    public function PostController_Render_Before($Sender, $args){
        //$Sender->AddJsFile('posthelp.js', 'themes/zotero-default');
    }

    public function discussionController_AuthorInfo_handler($Sender, $args){
        if(!c('Zotero.AddRoleTags', false)){
            return;
        }
        $roleTag = '';

        $authorUserID = $args['Author']->UserID;
        $Roles = Gdn::UserModel()->GetRoles($authorUserID)->ResultArray();
        foreach($Roles as $role){
            if($role['Name'] == 'Administrator'){
                $roleTag = "<span class='Tag RoleTag'>Admin</span>";
            } elseif($role['Name'] == 'Moderator'){
                $roleTag = "<span class='Tag RoleTag'>Moderator</span>";
            }
        }

        echo $roleTag;
    }

    public function discussionController_BeforeDiscussionDisplay_handler($Sender, $args){
        if(!c('Zotero.AddRoleClasses', false)){
            return;
        }

        $authorUserID = $args['Author']->UserID;
        $Roles = Gdn::UserModel()->GetRoles($authorUserID)->ResultArray();
        foreach($Roles as $role){
            $args['CssClass'] .= " Role_{$role['Name']}";
        }
    }

    public function discussionController_BeforeCommentDisplay_handler($Sender, $args){
        if(!c('Zotero.AddRoleClasses', false)){
            return;
        }
        $authorUserID = $args['Author']->UserID;
        $Roles = Gdn::UserModel()->GetRoles($authorUserID)->ResultArray();
        foreach($Roles as $role){
            $args['CssClass'] .= " Role_{$role['Name']}";
        }
    }

    public function PostController_BeforeFormInputs_handler($Sender, $args){
        echo '<div id="SuggestedReading" style="display:none;"><p>You may want to read:</p><ul id="suggestedReadingList"></ul></div>';
    }

    public function logModel_AfterRestore_handler($Sender, $args){
        if($args['Log']['Operation'] == 'Pending'){
            if($args['Log']['RecordType'] == 'Discussion' || $args['Log']['RecordType'] == 'Comment'){
                $userID = $args['Log']['InsertUserID'];
                Gdn::userModel()->setField($userID, 'Verified', 1);

                if($args['Log']['RecordType'] == 'Comment'){
                    //update DateLastComment and LastCommentUserID for discussion
                    $discussionID = $args['Log']['Data']['DiscussionID'];
                    if(!$discussionID){
                        throw new \Exception("No DiscussionID found in Event Log.Data.DiscussionID");
                    }
                    $latestCommentQ = 'SELECT * FROM GDN_Comment AS gc WHERE gc.DiscussionID = ? ORDER BY DateInserted DESC LIMIT 1';
                    $latestComment = Gdn::database()->query($latestCommentQ, [$discussionID])->firstRow();

                    $latestUserID = val('InsertUserID', $latestComment, false);
                    $latestDateInserted = val('DateInserted', $latestComment, false);
                    if($latestUserID == false || $latestDateInserted == false){
                        throw new \Exception("Unexpected value for latestUserID or latestDateInserted");
                    }
                    $updateSql = 'UPDATE GDN_Discussion SET DateLastComment = ?, LastCommentUserID = ? WHERE DiscussionID = ?';
                    Gdn::database()->query($updateSql, [$latestDateInserted, $latestUserID, $discussionID]);
                }
            }
        }
    }

    //when a post goes to moderation after the queue was previously empty, send notifications
    //to moderators
    public function logModel_AfterInsert_handler($Sender, $args){
        return;
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

function signInUrl(){
    return c('Garden.Authenticator.SignInUrl');
}

function registerUrl(){
    return c('Garden.Authenticator.RegisterUrl');
}

//don't actually link to ip search, since it's not optimized
//overrides library/core/functions.render.php:ipAnchor
if (!function_exists('ipanchor')){
    function ipAnchor($IP, $CssClass = '') {
        if ($IP) {
            return htmlspecialchars($IP);
        } else {
            return $IP;
        }
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
            } else {
                //no info provided by browser
                $GuestHourOffset = 0;
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
