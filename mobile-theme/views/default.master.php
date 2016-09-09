<!DOCTYPE html>
<html lang="en" class="no-js"> 
<?
$baseUrl = Gdn::Config("Zotero.BaseUrl", "");// "//test.zotero.net";
$baseForumsUrl = Gdn::Config("Zotero.BaseForumsUrl", "");
$staticPath = Gdn::Config("Zotero.StaticPath", "");
if (isset($_COOKIE['zoteroUserInfo'])) {
    $userInfo = unserialize($_COOKIE['zoteroUserInfo']);
} else {
    $userInfo = false;
}
$displayName = (empty($userInfo['realname']) ? $userInfo['username'] : $userInfo['realname']);

$staticUrl = function($path) use ($baseUrl, $staticPath) {
    return $baseUrl . $staticPath . $path;
};
$profileUrl = function($slug) use ($baseUrl) {
    return "$baseUrl/$slug";
};
$libraryUrl = function($slug) use ($baseUrl) {
    return "$baseUrl/$slug/items";
};
$settingsUrl = "$baseUrl/settings";
$storageUrl = "$baseUrl/settings/storage";
$groupsUrl = "$baseUrl/groups";
$peopleUrl = "$baseUrl/people";
$documentationUrl = "$baseUrl/support";
$getinvolvedUrl = "$baseUrl/getinvolved";
$inboxUrl = "$baseForumsUrl/messages/inbox";
$loginUrl = "$baseUrl/user/login";
$logoutUrl = "$baseUrl/user/logout";
$registerUrl = "$baseUrl/user/register";
$downloadUrl = "$baseUrl/download";
$dashboardUrl = "$baseForumsUrl/dashboard/settings";

$outdatedVersion = function(){
    $latestVersionParts = [4, 0, 26, 1];
    if(!isset($_SERVER['HTTP_X_ZOTERO_VERSION'])){
        return false;
    }
    $versionString = $_SERVER['HTTP_X_ZOTERO_VERSION'];
    $outdatedVersion = false;
    $matches = [];
    preg_match("/^(\d+)\.(\d+)\.?(\d+)?\.?(\d+)?/", $versionString, $matches);
    for($i = 0; $i < count($latestVersionParts); $i++) {
        if(!isset($matches[$i+1])){
            return true;
        }
        if(intval($matches[$i+1]) > $latestVersionParts[$i]) {
            break;
        } elseif(intval($matches[$i+1]) < $latestVersionParts[$i]){
            return true;
        }
    }
    return false;
};

$userIsAdmin = false;
$userIsModerator = false;
$UserID = Gdn::Controller()->Data('Profile.UserID', Gdn::Session()->UserID);
$User = Gdn::UserModel()->GetID($UserID);
$CountUnread = $User->CountUnreadConversations;
$Roles = Gdn::UserModel()->GetRoles($UserID)->ResultArray();
foreach($Roles as $role){
    if($role['Name'] == 'Administrator'){
        $userIsAdmin = true;
    } elseif($role['Name'] == 'Moderator'){
        $userIsModerator = true;
    }
}
if(count($Roles) == 0){
    $userInfo = false;
}

$forumNotificationPrefs = "$baseForumsUrl/profile/preferences/{$UserID}/{$userInfo['slug']}";

?>
<head>
    <?$this->RenderAsset('Head')?>
    <!-- {asset name='Head'} -->
</head>
<body id="<?=$BodyIdentifier?>" class="<?=$this->CssClass?>">
<!-- <body id="{$BodyID}" class="{$BodyClass}"> -->
<div id="Frame">
    <div class="Banner">
        <a href="<?=$baseUrl?>/"><img id="logo" src="<?=$staticUrl('/images/theme/zotero_theme/zotero_32.png')?>" /></a>
        <ul>
            <? if ($userInfo): ?>
                <li><a href="<?=$profileUrl($userInfo['slug']);?>"><?=htmlspecialchars($displayName)?></a></li>
                <li><a href="<?=$settingsUrl?>">Settings</a></li>
                <li><a href="<?=$inboxUrl?>">Inbox<?=$CountUnread > 0 ? " ($CountUnread)" : "";?></a></li>
                <li><a href="<?=$downloadUrl?>">Download</a></li>
                <li><a href="<?=$logoutUrl?>">Log Out</a></li>
            <? else: ?>
                <li><a href="<?=$loginUrl?>">Log In</a></li>
                <li><a href="<?=$registerUrl?>">Register</a></li>
            <? endif; ?>
        </ul>
    </div>
    <div id="Body">
        <!-- <div class="BreadcrumbsWrapper">
            {breadcrumbs homelink="0"}
        </div> -->
        <div id="content">
            <?$this->RenderAsset('Content')?>
            <!-- {asset name="Content"} -->
        </div>
    </div>
    <footer>
        <div class="center container">
            <nav role="secondary">                 
                <ul>
                    <!-- <li><a href="#">Give Us Feedback</a></li> -->
                    <li><a href="<?=$baseUrl?>/blog/">Blog</a></li>
                    <li><a href="<?=$baseForumsUrl?>/categories/">Forums</a></li>
                    <li><a href="<?=$baseUrl?>/support/dev/start">Developers</a></li>
                    <li><a href="<?=$baseUrl?>/support/">Documentation</a></li>
                    <li><a href="<?=$baseUrl?>/support/terms/privacy">Privacy</a></li>
                    <li><a href="<?=$baseUrl?>/getinvolved/">Get Involved</a></li>
                    <li><a href="<?=$baseUrl?>/jobs">Jobs</a></li>
                    <li><a href="<?=$baseUrl?>/about/">About</a></li>
                </ul>
            </nav>
            <p>
                Zotero is a project of the <a href="http://chnm.gmu.edu">Roy
                Rosenzweig Center for History and New Media</a>, and was initially funded
                 by the <a href="http://mellon.org">Andrew W. Mellon
                Foundation</a>, the <a href="http://imls.gov">Institute of
                Museum and Library Services</a>, and the <a
                href="http://sloan.org">Alfred P. Sloan Foundation</a>.
            </p>
            <img id="chnm-logo" src="<?=$staticUrl('/images/theme/rrchnmlogo-gray.png');?>" alt="Zotero">
            <?//$this->RenderAsset('Foot')?>
        </div>
    </footer>
</div>
<?$this->FireEvent("AfterBody");?>
<!-- {event name="AfterBody"} -->
</body>
</html>
