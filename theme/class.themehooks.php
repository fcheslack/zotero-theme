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

    public function PostController_Render_Before($Sender, $args){
        //$Sender->AddJsFile('posthelp.js', 'themes/zotero-default');
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
    ZDebug("ZoteroRelativeTime: $Timestamp");
    $secondsOffset = SecondsOffset();
    $time = $Timestamp + $secondsOffset;
    $timeObj = DateTimeImmutable::createFromFormat('U', $time);
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
        return sprintf(t('yesterday at %s'), date('g:ia', $time));
        //return t('1 day ago');
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
    /*
    $Format = t('Date.DefaultFormat', '%B %e, %Y');
    return strftime($Format, $Timestamp);
    */
    //switch to format supported by php DateTimeInterface::format
    $Format = "F j, Y";
    return $timeObj->format($Format);
}

function formatDateCustom($Timestamp = '', $Format = '') {
    ZDebug("formatDateCustom: $Timestamp : $Format");
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
    ZDebug("Creating DateTime from $Timestamp");
    $timeObj = DateTimeImmutable::createFromFormat('U', $time);
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
            //switch to format supported by php DateTimeInterface::format
            $Format = "F j, Y";
        }
    }

    $FullFormat = t('Date.DefaultDateTimeFormat', '%c');
    $FullFormat = 'r'; //switch to format supported by php DateTimeInterface::format

    //return a relative time string if within 30 days
    if(($Now - $Timestamp) < (ONE_DAY * 30)){
        $Result = ZoteroRelativeTime($GmTimestamp);
    } else {
        // $Result = strftime($Format, $Timestamp);
        $Result = $timeObj->format($Format);
    }

    //for html, wrap the formatted string in an html <time> tag with the full datetime
    if ($Html) {
        // $Result = wrap($Result, 'time', array('title' => strftime($FullFormat, $time), 'datetime' => date('c', $time)));
        $Result = wrap($Result, 'time', array('title' => $timeObj->format($FullFormat), 'datetime' => date('c', $time)));
    }
    return $Result;
}

// ==== End Custom Date Formatting Functions ====

//custom WriteComment function so we can change the ID for the comment that we pull up as
//an endorsed comment and it doesn't hijack the permalink
// replaces WriteComment from /application/vanilla/views/discussion/helper_functions.php
/**
 * Outputs a formatted comment.
 *
 * Prior to 2.1, this also output the discussion ("FirstComment") to the browser.
 * That has moved to the discussion.php view.
 *
 * @param DataSet $Comment .
 * @param Gdn_Controller $Sender .
 * @param Gdn_Session $Session .
 * @param int $CurrentOffet How many comments into the discussion we are (for anchors).
 */
if (!function_exists('WriteComment')) {
    function writeComment($Comment, $Sender, $Session, $CurrentOffset) {
        static $UserPhotoFirst = NULL;
        if ($UserPhotoFirst === null) {
            $UserPhotoFirst = c('Vanilla.Comment.UserPhotoFirst', true);
        }
        $Author = Gdn::userModel()->getID($Comment->InsertUserID); //UserBuilder($Comment, 'Insert');
        $Permalink = val('Url', $Comment, '/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID);

        // Set CanEditComments (whether to show checkboxes)
        if (!property_exists($Sender, 'CanEditComments')) {
            $Sender->CanEditComments = $Session->checkPermission('Vanilla.Comments.Edit', TRUE, 'Category', 'any') && c('Vanilla.AdminCheckboxes.Use');
        }

        // Prep event args
        $CssClass = CssClass($Comment, $CurrentOffset);
        $Sender->EventArguments['Comment'] = &$Comment;
        $Sender->EventArguments['Author'] = &$Author;
        $Sender->EventArguments['CssClass'] = &$CssClass;
        $Sender->EventArguments['CurrentOffset'] = $CurrentOffset;
        $Sender->EventArguments['Permalink'] = $Permalink;

        // DEPRECATED ARGUMENTS (as of 2.1)
        $Sender->EventArguments['Object'] = &$Comment;
        $Sender->EventArguments['Type'] = 'Comment';

        // First comment template event
        $Sender->fireEvent('BeforeCommentDisplay'); ?>
        <li class="<?php echo $CssClass; ?>" id="<?php echo 'Comment_'.$Comment->CommentID; ?>">
            <div class="Comment">
                <?php
                // Write a stub for the latest comment so it's easy to link to it from outside.
                if ($CurrentOffset == Gdn::controller()->data('_LatestItem')) {
                    echo '<span id="latest"></span>';
                }
                ?>
                <div class="Options">
                    <?php WriteCommentOptions($Comment); ?>
                </div>
                <?php $Sender->fireEvent('BeforeCommentMeta'); ?>
                <div class="Item-Header CommentHeader">
                    <div class="AuthorWrap">
                        <span class="Author">
                            <?php
                            if ($UserPhotoFirst) {
                                echo userPhoto($Author);
                                echo userAnchor($Author, 'Username');
                            } else {
                                echo userAnchor($Author, 'Username');
                                echo userPhoto($Author);
                            }
                            echo FormatMeAction($Comment);
                            $Sender->fireEvent('AuthorPhoto');
                            ?>
                        </span>
                        <span class="AuthorInfo">
                            <?php
                            if (val('Title', $Author)) {
                                echo ' '.WrapIf(htmlspecialchars(val('Title', $Author)), 'span', array('class' => 'MItem AuthorTitle'));
                            }
                            if (val('Location', $Author)) {
                                echo ' '.WrapIf(htmlspecialchars(val('Location', $Author)), 'span', array('class' => 'MItem AuthorLocation'));
                            }
                            $Sender->fireEvent('AuthorInfo');
                            ?>
                        </span>
                    </div>
                    <div class="Meta CommentMeta CommentInfo">
                        <span class="MItem DateCreated">
                        <?php echo anchor(Gdn_Format::date($Comment->DateInserted, 'html'), $Permalink, 'Permalink', array('name' => 'Item_'.($CurrentOffset), 'rel' => 'nofollow')); ?>
                        </span>
                        <?php
                        echo DateUpdated($Comment, array('<span class="MItem">', '</span>'));
                        ?>
                        <?php
                        // Include source if one was set
                        if ($Source = val('Source', $Comment)) {
                            echo wrap(sprintf(t('via %s'), t($Source.' Source', $Source)), 'span', array('class' => 'MItem Source'));
                        }

                        $Sender->fireEvent('CommentInfo');
                        $Sender->fireEvent('InsideCommentMeta'); // DEPRECATED
                        $Sender->fireEvent('AfterCommentMeta'); // DEPRECATED

                        // Include IP Address if we have permission
                        if ($Session->checkPermission('Garden.PersonalInfo.View')) {
                            echo wrap(IPAnchor($Comment->InsertIPAddress), 'span', array('class' => 'MItem IPAddress'));
                        }
                        ?>
                    </div>
                </div>
                <div class="Item-BodyWrap">
                    <div class="Item-Body">
                        <div class="Message">
                            <?php
                            echo FormatBody($Comment);
                            ?>
                        </div>
                        <?php
                        $Sender->fireEvent('AfterCommentBody');
                        WriteReactions($Comment);
                        if (val('Attachments', $Comment)) {
                            WriteAttachments($Comment->Attachments);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </li>
        <?php
        $Sender->fireEvent('AfterComment');
    }
}
