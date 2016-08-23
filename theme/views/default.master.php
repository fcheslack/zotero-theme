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

$forumNotificationPrefs = "$baseForumsUrl/profile/preferences/{$UserID}/{$userInfo['slug']}";

?>
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="verify-v1" content="IwMu0wZAlDVdRyYXR2iE6fy68J75yL4ZExOpoyPDHdw="/>
        <meta name="keywords" content="Zotero, research, tool, firefox, extension, reference"/>
        <meta name="description" content="Zotero is a powerful, easy-to-use research tool that 
                                          helps you gather, organize, and analyze sources and then 
                                          share the results of your research."/>
        <?$this->RenderAsset('Head')?>
        <!--{asset name="Head"}-->
        <link rel="shortcut icon" type="image/png" sizes="16x16" href="<?=$staticUrl("/images/theme/zotero_theme/zotero_16.png");?>" />
        <?/*<link rel="shortcut icon" type="image/png" sizes="24x24" href="<?=$staticUrl("/images/theme/zotero_theme/zotero_24.png");?>" />
        <link rel="shortcut icon" type="image/png" sizes="48x48" href="<?=$staticUrl("/images/theme/zotero_theme/zotero_48.png")?>" />
        <link rel="apple-touch-icon" type="image/png" href="<?=$staticUrl("/images/theme/zotero_theme/zotero_48.png")?>" />
        <link rel="apple-touch-icon-precomposed" type="image/png" href="<?=$staticUrl("/images/theme/zotero_theme/zotero_48.png")?>" />
        */?>
        <!-- auto discovery links -->
        <link rel="alternate" type="application/rss+xml" title="Zotero Blog" href="http://feeds.feedburner.com/zotero/" />
        
        <!-- css -->
        <!-- both theme_style.css and zotero_icons_sprite.css are included in zorg_style via zorg.less -->
        <!--<link rel="stylesheet" href="<?=$staticUrl("/css/style_reduced.css")?>" type="text/css" media="screen" charset="utf-8"/>-->
        <link rel="stylesheet" href="<?=$staticUrl("/css/zotero_icons_sprite.css")?>" type="text/css" media="screen" charset="utf-8"/>
        <!--<link rel="stylesheet" href="<?=$staticUrl('/css/zorg_style.css');?>" type="text/css" media="screen" charset="utf-8"/> -->
        <link href="/themes/zotero-bootstrap/fonts/glyphicons.css" rel="stylesheet">
        <link href="<?=$staticUrl('/fonts/glyphicons_halflings/css/glyphicons-halflings.css')?>" rel="stylesheet">
        
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
            <?if($outdatedVersion()):?>
                <div id="outdated-version-notification" style="background-color:#FFFECC; text-align:left; border:1px solid #FAEBB1; padding:5px 20px; margin-bottom:10px;">
                <p style="margin: 0; text-align: center;">Your version of Zotero for Firefox is out of date. <a href="https://www.zotero.org/download">Download the latest version.</a></p>
                </div>
                <?error_log($_SERVER['HTTP_X_ZOTERO_VERSION']);?>
            <?endif;?>
            
            <h1 id="logohead">
                <a href="<?=$baseUrl?>/"><img src="<?=$staticUrl('/images/theme/zotero.png')?>" alt="Zotero"></a>
            </h1>
        
        <div id="login-links">
            <? if ($userInfo): ?>
                Welcome, <a href="<?=$profileUrl($userInfo['slug']);?>"><?=htmlspecialchars($displayName)?></a>
                <a href="<?=$settingsUrl?>">Settings</a>
                <a href="<?=$inboxUrl?>">Inbox<?=$CountUnread > 0 ? " ($CountUnread)" : "";?></a>
                <a href="<?=$downloadUrl?>">Download</a>
                <a href="<?=$logoutUrl?>">Log Out</a>
            <? else: ?>
                <a href="<?=$loginUrl?>">Log In</a>
                <a href="<?=$registerUrl?>">Register</a>
            <? endif; ?>
        </div>
        
        <a href="<?=$baseUrl;?>/settings/storage?ref=usb" class="button" id="purchase-storage-link"><img src="<?=$staticUrl('/images/theme/archive.png')?>" /> Upgrade Storage</a>
        
        <div id="navbar" class="container">
            <nav id="sitenav">
                <ul>
                <li ><a href="/">Home</a></li>
                <? if ($userInfo): ?>
                <li ><a href="<?=$libraryUrl($userInfo['slug'])?>">My Library</a></li>
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
                    <input type="text" name="Search" size="20" id="header-search-query"/>
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
                    <?if($userInfo):?>
                        <li><a href="<?=$forumNotificationPrefs?>">Notification Preferences</a></li>
                    <?endif;?>
                    <?if($userIsAdmin):?>
                        <li><a href="/dashboard/settings">Forum Settings</a></li>
                    <?endif;?>
                    <?if($userIsAdmin || $userIsModerator):?>
                        <?$pendingCount = getPendingPostCount();?>
                        <li><a href="/dashboard/log/moderation">Moderation Queue (<?=$pendingCount?>)</a></li>
                        <?if (c('Garden.Email.Disabled')):?>
                            <li>Email is disabled</li>
                        <?endif;?>
                    <?endif;?>
                  </ul>
              </div>
              <?if($userInfo):?>
                  <h2>Discussion Filters</h2>
                  <div class="BoxFilter BoxDiscussionFilter">
                      <ul class="FilterMenu">
                        <li><a href="/discussions/mine">Your Discussions</a></li>
                        <li><a href="/discussions/bookmarked">Your Bookmarks</a></li>
                        <!--<li><a href="/comments/mine">Your Comments</a></li>-->
                        <li><a href="/drafts">Your Drafts</a></li>
                      </ul>
                  </div>
              <?endif;?>
              <?$this->RenderAsset('Panel')?>
              <!--{asset name="Panel"}-->
            </aside>

            <main class="page-content major-col last-col" style="float:right" role="main">
              <?$this->RenderAsset('Content')?>
              <!--{asset name="Content"}-->
            </main>

          </div>
        </div>
    </div>
    
    <footer>
        <div class="center container">
                <img id="chnm-logo" src="<?=$staticUrl('/images/theme/rrchnmlogo-gray.png');?>" alt="Zotero">
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
            <?$this->RenderAsset('Foot')?>
        </div>
    </footer>
    <?$this->FireEvent("AfterBody");?>
    <!--{event name="AfterBody"}-->
  </body>
</html>
