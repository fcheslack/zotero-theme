<?php
// Define the plugin:
$PluginInfo['zotero'] = array(
	'Name' => 'Zotero plugin',
	'Description' => '',
	'Version' => '0.1',
	'Author' => "Faolan Cheslack-Postava",
	'AuthorEmail' => '',
	'AuthorUrl' => '',
	'MobileFriendly' => TRUE,
);

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
		error_log('base_render_before');
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

	//add the vanilla search SearchModel when our overridden model fires the event
	//the core Vanilla app registers a 'searchModel_Search_Handler'
	public function zoterosearchModel_Search_Handler($Sender) {
		$SearchModel = new VanillaSearchModel();
		$SearchModel->Search($Sender);
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
                $roleTag = "<span class='Tag RoleTag'>Zotero</span>";
            } elseif($role['Name'] == 'Moderator'){
                $roleTag = "<span class='Tag RoleTag'>Zotero</span>";
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

    //update discussion's DateLastComment and LastCommentUserID when a pending comment is approved
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

    /**
     * Let users with permission choose to receive Moderation notification emails.
     */
    public function profileController_afterPreferencesDefined_handler($Sender) {
        if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            $Sender->Preferences['Notifications']['Email.Moderation'] = t('Notify me when moderation is required.');
        }
    }

    public function PostController_BeforeFormInputs_handler($Sender, $args){
        echo '<div id="SuggestedReading" style="display:none;"><p>You may want to read:</p><ul id="suggestedReadingList"></ul></div>';
    }
}
