<?php if (!defined('APPLICATION')) exit();

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

// ==== Begin unmodified default discussions index view ====

$Session = Gdn::Session();
include_once $this->FetchViewLocation('helper_functions', 'discussions', 'vanilla');

echo '<h1 class="H HomepageTitle">'.
   AdminCheck(NULL, array('', ' ')).
   $this->Data('Title').
   '</h1>';

if ($Description = $this->Description()) {
   echo Wrap($Description, 'div', array('class' => 'P PageDescription'));
}

include $this->FetchViewLocation('Subtree', 'Categories', 'Vanilla');


$PagerOptions = array('Wrapper' => '<span class="PagerNub">&#160;</span><div %1$s>%2$s</div>', 'RecordCount' => $this->Data('CountDiscussions'), 'CurrentRecords' => $this->Data('Discussions')->NumRows());
if ($this->Data('_PagerUrl'))
   $PagerOptions['Url'] = $this->Data('_PagerUrl');

echo '<div class="PageControls Top">';
   PagerModule::Write($PagerOptions);
   //echo Gdn_Theme::Module('NewDiscussionModule', $this->Data('_NewDiscussionProperties', array('CssClass' => 'Button Action Primary')));
echo '</div>';

if ($this->DiscussionData->NumRows() > 0 || (isset($this->AnnounceData) && is_object($this->AnnounceData) && $this->AnnounceData->NumRows() > 0)) {
?>
<ul class="DataList Discussions">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php
   
echo '<div class="PageControls Bottom">';
   PagerModule::Write($PagerOptions);
   //echo Gdn_Theme::Module('NewDiscussionModule', $this->Data('_NewDiscussionProperties', array('CssClass' => 'Button Action Primary')));
echo '</div>';

} else {
   ?>
   <div class="Empty"><?php echo T('No discussions were found.'); ?></div>
   <?php
}
