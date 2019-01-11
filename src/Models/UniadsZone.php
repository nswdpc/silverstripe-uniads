<?php
namespace SilverstripeUniads\Model;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataList;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverstripeUniads\Admin\UniadsAdmin;
use Silverstripe\Forms\LiteralField;
use Silverstripe\Forms\CheckboxField;
use Silverstripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Control\Controller;


/**
 * Description of UniadsZone
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author James Ellis <james.ellis@dpc.nsw.gov.au>
 * @license BSD http://silverstripe.org/BSD-license
 */

class UniadsZone extends DataObject {

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'UniadsZone';

    private static $singular_name = 'Zone';
    private static $plural_name = 'Zones';

    private static $db = [
        'Title' => 'Varchar',
        'ZoneWidth' => 'Varchar(6)',
        'ZoneHeight' => 'Varchar(6)',
        'Order' => 'Int',
        'Active' => 'Boolean',
        'ViewInIframe' => 'Boolean',
        'RandomiseIframeLink' => 'Boolean'
    ];

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $searchable_fields = [
        'Title',
        'ZoneWidth',
        'ZoneHeight',
        'Active'
    ];

    private static $summary_fields = [
        'ID' => '#',
        'Title',
        'ParentZone.Title',
        'ZoneWidth',
        'ZoneHeight',
        'ViewInIframe.Nice' => 'Iframe?',
        'Active.Nice' => 'Active',
    ];

    private static $has_one = [
        'ParentZone' => UniadsZone::class,
    ];

    private static $has_many = [
        'Ads' => UniadsObject::class,
        'ChildZones' => UniadsZone::class,
    ];

    private static $indexes = [
        'Title' => [ 'type' => 'index', 'columns' => ['Title'] ],
    ];

    private static $defaults = [
        'ParentZoneID' => 0,
        'Active' => 1,
        'ViewInIframe' => 0,
        'RandomiseIframeLink' => 0
    ];

    private static $default_records = array(
        array('Title' => 'Top', 'ZoneWidth' => '500', 'ZoneHeight' => '90'),
        array('Title' => 'Right', 'ZoneWidth' => '160', 'ZoneHeight' => '600'),
    );

    private static $default_sort = ' IF(ParentZoneID IS NULL OR ParentZoneID = 0, 1, 0) DESC, Order ASC, ID ASC';

    /**
     * Returns the Zone width, if the ZoneWidth is numeric we assume px unit
     * This allows someone to add e.g 100% as the unit
     * @returns string
     */
    public function getWidth(){
        return $this->ZoneWidth . (is_numeric($this->ZoneWidth) ? 'px' : '');
    }

    /**
     * Returns the Zone height, if the ZoneHeight is numeric we assume px unit
     * This allows someone to add e.g 100% as the unit
     * @returns string
     */
    public function getHeight(){
        return $this->ZoneHeight . (is_numeric($this->ZoneHeight) ? 'px' : '');
    }

    public function fieldLabels($includerelations = true) {
        $labels = parent::fieldLabels($includerelations);
        $labels['ParentZone.Title'] = _t('UniadsZone.has_one_ParentZone', 'Parent Zone');
        return $labels;
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        // no need to have order editing
        $fields->removeByName('Order');

        // if the zone has a parent, remove ability to create children, it's only two level zoning
        if($this->ParentZone()->exists()) {
            $fields->removeByName('ChildZones');
        }

        // If the zone has children, no parent zone can be selected
        if ($this->ChildZones()->Count() > 0) {
            $fields->removeByName('ParentZoneID');
        }

        // zone selection - can only select top level AND not self
        if (($field = $fields->dataFieldByName('ParentZoneID'))) {
            $field->setSource(
                UniadsZone::get()
                ->where("ID != '" . Convert::raw2sql($this->ID) . "' AND (ParentZoneID IS NULL OR ParentZoneID = 0)")
                ->map()->toArray()
            );
            $field->setDescription(
                _t('UniadsZone.ParentZoneSelection',
                'Select a parent for this zone, or leave unselected to set this zone as a top level zone.<br />When a zone is displayed, all active ads within its child zones will display.')
            );
        }

        $cz = $fields->dataFieldByName('ChildZones');
        if($cz) {
            $config = $cz->getConfig();
            $config->removeComponentsByType( GridFieldAddExistingAutocompleter::class );
        }

        if($this->exists()) {
            // @todo this is probably not the best way to concoct an admin link
            $class_name = str_replace("\\", "-", UniadsZone::class);
            $preview_link = Director::absoluteBaseURL() . 'admin/' . UniadsAdmin::config()->url_segment . '/' . $class_name . '/random/?id=' . $this->ID;

            $fields->addFieldToTab(
                'Root.Main',
                LiteralField::create(
                    'Preview',
                    "<a href=\"{$preview_link}\" target=\"_blank\">" . _t('UniadsZone.Preview', 'Random Ad Preview') . "</a>"
                ),
                'Title'
            );
        }


        $fields->addFieldsToTab(
                    'Root.Main', [
                    CheckboxField::create('ViewInIframe', _t('UniadsObject.db_ViewInIframe', 'View In Iframe'))
                        ->setDescription( _t('UniadsZone.LoadInIframe', 'Load the ad to display in an iframe') ),
                    CheckboxField::create('RandomiseIframeLink', _t('UniadsObject.db_RandomiseIframeLink', 'Add a random value to the iframe link'))
                        ->setDescription( _t('UniadsZone.db_RandomiseIframeLinkNote', 'Can assist with caching') )
        ]);

        return $fields;
    }

