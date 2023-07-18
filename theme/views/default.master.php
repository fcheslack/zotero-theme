<!DOCTYPE html>
<html lang="en" class="no-js"> 
<?php
$baseUrl = Gdn::Config("Zotero.BaseUrl", "");// "//test.zotero.net";
$baseForumsUrl = Gdn::Config("Zotero.BaseForumsUrl", "");
$staticPath = Gdn::Config("Zotero.StaticPath", "");
if (isset($_COOKIE['zoteroUserInfo'])) {
    $userInfo = unserialize($_COOKIE['zoteroUserInfo']);
} else {
    $userInfo = false;
}
$iosApp = false;
if(isset($_COOKIE['iosApp']) && $_COOKIE['iosApp'] == '1') {
	$iosApp = true;
}

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
$storageUrl = $userInfo ? "$baseUrl/settings/storage" : "$baseUrl/storage";
$groupsUrl = "$baseUrl/groups";
$peopleUrl = "$baseUrl/people";
$documentationUrl = "$baseUrl/support";
$getinvolvedUrl = "$baseUrl/getinvolved";
$inboxUrl = "$baseForumsUrl/messages/inbox";
$loginUrl = "$baseUrl/user/login?force=1";
$logoutUrl = "$baseUrl/user/logout";
$registerUrl = "$baseUrl/user/register";
$downloadUrl = "$baseUrl/download";
$dashboardUrl = "$baseForumsUrl/dashboard/settings";

