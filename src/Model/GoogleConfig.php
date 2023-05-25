<?php

namespace Iliain\GoogleConfig\Models;

use SilverStripe\ORM\DB;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Security\Permission;
use Iliain\GoogleConfig\Models\GooglePlace;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\View\TemplateGlobalProvider;
use Iliain\GoogleConfig\Admin\GoogleLeftAndMain;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

class GoogleConfig extends DataObject implements TemplateGlobalProvider
{
    private static $table_name = 'GoogleConfig';

    private static $db = [
        'HeadScripts'       => 'Text',
        'BodyStartScripts'  => 'Text',
        'BodyEndScripts'    => 'Text'
    ];

    private static $required_permission = [
        'CMS_ACCESS_CMSMain',
        'CMS_ACCESS_LeftAndMain'
    ];

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
    }

    public function getCMSFields()
    {
        // Configure default tabs
        $fields = new FieldList(
            new TabSet("Root",
                $tabGTM = new Tab('GTM'),
                $tabPlaces = new Tab('Places'),
            ),
            new HiddenField('ID')
        );

        // Set custom tab names
        $tabGTM->setTitle(_t(self::class . '.TABGTM', "GTM"));
        $tabPlaces->setTitle(_t(self::class . '.TABPLACES', "Places"));

        $fields->addFieldsToTab('Root.GTM', [
            HeaderField::create('GTMHeader', 'Configure Google Tag Manager'),
            TextareaField::create('HeadScripts', 'Scripts inside <head>')->setRows(10),
            TextareaField::create('BodyStartScripts', 'Scripts after <body>')->setRows(10),
            TextareaField::create('BodyEndScripts', 'Scripts before </body>')->setRows(10),
        ]);

        $reviewGridConf = GridFieldConfig_RecordEditor::create()::create();
        $grid = GridField::create(
            'Places',
            'Places',
            GooglePlace::get(),
            $reviewGridConf
        );
        $fields->addFieldToTab('Root.Places', $grid);

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
     * Setup a default GoogleConfig record if none exists.
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
     * Create GoogleConfig with defaults from language file.
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

    public function getPlaces()
    {
        return GooglePlace::get();
    }
}
