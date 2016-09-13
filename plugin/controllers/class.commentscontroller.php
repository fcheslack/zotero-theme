<?php
/**
 * Manages basic searching.
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

    /**  @var Gdn_Form */
    public $Form;

    /**  @var SearchModel */
    public $SearchModel;

    /**
     * Object instantiation & form prep.
     */
    public function __construct() {
        parent::__construct();

        // Object instantiation
        $this->SearchModel = new ZoteroSearchModel();
        //$this->SearchModel = new SearchModel();
        $Form = Gdn::Factory('Form');

        // Form prep
        $Form->Method = 'get';
        $Form->InputPrefix = '';
        $this->Form = $Form;
    }

    /**
     * Add JS, CSS, modules. Automatically run on every use.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        //error_log('zotero plugin search controller');
        $this->Head = new HeadModule($this);
        $this->addModule('GuestModule');
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
        //$this->title(t('Recent Comments'));

        saveToConfig('Garden.Format.EmbedSize', '160x90', false);
        Gdn_Theme::section('SearchResults');

        list($Offset, $Limit) = offsetLimit($Page, c('Garden.Search.PerPage', 20));
        $this->setData('_Limit', $Limit);

        $Search = $this->Form->getFormValue('Search');
        $Mode = $this->Form->getFormValue('Mode');
        if ($Mode) {
            $this->SearchModel->ForceSearchMode = $Mode;
        }
        try {
            $ResultSet = $this->SearchModel->Search($Search, $Offset, $Limit, true);
        } catch (Gdn_UserException $Ex) {
            $this->Form->addError($Ex);
            $ResultSet = array();
        } catch (Exception $Ex) {
            LogException($Ex);
            $this->Form->addError($Ex);
            $ResultSet = array();
        }
        Gdn::userModel()->joinUsers($ResultSet, array('UserID'));

        // Fix up the summaries.
        /*$SearchTerms = explode(' ', Gdn_Format::text($Search));
        foreach ($ResultSet as &$Row) {
            $Row['Summary'] = SearchExcerpt(Gdn_Format::plainText($Row['Summary'], $Row['Format']), $SearchTerms);
            $Row['Summary'] = Emoji::instance()->translateToHtml($Row['Summary']);
            $Row['Format'] = 'Html';
        }*/

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

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->Pager = $PagerFactory->GetPager('MorePager', $this);
        $this->Pager->MoreCode = 'More Results';
        $this->Pager->LessCode = 'Previous Results';
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $Offset,
            $Limit,
            $NumResults,
            'dashboard/search/%1$s/%2$s/?Search='.Gdn_Format::url($Search)
        );

//		if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
//         $this->setJson('LessRow', $this->Pager->toString('less'));
//         $this->setJson('MoreRow', $this->Pager->toString('more'));
//         $this->View = 'results';
//      }
        $this->View = 'feed';

        $this->canonicalUrl(url('search', true));

        $this->render();
    }
}
