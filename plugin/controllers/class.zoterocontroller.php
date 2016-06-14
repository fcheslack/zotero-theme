<?php
/**
 * Non-activity action logging.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /zmoderation endpoint.
 */
class ZoteroController extends Gdn_Controller {
    public function __construct() {
        parent::__construct();
        $this->PageName = 'Zotero';
    }

    public function initialize() {
        parent::initialize();
    }

    public function index() {
        //error_log('ZoteroController index');
        return;
    }

    public function spammer($LogIDs) {
        $this->deleteAndBan($LogIDs);
        return;
    }

    /**
     * Delete spam and optionally delete the users.
     * @param type $LogIDs
     */
    protected function deleteAndBan($LogIDs) {
        //error_log('deleteAndBan');
        $this->permission(array('Garden.Moderation.Manage', 'Moderation.Spam.Manage'), false);

        if (!$this->Request->isPostBack()) {
            throw permissionException('Javascript');
        }

        if (!$this->Request->isAuthenticatedPostBack()) {
            throw permissionException('CSRF');
        }


        $LogIDs = explode(',', $LogIDs);

        $LogModel = new LogModel();

        // We also want to collect the users from the log.
        $Logs = $LogModel->getIDs($LogIDs);
        $UserIDs = array();
        foreach ($Logs as $Log) {
            $UserID = $Log['RecordUserID'];
            if (!$UserID) {
                continue;
            }
            $UserIDs[] = $UserID;
        }
        
        if (!empty($UserIDs)) {
            // Grab the rest of the log entries.
            $OtherLogIDs = $LogModel->getWhere(array('Operation' => 'Pending', 'RecordUserID' => $UserIDs));
            $OtherLogIDs = array_column($OtherLogIDs, 'LogID');
            $LogIDs = array_merge($LogIDs, $OtherLogIDs);

            foreach ($UserIDs as $UserID) {
                Gdn::userModel()->ban($UserID, array('Reason' => 'Spam', 'DeleteContent' => true, 'Log' => true));
            }
        }

        // Grab the logs.
        $LogModel->delete($LogIDs);
    }
}
