<?php
namespace SilverstripeUniads\Model;
use Silverstripe\ORM\DataObject;

/**
 * Description of UniadsClient
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @author James Ellis <james.ellis@dpc.nsw.gov.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class UniadsClient extends DataObject {

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'UniadsClient';

    private static $singular_name = 'Client';
    private static $plural_name = 'Clients';

    private static $db = [
        'Title' => 'Varchar(128)',
        'ContactEmail' => 'Text',
    ];

    private static $summary_fields = [
        'Title',
        'ContactEmail',
    ];

    private static $searchable_fields = [
        'Title',
        'ContactEmail',
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Campaigns' => UniadsCampaign::class,
    ];

}
