<?php
namespace SilverstripeUniads\Model;
use Silverstripe\ORM\DataObject;
use Silverstripe\Security\Member;
use DateTime;

/**
 * Description of UniadsImpression
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @author James Ellis <james.ellis@dpc.nsw.gov.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class UniadsImpression extends DataObject {

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'UniadsImpression';

    private static $singular_name = 'Impression';
    private static $plural_name = 'Impressions';

    private static $store_user_agent = true;

    private static $db = [
        'UserAgent' => 'Varchar(128)',
        'BrowserVersion' => 'Varchar',
        'Browser' => 'Varchar',
        'Platform' => 'Varchar',
        'ViewDayName' => 'Varchar',
        'ViewMonth' => 'Varchar',
        'ViewDay' => 'Int',
        'ViewYear' => 'Int',
        'Referer' => 'Varchar',
        'RemoteIP' => 'Varchar',
    ];

    private static $has_one = [
        'User' => Member::class,
        'Ad' => UniadsObject::class,
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Created.Nice' => 'Created',
        'ViewDayName' => 'Day Name',
        'ViewDay' => 'Day',
        'ViewMonth' => 'Month',
        'ViewYear' => 'Year',
        'Referer' => 'Referer'
    ];

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $searchable_fields = [
        'UserAgent' => 'PartialMatchFilter',
        'RemoteIP' => 'PartialMatchFilter',
        'ViewMonth',
        'ViewDay',
        'ViewYear',
        'Created',
    ];

    public function onBeforeWrite() {
        parent::onBeforeWrite();

        $this->setBrowserInfo();

        $this->Referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $this->RemoteIP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $this->UserID = Member::currentUserID();

        $dt = new DateTime();

        $this->ViewDay = $dt->format('d');
        $this->ViewDayName = $dt->format('l');
        $this->ViewMonth = $dt->format('F');
        $this->ViewYear = $dt->format('Y');
    }

    /**
     * Store UserAgent only, consign UA parsing to an out-of-band reporting mechanism
     * Note this change removes the original get_browser_info sniffing function
     */
    protected function setBrowserInfo() {
        if($this->config()->store_user_agent) {
            $this->UserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        }
    }
}
