<?php
namespace SilverstripeUniads\Jobs;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Silverstripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverstripeUniads\Model\UniadsReport;
use Exception;
use DateTime;

/**
 * Reporting Job, pulls data from UniAdsImpression and saves to UniAdsReport
 * @author James Ellis <james.ellis@dpc.nsw.gov.au>
 * @license BSD http://silverstripe.org/BSD-license
 * @todo SS_Log
 */
class UniAdsReportJob extends AbstractQueuedJob implements QueuedJob {

	use Configurable;

	private static $interval = 24;//hr

	public function __construct() {
	}

	public function getTitle() {
		return _t(
			'UniAdsReportJob.Title',
			"Save current Uniads reporting data for all active ads"
		);
	}

	public function getJobType() {
		$this->totalSteps = 1;
		return QueuedJob::QUEUED;
	}

	public function setup() {
		$this->totalSteps = 1;
	}

	public function process() {
		try {
			$types = [
				UniadsReport::IMPRESSION,
				UniadsReport::CLICK,
			];
			$reports = UniadsReport::saveCurrentReports($types);
			$this->isComplete = true;
			return;
		} catch(Exception $e) {
			//SS_Log::log('UniAdsReportJob failed with error:' . $e->getMessage(), SS_Log::WARN);
		}
		$this->isComplete = true;
		return;
	}

	public function afterComplete() {
		$interval = (int)Config::inst()->get( UniAdsReportJob::class, 'interval');
		if($interval < 0) {
			$interval = 24;
		}
		$now  = new DateTime();
		$now->modify('+ ' . $interval . ' hours');
		$next_run_datetime = $now->format('Y-m-d H:i:s');
		singleton( QueuedJobService::class )->queueJob(new UniAdsReportJob(), $next_run_datetime);
	}
}
