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

class MyPlugin extends Gdn_Plugin {
	//add the vanilla search SearchModel when our overridden model fires the event
	//the core Vanilla app registers a 'searchModel_Search_Handler'
    public function zoterosearchModel_Search_Handler($Sender) {
		$SearchModel = new VanillaSearchModel();
		$SearchModel->Search($Sender);
	}
}
