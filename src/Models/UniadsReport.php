<?php
namespace SilverstripeUniads\Model;
use Silverstripe\ORM\DataObject;

/**
 * Description of UniadsReport
 *
 * @author James Ellis <james.ellis@dpc.nsw.gov.au>
 * @license BSD http://silverstripe.org/BSD-license
 * This table is populated by a queued job that scrapes the current impression and click count and adds a record per run
 */
class UniadsReport extends DataObject {

    private static $table_name = 'UniadsReport';

    const IMPRESSION = 'Impressions';
    const CLICK = 'Clicks';

    private static $db = array(
        'Type' => 'Varchar(12)',
        'Count' => 'Int',
    );

    private static $has_one = array(
        'Ad' => UniadsObject::class,
    );

    private static $summary_fields = array(
        'Type' => 'Type',
        'Created' => 'Created',
        'Count' => 'Count',
    );

    private static $indexes = array(
        'Created' => true, // Use Created as the record date/time
        'Type' => true,
        'Count' => true,
    );

    /**
     * Saves data for active ads from supplied UniAdsObject types
     * @todo is the ad within expiry time and is it's campaign active ?
     * @param array $types
     */
    public static function saveCurrentReports(array $types) {
        $ads = UniAdsObject::get()->filter('Active', 1);
        $reports = [];
        foreach($ads as $ad) {
            foreach($types as $type) {
                $has = $ad->hasField($type);
                if($has) {
                    $count = $ad->getField($type);
                    $report = UniAdsReport::create();
                    $report->Type = $type;
                    $report->Count = (int)$count;
                    $report->AdID = $ad->ID;
                    $report->write();
                    $reports[] = $report;
                }
            }
        }
        return $reports;
    }

    /**
     * run a report, unused at the moment
     */
    public function ReportByTime($type, $start_datetime, $end_datetime) {
            $report = UniAdsReport::get()->filter('Type', $type);
            if($start_datetime && $end_datetime) {
                $report = $report->filter('Created:GreaterThanOrEqual', $start_datetime->format('Y-m-d H:i:s'));
                $report = $report->filter('Created:LessThanOrEqual', $end_datetime->format('Y-m-d H:i:s'));
            } else if($start_datetime) {
                $report = $report->filter('Created:GreaterThanOrEqual', $start_datetime->format('Y-m-d H:i:s'));
            } else if($end_datetime) {
                $report = $report->filter('Created:LessThanOrEqual', $end_datetime->format('Y-m-d H:i:s'));
            }
            $report->sort('Created ASC');
            return $report;
    }


}
