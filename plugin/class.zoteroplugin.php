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
	//add the vanilla search SearchModel when our overridden model fires the event
	//the core Vanilla app registers a 'searchModel_Search_Handler'
	public function zoterosearchModel_Search_Handler($Sender) {
		$SearchModel = new VanillaSearchModel();
		$SearchModel->Search($Sender);
	}
}
