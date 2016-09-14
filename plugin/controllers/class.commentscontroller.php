<?php
/**
 * Adds comments feed
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /search endpoint.
 */
class CommentsController extends Gdn_Controller {

    /** @var array Models to automatically instantiate. */
    public $Uses = array('Database');

    /**  @var SearchModel */
    public $SearchModel;

    /**
     * Object instantiation & form prep.
     */
    public function __construct() {
        parent::__construct();

        // Object instantiation
        $this->SearchModel = new ZoteroSearchModel();
    }

    /**
     * Add JS, CSS, modules. Automatically run on every use.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        $this->Head = new HeadModule($this);
        parent::initialize();
    }

    /**
     * Default search functionality.
     *
     * @since 2.0.0
     * @access public
     * @param int $Page Page number.
     */
    public function index($Page = '') {
        $this->title(t('Recent Comments'));
        Gdn_Theme::section('SearchResults');

        if (!empty($_GET['Limit'])) {
            if ($_GET['Limit'] > 150) {
                $Limit = 150;
            }
            else {
                $Limit = (int) $_GET['Limit'];
            }
        }
        else {
            $Limit = 50;
        }

        try {
            $ResultSet = $this->SearchModel->feedSearch($Limit);
        } catch (Gdn_UserException $Ex) {
            $ResultSet = array();
        } catch (Exception $Ex) {
            LogException($Ex);
            $ResultSet = array();
        }
        //link to users so feed has 'creator'
        Gdn::userModel()->joinUsers($ResultSet, array('UserID'));

        $this->setData('SearchResults', $ResultSet, true);
        $this->setData('SearchTerm', Gdn_Format::text($Search), true);
        if ($ResultSet) {
            $NumResults = count($ResultSet);
        } else {
            $NumResults = 0;
        }
        if ($NumResults == $Offset + $Limit) {
            $NumResults++;
        }

        $this->View = 'feed';

        //$this->canonicalUrl(url('comments', true));

        $this->render();
    }
}
