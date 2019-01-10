<?php
namespace SilverstripeUniads\Controller;
use SilverstripeUniads\Model\UniadsObject;
use SilverstripeUniads\Model\UniadsZone;
use SilverstripeUniads\Model\UniadsClick;
use Silverstripe\Control\Director;
use Silverstripe\Control\Controller;
use Silverstripe\Control\HTTPRequest;
use SilverStripe\View\Requirements;
use SilverStripe\CMS\Model\SiteTree;

/**
 * Description of UniadsController
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @author James Ellis <james.ellis@dpc.nsw.gov.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class UniadsController extends Controller {

    private static $allowed_actions = [
        'clk',
        'go',
        'ad'
    ];

    public function clk(HTTPRequest $request) {
        $this->GetAdAndLogClick($this->request->requestVar('id'));
    }

    public function go(HTTPRequest $request) {
        $ad = $this->GetAdAndLogClick($this->request->param('ID'));
        if ($ad) {
            $target = $ad->getTarget();
            $this->redirect($target ? $target : Director::baseURL());
        }
    }

    private function GetAdAndLogClick($id) {
        if ($id) {
            $ad = UniadsObject::get()->byID($id);
            if ($ad && $ad->exists()) {
                $conf = UniadsObject::config();
                if ($conf->record_clicks) {
                    $ad->Clicks++;
                    $ad->write();
                }
                if ($conf->record_clicks_stats) {
                    $clk = UniadsClick::create();
                    $clk->AdID = $ad->ID;
                    $clk->write();
                }
                return $ad;
            }
        }
        return null;
    }

    /**
     * Renders an Ad in basic HTML page
     */
    public function ad() {
        $z = $this->request->getVar('z');
        $p = $this->request->getVar('p');
        $zone = UniadsZone::get()->filter(['ID' => $z, 'Active' => 1])->first();

        if(empty($zone->ID)) {
            return "";
        }

        // get the requesting page
        $page = SiteTree::get()->byId($p);

        // return random ad from zone with optional page - this checks validity
        $ad = $zone->GetRandomAd($page);

        // check if Ad campaign is valid if linked to
        if(empty($ad->ID)) {
            return "";
        }

        $ad->RecordImpression();

        // remove any requirements
        Requirements::clear();

        $html = $ad->forTemplate();
        header('HTTP/1.1 200 OK');
        header('Content-Type: text/html');

        $template_data = [
            'Ad' => $html,
            'BaseHref' => Director::absoluteBaseURL()
        ];

        print $ad->customise($template_data)->renderWith('UniadsAdOnly');
        exit;

    }

}
