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
    }

    public function LogController_Render_Before($Sender, $args){
        $Sender->AddJsFile('moderation.js', 'themes/zotero-default');
    }

    public function PostController_Render_Before($Sender, $args){
        //$Sender->AddJsFile('posthelp.js', 'themes/zotero-default');
    }

    public function PostController_BeforeFormInputs_handler($Sender, $args){
        echo '<div id="SuggestedReading" style="display:none;"><p>You may want to read:</p><ul id="suggestedReadingList"></ul></div>';
    }

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

//WriteDiscussion override with views count removed
//overrides applications/vanilla/views/discussions/helper_functions.php::writeDiscussion
if (!function_exists('WriteDiscussion')):
    function writeDiscussion($Discussion, &$Sender, &$Session) {
        $CssClass = CssClass($Discussion);
        $DiscussionUrl = $Discussion->Url;
        $Category = CategoryModel::categories($Discussion->CategoryID);

        if ($Session->UserID)
            $DiscussionUrl .= '#latest';

        $Sender->EventArguments['DiscussionUrl'] = &$DiscussionUrl;
        $Sender->EventArguments['Discussion'] = &$Discussion;
        $Sender->EventArguments['CssClass'] = &$CssClass;

        $First = UserBuilder($Discussion, 'First');
        $Last = UserBuilder($Discussion, 'Last');
        $Sender->EventArguments['FirstUser'] = &$First;
        $Sender->EventArguments['LastUser'] = &$Last;

        $Sender->fireEvent('BeforeDiscussionName');

        $DiscussionName = $Discussion->Name;
        if ($DiscussionName == '')
            $DiscussionName = t('Blank Discussion Topic');

        $Sender->EventArguments['DiscussionName'] = &$DiscussionName;

        static $FirstDiscussion = TRUE;
        if (!$FirstDiscussion)
            $Sender->fireEvent('BetweenDiscussion');
        else
            $FirstDiscussion = FALSE;

        $Discussion->CountPages = ceil($Discussion->CountComments / $Sender->CountCommentsPerPage);
        ?>
        <li id="Discussion_<?php echo $Discussion->DiscussionID; ?>" class="<?php echo $CssClass; ?>">
            <?php
            if (!property_exists($Sender, 'CanEditDiscussions'))
                $Sender->CanEditDiscussions = val('PermsDiscussionsEdit', CategoryModel::categories($Discussion->CategoryID)) && c('Vanilla.AdminCheckboxes.Use');

            $Sender->fireEvent('BeforeDiscussionContent');

            //   WriteOptions($Discussion, $Sender, $Session);
            ?>
            <span class="Options">
      <?php
      echo OptionsList($Discussion);
      echo BookmarkButton($Discussion);
      ?>
   </span>

            <div class="ItemContent Discussion">
                <div class="Title">
                    <?php
                    echo AdminCheck($Discussion, array('', ' ')).
                        anchor($DiscussionName, $DiscussionUrl);
                    $Sender->fireEvent('AfterDiscussionTitle');
                    ?>
                </div>
                <div class="Meta Meta-Discussion">
                    <?php
                    WriteTags($Discussion);
                    ?>
                    
         <span class="MItem MCount CommentCount"><?php
             printf(PluralTranslate($Discussion->CountComments,
                 '%s comment html', '%s comments html', t('%s comment'), t('%s comments')),
                 BigPlural($Discussion->CountComments, '%s comment'));
             ?></span>
         <span class="MItem MCount DiscussionScore Hidden"><?php
             $Score = $Discussion->Score;
             if ($Score == '') $Score = 0;
             printf(Plural($Score,
                 '%s point', '%s points',
                 BigPlural($Score, '%s point')));
             ?></span>
                    <?php
                    echo NewComments($Discussion);

                    $Sender->fireEvent('AfterCountMeta');

                    if ($Discussion->LastCommentID != '') {
                        echo ' <span class="MItem LastCommentBy">'.sprintf(t('Most recent by %1$s'), userAnchor($Last)).'</span> ';
                        echo ' <span class="MItem LastCommentDate">'.Gdn_Format::date($Discussion->LastDate, 'html').'</span>';
                    } else {
                        echo ' <span class="MItem LastCommentBy">'.sprintf(t('Started by %1$s'), userAnchor($First)).'</span> ';
                        echo ' <span class="MItem LastCommentDate">'.Gdn_Format::date($Discussion->FirstDate, 'html');

                        if ($Source = val('Source', $Discussion)) {
                            echo ' '.sprintf(t('via %s'), t($Source.' Source', $Source));
                        }

                        echo '</span> ';
                    }

                    if ($Sender->data('_ShowCategoryLink', true) && c('Vanilla.Categories.Use') && $Category)
                        echo wrap(Anchor(htmlspecialchars($Discussion->Category), CategoryUrl($Discussion->CategoryUrlCode)), 'span', array('class' => 'MItem Category '.$Category['CssClass']));

                    $Sender->fireEvent('DiscussionMeta');
                    ?>
                </div>
            </div>
            <?php $Sender->fireEvent('AfterDiscussionContent'); ?>
        </li>
    <?php
    }
endif;

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
