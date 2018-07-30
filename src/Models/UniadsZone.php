<?php
namespace SilverstripeUniads\Model;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataList;
use SilverStripe\Core\Convert;

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
        'ParentZone.Title',
        'Title',
        'Active',
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
    ];

    private static $default_records = array(
        array('Title' => 'Top', 'ZoneWidth' => '500', 'ZoneHeight' => '90'),
        array('Title' => 'Right', 'ZoneWidth' => '160', 'ZoneHeight' => '600'),
    );

    private static $default_sort = ' IF(ParentZoneID IS NULL OR ParentZoneID = 0, 1, 0) DESC, Order ASC, ID ASC';

    public function getWidth(){
        return $this->ZoneWidth . (ctype_digit($this->ZoneWidth) ? 'px' : '');
    }

    public function getHeight(){
        return $this->ZoneHeight . (ctype_digit($this->ZoneHeight) ? 'px' : '');
    }

    public function fieldLabels($includerelations = true) {
        $labels = parent::fieldLabels($includerelations);
        $labels['ParentZone.Title'] = _t('UniadsZone.has_one_ParentZone', 'Parent Zone');
        return $labels;
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        if (!$this->ParentZoneID) {
            $fields->removeByName('Order');
        }

        if ($this->ChildZones()->Count() > 0) {
            $fields->removeByName('ParentZoneID');
        }

        if (($field = $fields->dataFieldByName('ParentZoneID'))) {
            $field->setSource(
                UniadsZone::get()
                ->where("ID != '" . Convert::raw2sql($this->ID) . "' AND (ParentZoneID IS NULL OR ParentZoneID = 0)")
                ->map()->toArray()
            );
        }

        return $fields;
    }

}
