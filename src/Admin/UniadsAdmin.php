<?php
namespace SilverstripeUniads\Admin;
use SilverstripeUniads\Model\UniadsObject;
use SilverstripeUniads\Model\UniadsCampaign;
use SilverstripeUniads\Model\UniadsClient;
use SilverstripeUniads\Model\UniadsZone;
use Silverstripe\Control\Director;
use Silverstripe\Control\Controller;
use Silverstripe\Control\HTTPRequest;
use Silverstripe\ORM\DataObject;
use SilverStripe\Admin\ModelAdmin;
use Silverstripe\View\Requirements;
use Silverstripe\View\SSViewer;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use Silverstripe\Core\Config\Config;

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
    ];

    private static $allowed_actions = [
        'preview'
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
        switch($this->modelClass) {
            case UniadsZone::class:
                $field_name = $this->sanitiseClassName( UniadsZone::class );
                $gf = $fields->dataFieldByName( $field_name );
                $config = $gf->getConfig();
                $config->addComponent( GridFieldOrderableRows::create('Order') );
                $list = $this->getList();
                $list = $list->where('ParentZoneID = 0 OR ParentZoneID IS NULL');
                break;
        }
        return $form;
    }

    /** Preview an advertisement.
     */
    public function preview(HTTPRequest $request) {
        $request->shift();
        $adID = (int) $request->param('ID');
        $ad = UniadsObject::get()->byID($adID);

        if (!$ad) {
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

        $template = new SSViewer('UniadsPreview');

        return $template->Process($ad);
    }

}
