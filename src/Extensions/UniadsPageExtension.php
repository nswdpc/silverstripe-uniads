<?php
namespace SilverstripeUniads\Extension;
use Silverstripe\ORM\DataExtension;
use Silverstripe\ORM\DataList;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverstripeUniads\Model\UniadsObject;
use SilverstripeUniads\Model\UniadsZone;
use SilverstripeUniads\Model\UniadsCampaign;
use SilverstripeUniads\Model\UniadsImpression;
use Silverstripe\Forms\FieldList;
use Silverstripe\Forms\CheckboxField;
use Silverstripe\Forms\DropdownField;
use Silverstripe\Forms\GridField\GridField;
use Silverstripe\Forms\GridField\GridFieldConfig_RelationEditor;

/**
 * Description of UniadsPageExtension
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Hans de Ruiter <hans@hdrlab.org.nz>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @author James Ellis <james.ellis@dpc.nsw.gov.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class UniadsPageExtension extends DataExtension {

    private static $db = [
        'InheritSettings' => 'Boolean',
    ];
    private static $defaults = [
        'InheritSettings' => true
    ];
    private static $many_many = [
        'Ads' => UniadsObject::class,
    ];
    private static $has_one = [
        'UseCampaign' => UniadsCampaign::class,
    ];

    public function updateCMSFields(FieldList $fields) {
        parent::updateCMSFields($fields);

        $fields->findOrMakeTab('Root.Advertisements', _t('UniadsObject.PLURALNAME', 'Advertisements'));
        $fields->addFieldToTab('Root.Advertisements', CheckboxField::create('InheritSettings', _t('UniadsObject.InheritSettings', 'Inherit parent settings')));

        if (!$this->owner->InheritSettings) {
            $conf = GridFieldConfig_RelationEditor::create();
            $conf->getComponentByType('GridFieldAddExistingAutocompleter')->setSearchFields(array('Title'));
            $grid = new GridField("Advertisements", _t('UniadsObject.PLURALNAME', 'Advertisements'), $this->owner->Ads(), $conf);
            $fields->addFieldToTab(
                            "Root.Advertisements",
                            $grid
            );
            $fields->addFieldToTab(
                            'Root.Advertisements',
                            DropdownField::create(
                                'UseCampaignID',
                                _t('UniadsObject.UseCampaign', 'Use Campaign'),
                                UniadsCampaign::get()->map()->toArray()
                            )->setEmptyString('')
            );
        }
    }

    /** Displays a randomly chosen advertisement of the specified dimensions.
     *
     * @param zone of the advertisement
     */
    public function DisplayAd($zone) {
        $ad = null;

        if ($zone) {
            if (!is_object($zone)) {
                $zone = UniadsZone::get()
                    ->filter(array(
                        'Title' => $zone,
                        'Active' => 1
                    ))
                    ->first();
            }
            if ($zone) {
                $toUse = $this->owner;
                if ($toUse->InheritSettings) {
                    while ($toUse->ParentID) {
                        if (!$toUse->InheritSettings) {
                            break;
                        }
                        $toUse = $toUse->Parent();
                    }
                    if(!$toUse->ParentID && $toUse->InheritSettings) {
                        $toUse = null;
                    }
                }

                $page_related = "and not exists (select * from Page_Ads pa where pa.UniadsObjectID = UniadsObject.ID)";
                $campaign = '';
                if ($toUse) {
                    $page_related = "and (
                        exists (select * from Page_Ads pa where pa.UniadsObjectID = UniadsObject.ID and pa.PageID = ".$toUse->ID.")
                        or not exists (select * from Page_Ads pa where pa.UniadsObjectID = UniadsObject.ID)
                    )";
                    if ($toUse->UseCampaignID) {
                        $campaign = "and c.ID = '" . $toUse->UseCampaignID . "'";
                    }
                }

                $base_from = "
                    UniadsObject
                        left join UniadsCampaign c on c.ID = UniadsObject.CampaignID
                ";
                $base_where = "
                    UniadsObject.ZoneID = '" . $zone->ID . "'
                    ".$page_related."
                    and (c.ID is null or (
                        c.Active = '1'
                        and (c.Starts <= '" . date('Y-m-d') . "' or c.Starts = '' or c.Starts is null)
                        and (c.Expires >= '" . date('Y-m-d') . "' or c.Expires = '' or c.Expires is null)
                        ".$campaign."
                    ))
                    and (UniadsObject.Starts <= '" . date('Y-m-d') . "' or UniadsObject.Starts = '' or UniadsObject.Starts is null)
                    and (UniadsObject.Expires >= '" . date('Y-m-d') . "' or UniadsObject.Expires = '' or UniadsObject.Expires is null)
                    and UniadsObject.Active = '1'
                ";
                $subbase_where = preg_replace_callback(
                    '/(?<!\w)(UniadsObject|c)\./'
                    , function ($m) { return str_repeat($m[1], 2).'.'; }
                    , $base_where
                );

                $sqlQuery = new SQLSelect(
                    $select = 'UniadsObject.ID',
                    $from = array($base_from),
                    $where = $base_where . "
                        and (UniadsObject.ImpressionLimit = 0 or UniadsObject.ImpressionLimit > UniadsObject.Impressions)
                        and UniadsObject.Weight >= (rand() * (
                            select max(UniadsObjectUniadsObject.Weight)
                            from UniadsObject as UniadsObjectUniadsObject
                                left join UniadsCampaign cc on cc.ID = UniadsObjectUniadsObject.CampaignID
                            where " . $subbase_where . "
                        ))",
                    $order = "rand()",
                    $limit = 1
                );
                singleton('UniadsObject')->extend('augmentSQL', $sqlQuery);
                //echo $sqlQuery->sql();
                $result = $sqlQuery->execute();
                if($result && count($result) > 0) {
                    $row = $result->First();
                    if (isset($row['ID']) && $row['ID'] !== '') {
                        $ad = UniadsObject::get()->byID($row['ID']);
                        // now we can log impression
                        $conf = UniadsObject::config();
                        if ($conf->record_impressions) {
                            $ad->Impressions++;
                            $ad->write();
                        }
                        if ($conf->record_impressions_stats) {
                            $imp = new UniadsImpression;
                            $imp->AdID = $ad->ID;
                            $imp->write();
                        }
                    }
                }
            }
        }

        if (!$ad) {
            // Show an empty advert
            $ad = new UniadsObject();
        }

        $output = $ad->forTemplate();

        if ($zone) {
            foreach ($zone->ChildZones()->sort('Order') as $child) {
                if ($child->Active) {
                    $output .= $this->DisplayAd($child);
                }
            }
        }

        return $output;
    }

}
