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

    public function AutoBanAndNotify() {
        $autobankey = Gdn::Config("Zotero.AutoBanKey", "");
        if($_GET['autobankey'] !== $autobankey || $_GET['autobankey'] == ''){
            echo "invalid autobankey";
            return;
        }

        $banLogIDs = [];
        $logModel = new LogModel();

        //loop through all pending posts and check against our string and regex blacklists
        
        $pending = $logModel->getWhere(['Operation'=>'Pending', 'RecordType'=>['Discussion', 'Comment']]);

        foreach($pending as $pendingRow) {
            $data = $pendingRow['Data'];
            
            if($pendingRow['RecordType'] == 'Comment') {
                if($this->autoBannable($data['Body'])){
                    $banLogIDs[] = $pendingRow['LogID'];
                }
            } else if($pendingRow['RecordType'] == 'Discussion'){
                if($this->autoBannable($data['Name']) || $this->autoBannable($data['Body'])){
                    $banLogIDs[] = $pendingRow['LogID'];
                }
            }
        }

        //ban as spam the logIDs we've flagged
        //var_dump($banLogIDs);
        $this->deleteAndBan($banLogIDs, false);

        //send moderation notification if the only pending posts were posted in the last n minutes
        //re-fetch pending
        $recentQuery = "SELECT COUNT(LogID) as count FROM GDN_Log WHERE Operation = 'Pending' AND RecordType IN ('Discussion', 'Comment') AND DateInserted > NOW() - INTERVAL 5 MINUTE";
        $countQuery = "SELECT COUNT(LogID) as count FROM GDN_Log WHERE Operation = 'Pending' AND RecordType IN ('Discussion', 'Comment')";
        
        $recentPending = Gdn::database()->query($recentQuery)->value('count', 0);
        $allPending = Gdn::database()->query($countQuery)->value('count', 0);
        //var_dump($recentPending);
        //var_dump($allPending);
        if(($recentPending == 0) || ($recentPending < $allPending)){
            return;
        }
        echo "Send notification";
        //send notifications
        $Email = new Gdn_Email();
        $Email->subject('Moderation pending on Zotero forums');
        $Email->to('fcheslack@gmail.com');
        $Email->message('A new post is awaiting moderator approval on the Zotero forums.');

        try {
            $Email->send();
        } catch (phpmailerException $pex) {
            if ($pex->getCode() == PHPMailer::STOP_CRITICAL) {
                error_log('Failure sending moderation email');
            } else {
                error_log('Error sending moderation email');
            }
        } catch (Exception $ex) {
            error_log('Failure sending moderation email');
        }
        
        //get moderator users
        $moderators = Gdn::userModel()->getByRole('Moderator')->resultArray();
        $admins = Gdn::userModel()->getByRole('Administrator')->resultArray();
        $modUsers = array_merge($admins, $moderators);
        //var_dump($modUsers);die;
        $sendEmails = false;

        foreach($modUsers as $user){
            Gdn::userModel()->setCalculatedFields($user);
            $Preferences = val('Preferences', $user);
            $moderationEmail = arrayValue('Email.Moderation', $Preferences, false);
            if($moderationEmail){
                if(!$sendEmails){
                    echo "Would have sent email to {$user['Email']}<br/>";
                    continue;
                }
                $Email = new Gdn_Email();
                $Email->subject('Moderation pending on Zotero forums');
                $Email->to($user);
                $Email->message('A new post is awaiting moderator approval on the Zotero forums.');

                try {
                    $Email->send();
                } catch (phpmailerException $pex) {
                    if ($pex->getCode() == PHPMailer::STOP_CRITICAL) {
                        error_log('Failure sending moderation email');
                    } else {
                        error_log('Error sending moderation email');
                    }
                } catch (Exception $ex) {
                    error_log('Failure sending moderation email');
                }
            }
        }
        
    }

    private function autoBannable($text){
        $blackListMatch = [
            'avanti group',
            'antioxidants',
            'beauty care',
            'Beds UK',
            'Belly Fat',
            'best supplement to',
            'bp holdings',
            'bradley associates',
            'boost brain',
            'build muscle',
            'build your body',
            'build your muscle',
            'Circuit Training',
            'corliss group',
            'crown capital',
            'crown eco capital',
            'dietary supplement',
            'dining chairs',
            'download videos for free',
            'energy levels',
            'face care',
            'fifa coins',
            'fifa 16 coins',
            'fitness and exercise',
            'fitness Training',
            'furniture',
            'gain muscle',
            'garcinia',
            'hass and associates',
            'hass associates',
            'heating system',
            'hendren global group',
            'Fitted Kitchens',
            'Fraud Management',
            'help download youtube',
            'kitchen',
            'Koyal Group',
            'libido',
            'lost lover',
            'love marriage',
            'magic Specialist',
            'newport international group',
            'online women',
            'Q/',
            'Q\\',
            'penis',
            'skine care',
            'Slim Body',
            'Solid Wood',
            'tyler group',
            'weight lose',
            'weight loss',
            'weight gain',
            'with supplement',
            'vital cleanse',
            '69OP',
            'live stream',
            'livestream',
            'فريجيدير',
            'جنرال اليكتريك',
            'وستنجهاوس',
            'يونيون آير',
            'شارب',
            'كاريير',
            'هيتاشى',
            'غسالات',
            '카',
            '신',
            '놀',
            '俿',
            '이',
            '지',
            '썬',
            'Alexandria attorney',
            'alphafuelx',
            'androx',
            'astrology',
            'bank loan',
            'Belly Fat',
            'best supplements for',
            'blastcanada',
            'body[-_ ]*building',
            'Boiler room',
            'bp[-_ ]*holdings',
            'bp[-_ ]*spain[-_ ]*holdings',
            'brainammo',
            'Brain Plus',
            'Bridesmaid Dresses',
            'Call Girls',
            'capital management',
            'cash loan',
            'ccnaexam',
            'christian louboutin',
            'cisco.com',
            'corliss group',
            'Cosmetics Wholesale',
            'convertjpgtopdf',
            'deekshainstruments',
            'Divorce Certificates',
            'exdisplaykitchens',
            'fake passport',
            'Fashion Jewelry',
            'Full Movie Download',
            'garcinia',
            'garcinium',
            'get your love back',
            'Graystone Corp',
            'healthylifestyle',
            'heating boiler',
            'home loans',
            'horoscope',
            'husband wife problem',
            'Indostar303',
            'improve your brain',
            'jusomart',
            'kitchenfactory',
            'Lawson Rener',
            'lesgrandshotels',
            'long path tool',
            'love problem ',
            'luxury goods',
            'Louis Vuitton',
            'Marriage Certificates',
            'maxmanpowers',
            'mensusa.com',
            'meninasdiet',
            'Moncler Jackets',
            'movers and packers',
            'muscle build',
            'musclefacts',
            'Neeraj Tewari',
            'neuro3x',
            'newportintlgroup',
            'nike free',
            'Nike Shoes',
            'nike air',
            'nike shox',
            'no2extreme',
            'North Face Jackets',
            'oil spill',
            'optimalstackfacts',
            'OPBONDA',
            'packers and movers',
            'pandora charms',
            'pathtoodeep',
            'penis',
            'PLC Training',
            'Q/',
            'Q\\',
            'ray ban sunglasses',
            'repair refrigerators',
            'replica *handbags',
            'ripmuscle',
            'sexterrassa',
            'solidwoodkitchen',
            'soran.edu.iq',
            'sourceforge.net/projects/pdftodoc',
            'testinate',
            ' testo ',
            'udaiso',
            'ugg shoes',
            'unitechindia',
            'Westinghouse Maintenance',
            'wedding dress',
            'Westhill Healthcare',
            'x4fact',
            'YAGIRL',
            'yeosaek',
            '>>>>>> http',
            'http://steamcommunity.com/'
        ];
        $blackListRegex = [
            'body[-_ ]*(Builder|building|cleanse|fitness|thigh)',
            'brain[-_ ]*(performance|power|boost|support|again)',
            'Los[e|ing].*Weight',
            'male[^ -]+enhancement',
            'muscle[-_ ]*(build|gain|mass|shape)',
            'nitro (shred|x)',
            '[-_ ]testo[-_ ]',
            'watch.*(online|episode|season)',
            'season.*[0-9]+.*episode',
            'skin[-_ ]*(beauty|care|cream|repair)',
            'nfl.*jersey',
            'weight[-_ ]*(loss|lose|gain)',
            'watch[^\\?].*(online|episode|season)',
            'season.*[0-9]+.*episode',
            'abney[-_ ]*associates',
            'abney[-_ ]*and[-_ ]*associates',
            'abney[-_ ]*and[-_ ]*associates',
            'anti[-_ ]*aging',
            'avanti *group',
            'bam[-_ =]*war',
            '(body|vital|zen)[-_ ]*cleanse',
            'bradley[-_ ]*associates',
            '(cheap|affordable).*kitchen',
            '(cheap|sports|nfl|mlb) *jersey',
            'coffee[-_ ]*cleanse',
            'cleanse[-_ ]*ultimo',
            'Consinee[-_ ]*Group',
            'crown[-_ ]*capital',
            'crown[-_ ]*jakarta[-_ ]*capital',
            'crown[-_ ]*management',
            'cruse[-_ ]*associates',
            'elite[-_ ]*test',
            'fat[-_ ]*burner',
            'fitted[-_ ]*kitchens',
            'gulf[-_ ]*oil[-_ ]*spill',
            'hass[-_ ]*and[-_ ]*associates',
            'hass[-_ ]*associates',
            'haney[-_ ]*group',
            'hendren[-_ ]*group',
            'Hendren *Global *Group',
            'hgh ?xl',
            'koyal[-_ ]*(training[-_ ]*)?group',
            'kitchen[-_ ]*(design|units)',
            'long[-_ ]*path[-_ ]*tool',
            'male[^ -]+enhancement',
            'moringa[-_ ]*slim',
            'Mulberry.*bags',
            'mumbai.+escorts',
            'http[^ ]+muscle',
            'nature[-_ ]*(pure[-_ ]*)?cleanse',
            'newport[-_ ]*international[-_ ]*group',
            'nfl.*jersey',
            'nitro[-_ ]*(shred|x)',
            'payday *loan',
            'rolex *watch',
            'skin[-_ ]*(beauty|care|repair)',
            'slim[-_ ]*(cleanse|fast)',
            "^Source( Link)?:?[[:space:]]+http",
            'southwood[-_ ]*group',
            'Springhill[-_ ]*group',
            'superior[-_ ]*muscle',
            't(ee|oo)th[-_ ]*whiten',
            'testo(sterone)?[-_ ]*boost',
            'T-90[-_ ]*Xplode',
            'triplex[-_ ]*ultra',
            'tyler[-_ ]*group',
            'tyler[-_ ]*group',
            'Unnart[-_ ]*fortune[-_ ]*group',
            'wealth[-_ ]*management',
            'weight[-_ ]*loss',
            'westhill[-_ ]*consulting',
            'https?://www.facebook.com/',
            'https?://www.youtube.com/watch'
        ];
        foreach($blackListMatch as $m){
            if(stripos($text, $m) !== false){
                return true;
            }
        }
        foreach($blackListRegex as $r){
            if(preg_match("/".$r."/i", $text) == 1){
                return true;
            }
        }
        return false;
    }

    /**
     * Delete spam and optionally delete the users.
     * @param type $LogIDs
     */
    protected function deleteAndBan($LogIDs, $checkRequestPermission=true) {
        //error_log('deleteAndBan');
        $reason = 'Spam';
        if($checkRequestPermission){
            $this->permission(array('Garden.Moderation.Manage', 'Moderation.Spam.Manage'), false);

            if (!$this->Request->isPostBack()) {
                throw permissionException('Javascript');
            }

            if (!$this->Request->isAuthenticatedPostBack()) {
                throw permissionException('CSRF');
            }
        }
        if($checkRequestPermission === false){
            $reason = 'Automatic Spam Detection';
        }

        if(!is_array($LogIDs)){
            $LogIDs = explode(',', $LogIDs);
        }

        $LogModel = new LogModel();

        // We also want to collect the users from the log.
        $Logs = $LogModel->getIDs($LogIDs);
        $UserIDs = [];
        foreach ($Logs as $Log) {
            $UserID = $Log['RecordUserID'];
            if (!$UserID) {
                continue;
            }
            $UserIDs[$UserID] = true;
        }

        $UserIDs = array_keys($UserIDs);
        if (!empty($UserIDs)) {
            // Grab the rest of the log entries.
            $OtherLogIDs = $LogModel->getWhere(array('Operation' => 'Pending', 'RecordUserID' => $UserIDs));
            $OtherLogIDs = array_column($OtherLogIDs, 'LogID');
            $LogIDs = array_merge($LogIDs, $OtherLogIDs);

            foreach ($UserIDs as $UserID) {
                Gdn::userModel()->ban($UserID, array('Reason' => $reason, 'DeleteContent' => true, 'Log' => true));
            }
        }

        // Grab the logs.
        $LogModel->delete($LogIDs);
    }
}
