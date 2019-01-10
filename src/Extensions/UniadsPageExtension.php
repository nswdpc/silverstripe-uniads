<?php
namespace SilverstripeUniads\Extension;
use Silverstripe\ORM\DataExtension;
use Silverstripe\ORM\DataList;
use SilverstripeUniads\Model\UniadsObject;
use SilverstripeUniads\Model\UniadsZone;
use SilverstripeUniads\Model\UniadsCampaign;
use SilverstripeUniads\Model\UniadsImpression;
use Silverstripe\Forms\FieldList;
use Silverstripe\Forms\CheckboxField;
use Silverstripe\Forms\DropdownField;
use Silverstripe\Forms\GridField\GridField;
use Silverstripe\Forms\GridField\GridFieldConfig_RelationEditor;
use Silverstripe\Forms\GridField\GridFieldAddExistingAutocompleter;

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
            $conf->getComponentByType( GridFieldAddExistingAutocompleter::class )->setSearchFields(array('Title'));
            $grid = new GridField("Advertisements", _t('UniadsObject.PLURALNAME', 'Advertisements'), $this->owner->Ads(), $conf);
            $fields->addFieldsToTab(
                            "Root.Advertisements",
                            [
                                DropdownField::create(
                                    'UseCampaignID',
                                    _t('UniadsObject.UseCampaign', 'Use Campaign'),
                                    UniadsCampaign::get()->map()->toArray()
                                )->setEmptyString(''),
                                $grid
                            ]
            );
        }
    }

    /**
     * Displays a randomly chosen advertisement from the provided zone
     *
     * @param mixed $zone either a string zone name or a {@link UniadsZone}
     */
    public function DisplayAd($zone) {
        $ad = null;

        if (is_scalar($zone)) {
             $zone = UniadsZone::get()
                ->filter(array(
                    'Title' => $zone,
                    'Active' => 1
                ))
                ->first();
        }

        if (!($zone instanceof UniadsZone) || empty($zone->ID)) {
            // no zone found
            return null;
        }

        if($zone->ViewInIframe == 1) {
            // the Zone renders with an Iframe tag
            $iframe_link = $zone->IframeLink( $this->owner );
            $output = $zone->customise(['IframeLink' => $iframe_link])->renderWith('UniadsIframe');
        } else {
            // the zone renders inline

            // get ad from the Zone
            $ad = $zone->GetRandomAd($this->owner);
            if($ad) {
                // now we can log impression
                $ad->RecordImpression();
            } else {
                // Show an empty advert
                $ad = UniadsObject::create();
            }

            // if there is an Ad and Iframe is selected
            $output = $ad->forTemplate();

        }

        // process immediate child zones
        if ($zone) {
            $children = $zone->ChildZones()
                            ->filter('Active', 1)
                            ->exclude('ID', $zone->ID) // exclude possibility of zone being parent of itself
                            ->sort('Order ASC');
            foreach ($children as $child) {
                $output .= $this->DisplayAd($child);
            }
        }

        return $output;
    }

}
