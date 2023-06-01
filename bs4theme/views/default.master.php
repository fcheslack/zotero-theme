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
$displayName = (empty($userInfo['realname']) ? $userInfo['username'] : $userInfo['realname']);

$staticUrl = function($path) use ($baseUrl, $staticPath) {
	error_log($baseUrl . $staticPath);
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
$UserID = Gdn::Session()->UserID;
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
$userCommentsUrl = "/profile/comments/{$UserID}/{$userInfo['slug']}";
?>
	<head>
		<!-- Required meta tags -->
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta name="description" content="Zotero is a free, easy-to-use tool to help you collect, organize, cite, and share research."/>

		<?php $this->RenderAsset('Head')?>

		<link rel="stylesheet" href="<?=$staticUrl('/css/bs4/web-components.min.css')?>" type="text/css"/>
		<?php 
		$UAString = $_SERVER['HTTP_USER_AGENT'];
		$os = 'unknown';
		if(stripos($UAString, 'windows') !== false){
			$os = 'windows';
		}
		?>

		<!-- set jsConfig on window.zoteroConfig -->
		<?php //$jsConfig = Zend_Registry::get('jsConfig');?>
		<script type="text/javascript" charset="utf-8" nonce="<?//=Zend_Registry::get('scriptNonce');?>">
			//window.zoteroConfig = <?//=json_encode($jsConfig);?>;
			window.zoteroConfig = {installData:{}};

			if(!window.Zotero){
				window.Zotero = {};
			}
			if(typeof window.zoteroData == 'undefined'){
				window.zoteroData = {};
			}
			<?php // $jsData = Zend_Registry::get('jsData');?>
			<?php if(isset($jsData)):?>
				window.zoteroData = <?=json_encode((object)$jsData);?>;
			<?php endif;?>
		</script>
		<script src="<?=$staticUrl('/js/web-components.min.js')?>"></script>
		<script type="text/javascript" charset="utf-8" nonce="<?//=Zend_Registry::get('scriptNonce');?>">
			WebFont.load({
				custom: {
					families: [
						'AvenirNextLTPro:n3',
						'AvenirNextLTPro:i3',
						'AvenirNextLTPro:n4',
						'AvenirNextLTPro:i4',
						'AvenirNextLTPro:n6',
						'AvenirNextLTPro:i6'
					]
				}
			});
		</script>
	</head>
	<body id="<?=$BodyIdentifier?>" class="<?=$this->CssClass?>">
		<!-- hidden area for possible JS messages -->
		<ul class="messages"></ul>
		<header class="mobile-header d-lg-none d-xl-none">
			<nav>
				<ul class="mobile-nav">
					<li class="active"><a class="nav-link" href="<?=$baseUrl?>">Zotero</a></li>
					<? if ($userInfo):?>
					<li><a class="nav-link" href="<?=$libraryUrl($userInfo['slug'])?>">Web Library</a></li>
					<? endif;?>
					<li><a class="nav-link" href="<?=$groupsUrl?>">Groups</a></li>
					<li><a class="nav-link" href="<?=$documentationUrl?>">Documentation</a></li>
					<li><a class="nav-link" href="<?=$baseForumsUrl?>">Forums</a></li>
					<li><a class="nav-link" href="<?=$getinvolvedUrl?>">Get Involved</a></li>
					<? if (!$userInfo):?>
					<li><a class="nav-link separated" href="<?=$loginUrl?>">Log In</a></li>
					<? else:?>
					<li><a class="nav-link separated" href="<?=$profileUrl($userInfo['slug']);?>"><?=htmlspecialchars($displayName)?></a></li>
					<li><a class="nav-link separated" href="<?=$inboxUrl?>">Inbox<?=$CountUnread > 0 ? " ($CountUnread)" : "";?></a></li>
					<li><a class="nav-link separated" href="<?=$settingsUrl?>">Settings</a></li>
					<li><a class="nav-link" href="<?=$logoutUrl?>">Log Out</a></li>
					<? endif;?>
					<li><a class="nav-link separated" href="<?=$storageUrl?>?ref=usb">Upgrade Storage</a></li>
				</ul>
			</nav>
		</header>
		<div class="nav-cover"></div>
		<div class="site-wrapper">
			<header class="main-header">
				<div class="container">
					<a href="<?=$baseUrl?>" class="brand">
						<img src="<?=$staticUrl('/images/bs4theme/zotero-logo.svg');?>" width="108" height="32" alt="Zotero">
					</a>
					<nav>
						<ul class="main-nav d-none d-lg-flex">
							<? if($userInfo):?>
							<li><a class="nav-link" href="<?=$libraryUrl($userInfo['slug'])?>">Web Library</a></li>
							<? endif;?>
							<li class="nav-item active"><a href="<?=$groupsUrl?>" class="nav-link">Groups</a></li>
							<li class="nav-item"><a href="<?=$documentationUrl?>" class="nav-link">Documentation</a></li>
							<li class="nav-item"><a href="<?=$baseForumsUrl?>" class="nav-link">Forums</a></li>
							<li class="nav-item"><a href="<?=$getinvolvedUrl?>" class="nav-link">Get Involved</a></li>
							<? if(!$userInfo):?>
							<li class="nav-item"><a href="<?=$loginUrl?>" class="nav-link log-in">Log In</a></li>
							<? else:?>
							<div class="btn-group">
								<button type="button" class="nav-link btn btn-link text-truncate user-dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<?=$displayName?>
								</button>
								<div class="dropdown-menu">
								<a class="dropdown-item" href="<?=$profileUrl($userInfo['slug'])?>">My Profile</a>
								<div role="separator" class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?=$inboxUrl?>">Inbox<?=$CountUnread > 0 ? " ({$CountUnread})" : "";?></a>
								<div role="separator" class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?=$settingsUrl?>">Settings</a>
								<a class="dropdown-item" href="<?=$logoutUrl?>">Log Out</a>
								</div>
							</div>
							<? endif;?>
							<li class="nav-item"><a href="<?=$storageUrl?>?ref=usb" class="btn btn-sm btn-secondary upgrade-storage">Upgrade Storage</a></li>
						</ul>
					</nav>
					<button class="btn btn-link nav-toggle d-lg-none">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="18" viewBox="0 0 24 18">
						  <path d="M1,1H23M1,9H23M1,17H23" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2"/>
						</svg>
					</button>
				</div>
			</header>
			<!-- Output content -->
			<div id="content">
				<div class="container">
					<div class="row">
						<div class="col-md-3">
							<aside class="page-sidebar" role="complementary">
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
						</div>
						<div class="col-md-9">
							<main class="page-content" role="main">
								<?php $this->RenderAsset('Content')?>
								<!--{asset name="Content"}-->
							</main>
						</div>
					</div>
				</div>
			</div>
			
			<footer class="main-footer">
				<div class="container">
					<nav>
						<ul class="footer-nav">
							<li class="nav-item"><a href="https://www.zotero.org/support/" class="nav-link">Documentation</a></li>
							<li class="nav-item"><a href="https://forums.zotero.org/" class="nav-link">Forums</a></li>
							<li class="nav-item"><a href="https://www.zotero.org/blog/" class="nav-link">Blog</a></li>
							<li class="nav-item"><a href="https://www.zotero.org/support/privacy" class="nav-link">Privacy</a></li>
							<li class="nav-item"><a href="https://www.zotero.org/getinvolved/" class="nav-link">Get Involved</a></li>
							<li class="nav-item"><a href="https://www.zotero.org/support/dev/start" class="nav-link">Developers</a></li>
							<li class="nav-item"><a href="https://www.zotero.org/jobs" class="nav-link">Jobs</a></li>
						</ul>
						<ul class="social-nav">
							<li class="nav-item follow-us">Follow us</li>
							<li class="nav-item twitter"><a href="https://twitter.com/zotero"><img src="<?=$staticUrl('/images/bs4theme/twitter-icon.svg')?>"></a></li>
							<li class="nav-item fb"><a href="https://www.facebook.com/zotero/"><img src="<?=$staticUrl('/images/bs4theme/fb-icon.svg')?>"></a></li>
							<li class="nav-item rss"><a href="/blog/"><img src="<?=$staticUrl('/images/bs4theme/rss-icon.svg')?>"></a></li>
						</ul>
					</nav>
					<div class="credits">
						<p class="about">Zotero is a project of the <a href="http://digitalscholar.org/">Corporation for Digital Scholarship</a>, a nonprofit organization dedicated to the development of software and services for researchers and cultural heritage institutions.</p>
					</div>
					<?php $this->RenderAsset('Foot')?>
				</div>
			</footer>
			<?php $this->FireEvent("AfterBody");?>
			<!--{event name="AfterBody"}-->
		</div>
	</body>
</html>
<?/*

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
				<div id="outdated-version-notification" style="background-color:#FFFECC; text-align:left; border:1px solid #FAEBB1; padding:5px 20px; margin-bottom:10px;">
				<p style="margin: 0; text-align: center;">Your version of Zotero for Firefox is out of date. <a href="https://www.zotero.org/download">Download the latest version.</a></p>
				</div>
				<?php error_log($_SERVER['HTTP_X_ZOTERO_VERSION']);?>
			<?php endif;?>
			
			<h1 id="logohead">
				<a href="<?=$baseUrl?>/"><img src="<?=$staticUrl('/images/theme/zotero-logo.svg')?>" alt="Zotero"></a>
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
		
		<a href="<?=$baseUrl;?>/settings/storage?ref=usb" class="button" id="purchase-storage-link"><img src="<?=$staticUrl('/images/theme/archive.png')?>" /> Upgrade Storage</a>
		
		<div id="navbar" class="container">
			<nav id="sitenav">
				<ul>
				<li ><a href="<?=$baseUrl?>">Home</a></li>
				<?php if ($userInfo): ?>
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
					<li><a href="<?=$baseUrl?>/support/terms/privacy">Privacy</a></li>
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
*/?>
