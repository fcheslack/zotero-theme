<?php
// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
// Define the plugin:
$PluginInfo['zotero'] = [
	'Name' => 'Zotero plugin',
	'Description' => '',
	'Version' => '0.1',
	'Author' => "Faolan Cheslack-Postava",
	'AuthorEmail' => '',
	'AuthorUrl' => '',
	'MobileFriendly' => true,
];

class ZoteroPlugin extends Gdn_Plugin {
	/**
	 * Plugin setup
	 *
	 * This method is fired only once, immediately after the plugin has been enabled in the /plugins/ screen,
	 * and is a great place to perform one-time setup tasks, such as database structure changes,
	 * addition/modification of config file settings, filesystem changes, etc.
	 */
	/*
	public function setup() {
		// Set up the plugin's default values
		saveToConfig('Preferences.Email.Moderation', 1);

		// Trigger database changes
		$this->structure();
	}

	public function structure(){
		//noop
	}
	*/
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

		/*
		if (Gdn::session()->isValid()) {
			Gdn::userModel()->updateVisit(Gdn::session()->UserID);
		}
		*/
	}

	//add the vanilla search SearchModel when our overridden model fires the event
	//the core Vanilla app registers a 'searchModel_Search_Handler'
	public function zoterosearchModel_Search_Handler($Sender) {
		$SearchModel = new VanillaSearchModel();
		$SearchModel->Search($Sender);
	}

	public function discussionController_BeforeDiscussionRender_handler($Sender, $args) {
		//add image-upload JS to single discussion view page for comment form
		if (!$this->userIsIn(c('Zotero.previewFeatureUserIDs', []))) {
			return;
		}
		$Sender->addJsFile('common.js');
		$Sender->addJsFile('image-upload.js');

		//add posthelp javascript for content based feedback on comment form
		$Sender->addJsFile('posthelp.js');

		//get endorsed comments for rendering at top
		$CommentModel = new CommentModel();
		$Endorsed = $CommentModel->GetWhere(['DiscussionID' => $Sender->Data('Discussion.DiscussionID'), 'Endorsed' => 1])->Result();
  
		$Sender->SetData('Endorsed', $Endorsed);
	}

	//add image-upload JS to new discussion page
	public function postController_BeforeDiscussionRender_Handler($Sender) {
		if (!$this->userIsIn(c('Zotero.previewFeatureUserIDs', []))) {
			return;
		}
		$Sender->addJsFile('common.js', 'plugins/Zotero');
		$Sender->addJsFile('image-upload.js', 'plugins/Zotero');

		//add posthelp javascript for content based feedback on new discussion form
		$Sender->addJsFile('posthelp.js');
	}

	public function discussionController_AuthorInfo_handler($Sender, $args) {
		//Add role tag to specific users, adding a span with a label after their forum username
		//in the heading of all discussion/comment posts
		if(!c('Zotero.AddRoleTags', false)){
			return;
		}
		if (!$this->userIsIn(c('Zotero.previewFeatureUserIDs', []))) {
			return;
		}

		$authorUserID = $args['Author']->UserID;
		$userTag = '';
		$userRoleMap = c('Zotero.userRoleMap', []);
		if(array_key_exists($authorUserID, $userRoleMap)) {
			$role = $userRoleMap[$authorUserID];
			$userTag = "<span class='Tag RoleTag'>{$role}</span>";
		}

		echo $userTag;
	}

	//add role class to initial discussion post for styling (separate from role tag)
	public function discussionController_BeforeDiscussionDisplay_handler($Sender, $args) {
		if(!c('Zotero.AddRoleClasses', false)){
			return;
		}

		$authorUserID = $args['Author']->UserID;
		$Roles = Gdn::UserModel()->GetRoles($authorUserID)->ResultArray();
		foreach($Roles as $role){
			$args['CssClass'] .= " Role_{$role['Name']}";
		}
	}

	public function discussionController_BeforeCommentDisplay_handler($Sender, $args) {
		//add role class to comment post for styling (separate from role tag)
		//add role classes to highlight developer/expert posts
		if(c('Zotero.AddRoleClasses', false)){
			$authorUserID = $args['Author']->UserID;
			$Roles = Gdn::UserModel()->GetRoles($authorUserID)->ResultArray();
			foreach($Roles as $role){
				$args['CssClass'] .= " Role_{$role['Name']}";
			}
		}

		if(c('Zotero.EndorsedComments', false) && $this->userIsIn(c('Zotero.previewFeatureUserIDs', []))) {
			//check if Endorsed comment in normal display flow and add extra class for rendering if it is
			$endorsed = GetValueR('Comment.Endorsed', $args);
			if ($endorsed) {
				$args['CssClass'] .= " EndorsedComment";
			}
		}
	}

	//show endorsed comments at the top of the page before regular comment flow
	public function discussionController_ZoteroBeforeComments_handler($Sender) {
		if(c('Zotero.EndorsedComments', false) && $this->userIsIn(c('Zotero.previewFeatureUserIDs', []))) {
			if ($Sender->data('Page') == 1) {
				if(count($Sender->Data('Endorsed'))) {
					include $Sender->FetchViewLocation('endorsedcomments', 'discussion', 'plugins/zotero');
				}
			}
		}
	}

	public function discussionController_AfterCommentBody_handler($Sender) {
		//add link to endorsed comment that has been pulled up in order to view original in context
		if(c('Zotero.EndorsedComments', false)) {
			if($endorsed = $Sender->EventArguments['EndorsedPullup'] ?? false) {
				echo "<p><a class='endorsedContextLink' href='{$Sender->EventArguments['Permalink']}'>View in context</a></p>";
			}
		}
	}

	//add comment options for admin to select correct answer/featured comment
	public function discussionController_CommentOptions_handler($Sender, $args) {
		if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
			$Session = Gdn::session();
			$Comment = $args['Comment'];
			if (!$Comment) {
				return;
			}

			$label = 'Endorse Comment';
			$tkey = $Session->TransientKey();
			$qstring = "?commentid={$Comment->CommentID}&tkey={$tkey}";

			//if comment is already endorsed, change menu item to cancel
			if ($Comment->Endorsed == '1') {
				$label = 'Cancel Endorsement';
				$qstring .= "&cancelendorsement=1";
			}
			
			$args['CommentOptions']['EndorseComment'] = [
				'Label' => $label,
				'Url' => '/zotero/endorsecomment' . $qstring,
			];
		}
	}

	//update discussion's DateLastComment and LastCommentUserID when a pending comment is approved
	public function logModel_AfterRestore_handler($Sender, $args) {
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

	/**
	 * Let users with permission choose to receive Moderation notification emails.
	 */
	public function profileController_afterPreferencesDefined_handler($Sender) {
		if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
			$Sender->Preferences['Notifications']['Email.Moderation'] = t('Notify me when moderation is required.');
		}
	}

	public function PostController_BeforeFormInputs_handler() {
		//add hidden suggested reading placeholder that JS can populate and unhide
		echo '<div id="SuggestedReading" style="display:none;"><p>You may want to read:</p><ul id="suggestedReadingList"></ul></div>';
	}

	//return 404 if url being requested specifies userID, but not a matching username
	//this allows urls that either have an ID matching the passed username, or only a username
	public function ProfileController_UserLoaded_Handler($Sender) {
		if ($Sender instanceof ProfileController) {
			$userReference = $Sender->ReflectArgs['UserReference'] ?? $Sender->ReflectArgs['User'] ?? null;
			$username = $Sender->ReflectArgs['Username'] ?? false;
			if ($userReference && is_numeric($userReference)) {
				if ($username != $Sender->User->Name) {
					error_log("ProfileController request made with username url mismatch");
					throw notFoundException('User');
				}
			}
		}
	}

	// test that logged in userID included in the passed array
	public function userIsIn($userIDs = []) {
		$Session = Gdn::session();
		if(!$Session->UserID) {
			//no user
			return false;
		}
		if (in_array($Session->UserID, $userIDs)) {
			return true;
		}
		return false;
	}
}