$outdatedVersion = function(){
    $latestVersionParts = [4, 0, 29, 21];
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
$UserID = Gdn::Session()->UserID;
$User = Gdn::UserModel()->GetID($UserID);
if($User) {
    $displayName = !empty($userInfo['realname']) ? $userInfo['realname'] : $userInfo['username'];

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

    $forumNotificationPrefs = $userInfo ? "$baseForumsUrl/profile/preferences/{$UserID}" : false;
    $userCommentsUrl = $userInfo ? "/profile/comments/{$UserID}/{$userInfo['slug']}" : false;
}
?>
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Zotero is a free, easy-to-use tool to help you collect, organize, cite, and share research."/>
        <?php $this->RenderAsset('Head')?>
        <!--{asset name="Head"}-->
        <link rel="shortcut icon" type="image/png" sizes="16x16" href="<?=$staticUrl("/images/theme/zotero_theme/favicon.ico");?>" />
        <!-- auto discovery links -->
        <link rel="alternate" type="application/rss+xml" title="Zotero Blog" href="http://feeds.feedburner.com/zotero/" />
        
        <!-- css -->
        <!-- both theme_style.css and zotero_icons_sprite.css are included in zorg_style via zorg.less -->
        <!--<link rel="stylesheet" href="<?=$staticUrl("/css/style_reduced.css")?>" type="text/css" media="screen" charset="utf-8"/>-->
        <link rel="stylesheet" href="<?=$staticUrl("/css/zotero_icons_sprite.css")?>" type="text/css" media="screen" charset="utf-8"/>
        <!--<link rel="stylesheet" href="<?=$staticUrl('/css/zorg_style.css');?>" type="text/css" media="screen" charset="utf-8"/> -->
        
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body id="<?=$BodyIdentifier?>" class="<?=$this->CssClass?>">
    <!-- Header -->
    <!-- Header -->
    <header role="banner" class="container">
        <div class="center container">
            <?php if($outdatedVersion()):?>
                <div id="outdated-version-notification" style="background-color:#FFFECC; text-align:left; border:1px solid #FAEBB1; padding:5px 20px; margin-bottom:20px;">
                <p style="margin: 0; text-align: center;">Your version of Zotero for Firefox is out of date. <a href="https://www.zotero.org/download">Download the latest version.</a></p>
                </div>
                <?php error_log($_SERVER['HTTP_X_ZOTERO_VERSION']);?>
            <?php endif;?>
            
            <h1 id="logohead">
                <a href="<?=$baseUrl?>/"><img src="<?=$staticUrl('/images/theme/zotero-logo.1519224037.svg')?>" alt="Zotero"></a>
            </h1>
        
        <div id="login-links">
            <?php if ($userInfo): ?>
                Welcome, <a href="<?=$profileUrl($userInfo['slug']);?>"><?=htmlspecialchars($displayName)?></a>
                <a href="<?=$settingsUrl?>">Settings</a>
                <a href="<?=$inboxUrl?>">Inbox<?=$CountUnread > 0 ? " ($CountUnread)" : "";?></a>
                <a href="<?=$downloadUrl?>">Download</a>
                <a href="<?=$logoutUrl?>">Log Out</a>
            <?php else: ?>
                <a href="<?=$loginUrl?>">Log In</a>
                <a href="<?=$registerUrl?>">Register</a>
            <?php endif; ?>
        </div>
        
        <? if(!$iosApp) { ?>
        <a href="<?=$storageUrl;?>" class="button" id="purchase-storage-link"><img src="<?=$staticUrl('/images/theme/archive.png')?>" /> Upgrade Storage</a>
        <? } ?>
        
        <div id="navbar" class="container">
            <nav id="sitenav">
                <ul>
                <li ><a href="<?=$baseUrl?>">Home</a></li>
                <?php if ($userInfo): ?>
                <li ><a href="<?=$libraryUrl($userInfo['slug'])?>">Web Library</a></li>
                <?php endif; ?>
                <li ><a href="<?=$groupsUrl?>">Groups</a></li>
                <li ><a href="<?=$peopleUrl?>">People</a></li>
                <li ><a href="<?=$documentationUrl?>">Documentation</a></li>
                <li class='selected'><a href="<?=$baseForumsUrl?>">Forums</a></li>
                <li ><a href="<?=$getinvolvedUrl?>">Get Involved</a></li>
                </ul>
            </nav>
            <form action="<?=$baseForumsUrl?>/search/" class="zform zsearch" id="simple-search">
                <div>
                    <input type="text" name="Search" size="20" id="header-search-query" placeholder="Search forums"/>
                    <input class="button" type="submit" value="Search">
                </div>
            </form>
        </div>
    </div>
    </header>
    
    <div id="content">
        <div class="center container">
          <div class="row">

            <aside class="page-sidebar minor-col" style="float:left" role="complementary">
              <div class="BoxButtons BoxNewDiscussion">
                  <a href="/post/discussion" class="Button Primary Action NewDiscussion BigButton">New Discussion</a>
              </div>
              
              <h2>Quick Links</h2>
              <div class="BoxFilter BoxQuickLinksFilter">
                  <ul class="FilterMenu">
                    <li><a href="/discussions">Discussions</a></li>
                    <!--<li><a href="/categories">Categories</a></li>-->
                    <li><a href="/search">Search</a></li>
                    <?php if($userInfo):?>
                        <li><a href="<?=$forumNotificationPrefs?>">Notification Preferences</a></li>
                    <?php endif;?>
                    <?php if($userIsAdmin):?>
                        <li><a href="/dashboard/settings">Forum Settings</a></li>
                    <?endif;?>
                    <?php if($userIsAdmin || $userIsModerator):?>
                        <?php $pendingCount = getPendingPostCount();?>
                        <li><a href="/dashboard/log/moderation">Moderation Queue (<?=$pendingCount?>)</a></li>
                        <?php if (c('Garden.Email.Disabled')):?>
                            <li>Email is disabled</li>
                        <?php endif;?>
                    <?php endif;?>
                  </ul>
              </div>
              <?php if($userInfo):?>
                  <h2>Discussion Filters</h2>
                  <div class="BoxFilter BoxDiscussionFilter">
                      <ul class="FilterMenu">
                        <li><a href="/discussions/participated">Participated</a></li>
                        <li><a href="/discussions/mine">Your Discussions</a></li>
                        <li><a href="/discussions/bookmarked">Your Bookmarks</a></li>
                        <!-- <li><a href="<?=$userCommentsUrl?>">Your Comments</a></li> -->
                        <li><a href="/drafts">Your Drafts</a></li>
                      </ul>
                  </div>
              <?php endif;?>
              <?php $this->RenderAsset('Panel')?>
              <!--{asset name="Panel"}-->
            </aside>

            <main class="page-content major-col last-col" style="float:right" role="main">
              <?php $this->RenderAsset('Content')?>
              <!--{asset name="Content"}-->
            </main>

          </div>
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
                    <li><a href="<?=$baseUrl?>/support/privacy">Privacy</a></li>
                    <li><a href="<?=$baseUrl?>/getinvolved/">Get Involved</a></li>
                    <li><a href="<?=$baseUrl?>/jobs">Jobs</a></li>
                    <li><a href="<?=$baseUrl?>/about/">About</a></li>
                </ul>
            </nav>
            <p class="about">Zotero is a project of the <a href="http://digitalscholar.org/">Corporation for Digital Scholarship</a>, a nonprofit organization dedicated to the development of software and services for researchers and cultural heritage institutions.</p>
            <?php $this->RenderAsset('Foot')?>
        </div>
    </footer>
    <?php $this->FireEvent("AfterBody");?>
    <!--{event name="AfterBody"}-->
  </body>
</html>
