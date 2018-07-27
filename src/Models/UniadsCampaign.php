<?php
namespace SilverstripeUniads\Model;
use Silverstripe\ORM\DataObject;
use SilverStripe\i18n\i18n;

/**
 * Description of UniadsCampaign
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @author James Ellis <james.ellis@dpc.nsw.gov.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class UniadsCampaign extends DataObject {

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'UniadsCampaign';

    private static $singular_name = 'Campaign';
    private static $plural_name = 'Campaigns';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Starts' => 'Date',
        'Expires' => 'Date',
        'Active' => 'Boolean',
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Starts' => 'Starts',
        'Expires' => 'Expires',
        'Active' => 'Active',
    ];

    private static $searchable_fields = [
        'Title',
        'Starts',
        'Expires',
        'Active',
    ];

    private static $has_many = [
        'Ads' => UniadsObject::class,
    ];

    private static $has_one = [
        'Client' => UniadsClient::class,
    ];

    private static $indexes = [
        'Active' => [ 'type' => 'index', 'value' => 'Active'],
        'DateLimit' => [ 'type' => 'index', 'value' => '"Starts","Expires"'],
    ];

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $expires = $fields->dataFieldByName('Expires');
        $expires->setMinDate( date( 'Y-m-d', strtotime($this->Starts ? $this->Starts : '+1 days') ) );

        $fields->changeFieldOrder(array(
            'Title',
            'ClientID',
            'Starts',
            'Expires',
            'Active',
        ));

        return $fields;
    }

}
