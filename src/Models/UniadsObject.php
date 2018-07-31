<?php
namespace SilverstripeUniads\Model;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use Silverstripe\ORM\DataObject;
use Silverstripe\ORM\DataList;
use Silverstripe\Forms\FieldList;
use Silverstripe\Forms\TabSet;
use Silverstripe\Forms\Tab;
use Silverstripe\Forms\TextField;
use Silverstripe\Forms\NumericField;
use Silverstripe\Forms\CheckboxField;
use Silverstripe\Forms\Treedropdownfield;
use Silverstripe\Forms\Dropdownfield;
use SilverStripe\AssetAdmin\Forms\UploadField;
use Silverstripe\Forms\ReadonlyField;
use Silverstripe\Forms\LiteralField;
use Silverstripe\Forms\TextareaField;
use Silverstripe\Forms\DateField;
use Silverstripe\Assets\File;
use Silverstripe\Security\Member;
use SilverstripeUniads\Admin\UniadsAdmin;
use Silverstripe\i18n\i18n;
use SilverStripe\View\SSViewer;
use SilverStripe\View\Requirements;
use SilverStripe\Core\Convert;
use SilverStripe\Control\HTTP;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use DateTime;
use Page;

/**
 * Description of UniadsObject (ddvertisement object)
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Hans de Ruiter <hans@hdrlab.org.nz>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @author James Ellis <james.ellis@dpc.nsw.gov.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class UniadsObject extends DataObject {

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'UniadsObject';

    private static $singular_name = 'Ad';
    private static $plural_name = 'Ads';

    private static $use_js_tracking = true;
    private static $record_impressions = true;
    private static $record_impressions_stats = false;
    private static $record_clicks = true;
    private static $record_clicks_stats = true;

    private static $files_dir = 'UploadedAds';
    private static $max_file_size = 2097152;

    private static $db = [
        'Title' => 'Varchar(255)',
        'Starts' => 'Date',
        'Expires' => 'Date',
        'Active' => 'Boolean',
        'TargetURL' => 'Varchar(255)',
        'NewWindow' => 'Boolean',
        'AdContent' => 'HTMLText',
        'ImpressionLimit' => 'Int',
        'Weight' => 'Double',
        'Impressions' => 'Int',
        'Clicks' => 'Int',
    ];

    private static $has_one = [
        'File' => File::class,
        'Zone' => UniadsZone::class,
        'Campaign' => UniadsCampaign::class,
        'InternalPage' => Page::class,
    ];

    private static $belongs_many_many = [
        'AdInPages' => Page::class,
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'ImpressionRecords' => UniadsImpression::class,
        'Reports' => UniadsReport::class,
    ];

    private static $defaults = [
        'Active' => 0,
        'NewWindow' => 1,
        'ImpressionLimit' => 0,
        'Weight' => 1.0,
    ];

    private static $searchable_fields = [
        'Title',
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Starts.Nice' => 'Starts',
        'Expires.Nice' => 'Expires',
        'Campaign.Title' => 'Campaign',
        'Zone.Title' => 'Zone',
        'Impressions' => 'Impressions',
        'Clicks' => 'Clicks',
    ];

    private static $indexes = [
        'Active' => [ 'type' => 'index', 'columns' => ['Active'] ],
        'Weight' => [ 'type' => 'index', 'columns' => ['Weight'] ],
        'ImpressionLimit' => [ 'type' => 'index', 'columns' => ['ImpressionLimit'] ],
        'Impressions' => [ 'type' => 'index', 'columns' => ['Impressions'] ],
        'Clicks' => [ 'type' => 'index', 'columns' => ['Clicks'] ],
        'DateLimit' => [ 'type' => 'index', 'columns' => ['Starts','Expires'] ],
    ];


    public function fieldLabels($includerelations = true) {
        $labels = parent::fieldLabels($includerelations);

        $labels['Campaign.Title'] = _t('UniadsObject.has_one_Campaign', 'Campaign');
        $labels['Zone.Title'] = _t('UniadsObject.has_one_Zone', 'Zone');
        $labels['Impressions'] = _t('UniadsObject.db_Impressions', 'Impressions');
        $labels['Clicks'] = _t('UniadsObject.db_Clicks', 'Clicks');

        return $labels;
    }


    public function getCMSFields() {
        $fields = new FieldList();
        $fields->push(new TabSet('Root', new Tab('Main', _t('SiteTree.TABMAIN', 'Main')
            , new TextField('Title', _t('UniadsObject.db_Title', 'Title'))
        )));

        if ($this->exists()) {

            $fields->addFieldToTab('Root.Main', new ReadonlyField('Impressions', _t('UniadsObject.db_Impressions', 'Impressions')), 'Title');
            $fields->addFieldToTab('Root.Main', new ReadonlyField('Clicks', _t('UniadsObject.db_Clicks', 'Clicks')), 'Title');

            $fields->addFieldsToTab('Root.Main', array(
                DropdownField::create('CampaignID', _t('UniadsObject.has_one_Campaign', 'Campaign'), UniadsCampaign::get()->map())->setEmptyString(_t('UniadsObject.Campaign_none', 'none')),
                DropdownField::create('ZoneID', _t('UniadsObject.has_one_Zone', 'Zone'), UniadsZone::get()->map())->setEmptyString(_t('UniadsObject.Zone_select', 'select one')),
                new NumericField('Weight', _t('UniadsObject.db_Weight', 'Weight (controls how often it will be shown relative to others)')),
                new TextField('TargetURL', _t('UniadsObject.db_TargetURL', 'Target URL')),
                new Treedropdownfield('InternalPageID', _t('UniadsObject.has_one_InternalPage', 'Internal Page Link'), 'Page'),
                new CheckboxField('NewWindow', _t('UniadsObject.db_NewWindow', 'Open in a new Window')),
                $file = new UploadField('File', _t('UniadsObject.has_one_File', 'Advertisement File')),
                $content = new TextareaField('AdContent', _t('UniadsObject.db_AdContent', 'Advertisement Content')),
                $starts = new DateField('Starts', _t('UniadsObject.db_Starts', 'Starts')),
                $expires = new DateField('Expires', _t('UniadsObject.db_Expires', 'Expires')),
                new NumericField('ImpressionLimit', _t('UniadsObject.db_ImpressionLimit', 'Impression Limit')),
                new CheckboxField('Active', _t('UniadsObject.db_Active', 'Active')),
            ));

            // @todo this is probably not the best way to concoct an admin link
            $class_name = str_replace("\\", "-", UniadsObject::class);
            $preview_link = Director::absoluteBaseURL() . 'admin/' . UniadsAdmin::config()->url_segment . '/' . $class_name . '/preview/?id=' . $this->ID;

            $fields->addFieldToTab(
                'Root.Main',
                LiteralField::create(
                    'Preview',
                    "<a href=\"{$preview_link}\" target=\"_blank\">" . _t('UniadsObject.Preview', 'Preview this advertisement') . "</a>"
                ),
                'Title'
            );

            $app_categories = File::config()->app_categories;
            $file->setFolderName($this->config()->files_dir);
            $file->getValidator()->setAllowedMaxFileSize(array('*' => $this->config()->max_file_size));
            $file->getValidator()->setAllowedExtensions(array_merge($app_categories['image'], $app_categories['flash']));

            $expires->setMinDate( date( 'Y-m-d', strtotime($this->Starts ? $this->Starts : '+1 days') ) );

            $content->setRows(10);
            $content->setColumns(20);
        }

        if($this->exists()) {
            // for each type, add a tab for the grid field
            $types = [
                UniAdsReport::IMPRESSION,
                UniAdsReport::CLICK,
            ];
            foreach($types as $type) {
                $config = GridFieldConfig_RecordEditor::create();
                $list = $this->owner->Reports()->filter('Type', $type)->sort('Created DESC');
                $name = 'Reports'. $type;
                $gf = GridField::create($name, sprintf(_t('UniadsReport.TypeReport', '%s report'), $type), $list, $config);
                $config->removeComponentsByType( GridFieldAddNewButton::class );
                $config->removeComponentsByType( GridFieldAddExistingAutocompleter::class );
                $config->addComponent( new GridFieldExportButton('buttons-before-left') );
                $fields->addFieldToTab('Root.' . $type . 'Report', $gf);
            }
        }

        $this->extend('updateCMSFields', $fields);
        return $fields;
    }


    /**
     * Returns true if this is an "external" advertisment (e.g., one from Google AdSense).
     * "External" advertisements have no target URL or page.
     */
    public function ExternalAd() {
        if (!$this->InternalPageID && empty($this->TargetURL)) {
            return true;
        }

        $file = $this->getComponent('File');
        if ($file && $file->appCategory() == 'flash') {
            return true;
        }

        return false;
    }

    public function forTemplate() {
        return $this->renderWith('UniadsObject');
    }

    public function UseJsTracking() {
        return $this->config()->use_js_tracking;
    }

    public function TrackingLink($absolute = false) {
        return Controller::join_links($absolute ? Director::absoluteBaseURL() : Director::baseURL(), 'uniads-click/go/'.$this->ID);
    }

    public function Link() {
        if ($this->UseJsTracking()) {
            Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery.js'); // TODO: How about jquery.min.js?
            Requirements::javascript(ADS_MODULE_DIR.'/javascript/uniads.js');

            $link = Convert::raw2att($this->getTarget());
        } else {
            $link = $this->TrackingLink();
        }
        return $link;
    }

    public function getTarget() {
        return $this->InternalPageID
            ? $this->InternalPage()->AbsoluteLink()
            : ($this->TargetURL ? (strpos($this->TargetURL, 'http') !== 0 ? 'http://' : '') . $this->TargetURL : false)
        ;
    }

    public function getContent() {
        $file = $this->File();
        if (!empty($file->ID)) {
            if ($file->appCategory() == 'flash') {
                $html = $this->getFlashContent();
            } else if ($file->appCategory() == 'image') {
                $src = htmlspecialchars($file->getAbsoluteURL());
                $alt = htmlspecialchars($file->Title);
                $html = <<<HTML
<img src="{$src}" style="width:100%;display:block;" alt="{$alt}">
HTML;
            } else {
                $html = "";
            }
            return DBField::create_field(DBHTMLText::class, $html);
        } else {
            return $this->AdContent;
        }
    }

    private function getFlashContent() {
        if(!$this->config()->allow_flash) {
            return "";
        }
        $zone = $this->Zone();
        if(empty($zone->ID)) {
            return "";
        }
        $src = $this->getTarget() ? HTTP::setGetVar('clickTAG', $this->TrackingLink(true), $file->Filename) : $file->Filename;
        $html = <<<HTML
<object
classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"
width="{$zone->Width}" height="{$zone->Height}"
style="display:block;">
    <param name="movie" value="{$src}">
    <param name="quality" value="high">
    <param name="wmode" value="transparent">
    <embed
        src="{$src}"
        quality="high"
        wmode="transparent"
        width="{$zone->Width}"
        height="{$zone->Height}"
        type="application/x-shockwave-flash"
        pluginspage="http://www.macromedia.com/go/getflashplayer">
    </embed>
</object>
HTML;
        return $html;
    }

}
