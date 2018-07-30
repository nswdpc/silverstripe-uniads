<?php
namespace SilverstripeUniads\Controller;
use SilverstripeUniads\Model\UniadsObject;
use SilverstripeUniads\Model\UniadsClick;
use Silverstripe\Control\Director;
use Silverstripe\Control\Controller;
use Silverstripe\Control\HTTPRequest;

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

}
