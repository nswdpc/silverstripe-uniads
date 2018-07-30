<?php
namespace SilverstripeUniads\Admin;
use SilverstripeUniads\Model\UniadsObject;
use SilverstripeUniads\Model\UniadsCampaign;
use SilverstripeUniads\Model\UniadsClient;
use SilverstripeUniads\Model\UniadsZone;
use SilverstripeUniads\Model\UniadsReport;
use Silverstripe\Security\Member;
use Silverstripe\Control\Director;
use Silverstripe\Control\Controller;
use Silverstripe\Control\HTTPRequest;
use Silverstripe\ORM\DataObject;
use SilverStripe\Admin\ModelAdmin;
use Silverstripe\View\Requirements;
use Silverstripe\View\SSViewer;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use Silverstripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;

/**
 * Description of UniadsAdmin
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @author James Ellis <james.ellis@dpc.nsw.gov.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class UniadsAdmin extends ModelAdmin {

    private static $managed_models = [
        UniadsObject::class,
        UniadsCampaign::class,
        UniadsClient::class,
        UniadsZone::class,
        UniadsReport::class
    ];

    private static $allowed_actions = [
        'preview',
        'random'
    ];

    private static $url_rule = '/$ModelClass/$Action/$ID/$OtherID';

    private static $url_segment = 'advrt';
    private static $menu_title = 'Ads';
    private static $menu_icon = '';


    public function __construct() {
        parent::__construct();
    }

    /**
     * @param Int $id
     * @param FieldList $fields
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $fields = $form->Fields();
        // GridField config mogrification

        $field_name = $this->sanitiseClassName( $this->modelClass );
        $gf = $fields->dataFieldByName( $field_name );
        if($gf) {
            $config = $gf->getConfig();
            switch($this->modelClass) {
                case UniadsZone::class:
                    $config->addComponent( GridFieldOrderableRows::create('Order') );
                    break;
                case UniadsReport::class:
                    $config->removeComponentsByType( GridFieldAddNewButton::class );
                    $config->removeComponentsByType( GridFieldImportButton::class );
                    break;
            }
        }
        return $form;
    }

    /** Preview an advertisement.
     */
    public function preview(HTTPRequest $request) {
        $ad_id = $request->getVar('id');
        if(!$ad_id) {
            Controller::curr()->httpError(404);
            return;
        }
        $ad = UniadsObject::get()->byID($ad_id);

        $member = Member::currentUser();
        if (!$ad || !$ad->canView($member)) {
            Controller::curr()->httpError(404);
            return;
        }

        // No impression and click tracking for previews
        $conf = UniadsObject::config();
        $conf->use_js_tracking = false;
        $conf->record_impressions = false;
        $conf->record_impressions_stats = false;

        // Block stylesheets and JS that are not required (using our own template)
        Requirements::clear();

        return $ad->renderWith('UniadsPreview');
    }

    /**
     * Test method for random ad
     */
    public function random(HTTPRequest $request) {
        $zone_id = $request->getVar('id');
        if(!$zone_id) {
            Controller::curr()->httpError(404);
            return;
        }
        $member = Member::currentUser();
        $zone = UniadsZone::get()->filter('ID',  $zone_id)->first();
        if (!$zone || !$zone->canView($member)) {
            Controller::curr()->httpError(404);
            return;
        }

        $ad = $zone->GetRandomAd();
        if (!$ad || !$ad->canView($member)) {
            Controller::curr()->httpError(404);
            return;
        }

        // No impression and click tracking for previews
        $conf = UniadsObject::config();
        $conf->use_js_tracking = false;
        $conf->record_impressions = false;
        $conf->record_impressions_stats = false;

        // Block stylesheets and JS that are not required (using our own template)
        Requirements::clear();

        return $ad->renderWith('UniadsPreview');
    }

}
