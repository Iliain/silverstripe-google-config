<?php

namespace Iliain\GoogleConfig\Models;

use Exception;
use SilverStripe\ORM\DB;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Security\Permission;
use UncleCheese\DisplayLogic\Forms\Wrapper;
use Iliain\GoogleConfig\Admin\GoogleLeftAndMain;

class GoogleConfig extends DataObject
{
    private static $table_name = 'GoogleConfig';

    private static $db = [
        'HeadScripts'       => 'Text',
        'BodyStartScripts'  => 'Text',
        'BodyEndScripts'    => 'Text',
        'PlaceID'           => 'Varchar(255)',
        'PlaceTitle'        => 'Varchar(255)',
    ];

    private static $required_permission = [
        'CMS_ACCESS_CMSMain',
        'CMS_ACCESS_LeftAndMain'
    ];

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->PlaceID) {
            $this->PlaceTitle = $this->getPlaceTitle($this->PlaceID);
        }
    }

    public function getCMSFields()
    {
        // Configure default tabs
        $fields = new FieldList(
            new TabSet("Root",
                $tabGTM = new Tab('GTM'),
                $tabReviews = new Tab('Reviews'),
            ),
            new HiddenField('ID')
        );

        // Set custom tab names
        $tabGTM->setTitle(_t(self::class . '.TABGTM', "GTM"));
        $tabReviews->setTitle(_t(self::class . '.TABREVIEWS', "Reviews"));

        $fields->addFieldsToTab('Root.GTM', [
            HeaderField::create('GTMHeader', 'Configure Google Tag Manager'),
            TextareaField::create('HeadScripts', 'Scripts inside <head>')->setRows(10),
            TextareaField::create('BodyStartScripts', 'Scripts after <body>')->setRows(10),
            TextareaField::create('BodyEndScripts', 'Scripts before </body>')->setRows(10),
        ]);

        $fields->addFieldsToTab('Root.Reviews', [
            HeaderField::create('ReviewHeader', 'Configure Reviews'),
            TextField::create('PlaceID', 'Place ID'),
            LiteralField::create('Message', '<div class="message notice"><p>Use the map below to find your location, then copy the Place ID into the field above.</p><p>To select a new location\'s Place ID, delete the Place ID above, then begin searching.</p></div>'),
            Wrapper::create(
                // Use map listed in the guides. If it stops working, we'll have to figure out how best to get the place ID
                LiteralField::create('SelectorMap', '<iframe src="https://geo-devrel-javascript-samples.web.app/samples/places-placeid-finder/app/dist/" allow="fullscreen; " style="width: 100%; height: 400px;"></iframe>')
            )->displayIf('PlaceID')->isEmpty()->end(),
            Wrapper::create(
                LiteralField::create('DisplayMap', '<iframe src="https://www.google.com/maps/embed/v1/place?key=' . Environment::getEnv('GOOGLE_PLACE_API_KEY') . '&q=place_id:' . $this->PlaceID . '" allow="fullscreen; " style="width: 100%; height: 400px;" referrerpolicy="no-referrer-when-downgrade"></iframe>'),
            )->displayIf('PlaceID')->isNotEmpty()->end(),
        ]);

        if ($this->PlaceTitle) {
            $fields->insertBefore('PlaceID', ReadonlyField::create('PlaceTitle', 'Location'));
            $fields->addFieldsToTab('Root.Reviews', [
                HeaderField::create('FeedHeader', 'Reviews')
            ]);
        }

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    /**
     * Get the actions that are sent to the CMS.
     *
     * In your extensions: updateEditFormActions($actions)
     *
     * @return FieldList
     */
    public function getCMSActions()
    {
        if (Permission::check('ADMIN') || Permission::check('EDIT_SITECONFIG')) {
            $actions = new FieldList(
                FormAction::create(
                    'save_googleconfig',
                    _t('SilverStripe\\CMS\\Controllers\\CMSMain.SAVE', 'Save')
                )->addExtraClass('btn-primary font-icon-save')
            );
        } else {
            $actions = new FieldList();
        }

        $this->extend('updateCMSActions', $actions);

        return $actions;
    }

    /**
     * @return string
     */
    public function CMSEditLink()
    {
        return GoogleLeftAndMain::singleton()->Link();
    }

    /**
     * Get the current sites GoogleConfig, and creates a new one through
     * {@link make_google_config()} if none is found.
     *
     * @return GoogleConfig
     */
    public static function current_google_config()
    {
        /** @var GoogleConfig $googleConfig */
        $googleConfig = DataObject::get_one(GoogleConfig::class);
        if ($googleConfig) {
            return $googleConfig;
        }

        return self::make_google_config();
    }

    /**
     * Setup a default SiteConfig record if none exists.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        $config = DataObject::get_one(GoogleConfig::class);

        if (!$config) {
            self::make_google_config();

            DB::alteration_message("Added default Google config", "created");
        }
    }

    /**
     * Create googleConfig with defaults from language file.
     *
     * @return GoogleConfig
     */
    public static function make_google_config()
    {
        $config = GoogleConfig::create();
        $config->write();

        return $config;
    }

    /**
     * Add $GoogleConfig to all SSViewers
     */
    public static function get_template_global_variables()
    {
        return [
            'GoogleConfig' => 'current_google_config',
        ];
    }

    public function getAPIURL($placeID)
    {
        $key = Environment::getEnv('GOOGLE_PLACE_API_KEY');

        if (!$key) {
            return false;
        }

        return 'https://maps.googleapis.com/maps/api/place/details/json?place_id=' . $placeID . '&fields=name,rating,reviews&key=' . Environment::getEnv('GOOGLE_PLACE_API_KEY');
    }

    public function getPlaceTitle($placeID)
    {
        try {
            $url = $this->getAPIURL($placeID);
            $json = file_get_contents($url);
            $obj = json_decode($json);

            if ($obj && isset($obj->result)) {
                return $obj->result->name;
            } else {
                return null;
            }
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}