    /**
     * Return src for showing ad in iframe
     */
    public function IframeLink(SiteTree $page = null) {
        $display_path = 'dsp/ad/?p=' . ($page ? $page->ID : '') . '&z=' . $this->ID;
        return Controller::join_links(Director::baseURL(), $display_path);
    }

    public function IframeHeight() {
        return $this->Height;
    }

    public function IframeWidth() {
        return $this->Width;
    }

    /**
     * Get a random ad from this zone. Room for improvement, here.
     * @param SiteTree $page
     */
    public function GetRandomAd(SiteTree $page = null) {
        // Determine actual page to use settings from
        if ($page && $page->InheritSettings == 1) {
            while ($page->ParentID) {
                if (!$page->InheritSettings) {
                    break;
                }
                $page = $page->Parent();
            }
            if(!$page->ParentID && $page->InheritSettings) {
                $page = null;
            }
        }

        $page_related = "AND NOT EXISTS (SELECT * FROM Page_Ads pa WHERE pa.UniadsObjectID = UniadsObject.ID)";
        $campaign = "";
        if (($page instanceof SiteTree) && !empty($page->ID)) {
            $page_related = "AND (\n"
                . " EXISTS (SELECT * FROM Page_Ads pa WHERE pa.UniadsObjectID = UniadsObject.ID AND pa.PageID = '" . Convert::raw2sql($page->ID) . "')\n"
                . " OR NOT EXISTS (SELECT * FROM Page_Ads pa WHERE pa.UniadsObjectID = UniadsObject.ID)\n"
                . " )\n";
            if ($page->UseCampaignID) {
                $campaign = "AND c.ID = '" . Convert::raw2sql($page->UseCampaignID) . "'\n";
            }
        }

        $base_from = "UniadsObject\n"
                . " LEFT JOIN UniadsCampaign c ON c.ID = UniadsObject.CampaignID\n";
        $base_where = " UniadsObject.ZoneID = '" . Convert::raw2sql($this->ID) . "'\n"
                . $page_related
                . " AND (c.ID IS NULL OR (\n"
                . " c.Active = 1\n"
                . " AND (c.Starts <= '" . Convert::raw2sql(date('Y-m-d')) . "' OR c.Starts = '' OR c.Starts IS NULL)\n"
                . " AND (c.Expires >= '" . Convert::raw2sql(date('Y-m-d')) . "' OR c.Expires = '' OR c.Expires IS NULL)\n"
                . $campaign
                . ") )\n"
                . " AND (UniadsObject.Starts <= '" . Convert::raw2sql(date('Y-m-d')) . "' OR UniadsObject.Starts = '' OR UniadsObject.Starts IS NULL)\n"
                . " AND (UniadsObject.Expires >= '" . Convert::raw2sql(date('Y-m-d')) . "' OR UniadsObject.Expires = '' OR UniadsObject.Expires IS NULL)\n"
                . " AND UniadsObject.Active = 1\n";
        // TODO - review
        $subbase_where = preg_replace_callback(
            '/(?<!\w)(UniadsObject|c)\./'
            , function ($m) { return str_repeat($m[1], 2).'.'; }
            , $base_where
        );

        $sqlQuery = new SQLSelect(
            $select = 'UniadsObject.ID',
            $from = [ $base_from ],
            $where = $base_where
                . " AND (UniadsObject.ImpressionLimit = 0 OR UniadsObject.ImpressionLimit > UniadsObject.Impressions)\n"
                . " AND UniadsObject.Weight >= (\n"
                . " RAND() * ( "
                    . " SELECT MAX(UniadsObjectUniadsObject.Weight)\n"
                    . " FROM UniadsObject AS UniadsObjectUniadsObject\n"
                    . " LEFT JOIN UniadsCampaign cc on cc.ID = UniadsObjectUniadsObject.CampaignID\n"
                    . " WHERE {$subbase_where}\n"
                . " )\n"
                . " )",
            $order = "RAND()",
            $limit = 1
        );
        singleton( UniadsObject::class )->extend('augmentSQL', $sqlQuery);
        // echo nl2br($sqlQuery->sql()) . "\n";
        $result = $sqlQuery->execute();

        $ad = null;
        if($result && count($result) > 0) {
            $row = $result->First();
            if (isset($row['ID']) && $row['ID'] !== '') {
                $ad = UniadsObject::get()->byID($row['ID']);
            }
        }
        return $ad;

    }

}
