<?php

namespace Iliain\GoogleConfig\Models;

use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\ORM\DB;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Security\Permission;
use Iliain\GoogleConfig\Models\GooglePlace;
use SilverStripe\Forms\GridField\GridField;
use Iliain\GoogleConfig\Models\SchemaObject;
use SilverStripe\View\TemplateGlobalProvider;
use Iliain\GoogleConfig\Models\SchemaProperty;
use Iliain\GoogleConfig\Admin\GoogleLeftAndMain;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;

class GoogleConfig extends DataObject implements TemplateGlobalProvider
{
    private static $table_name = 'GoogleConfig';

    private static $db = [
        'HeadScripts'       => 'Text',
        'BodyStartScripts'  => 'Text',
        'BodyEndScripts'    => 'Text'
    ];

    private static $has_many = [
        'SchemaProperties'  => SchemaProperty::class,
        'SchemaObjects'     => SchemaObject::class,
    ];

    private static $required_permission = [
        'CMS_ACCESS_CMSMain',
        'CMS_ACCESS_LeftAndMain'
    ];

    public function onBeforeWrite(): void
    {
        parent::onBeforeWrite();
    }

    public function getCMSFields(): FieldList
    {
        // Configure default tabs
        $fields = new FieldList(
            new TabSet("Root",
                'Root',
                $tabGTM = new TabSet('GTM'),
                $tabSchema = new TabSet('Schema'),
                $tabPlaces = new TabSet('Places'),
            ),
            new HiddenField('ID')
        );

        $enabledPanels = $this->config()->get('enabled_panels');

        // Set custom tab names
        $tabGTM->setTitle(_t(self::class . '.TABGTM', "GTM"));
        $tabPlaces->setTitle(_t(self::class . '.TABPLACES', "Places"));
        $tabSchema->setTitle(_t(self::class . '.TABSCHEMA', "Schema"));

        // GTM
        if (in_array('GTM', $enabledPanels) && $enabledPanels['GTM']) {
            $fields->addFieldsToTab('Root.GTM.Main', [
                HeaderField::create('GTMHeader', 'Configure Google Tag Manager'),
                TextareaField::create('HeadScripts', 'Scripts inside <head>')->setRows(10),
                TextareaField::create('BodyStartScripts', 'Scripts after <body>')->setRows(10),
                TextareaField::create('BodyEndScripts', 'Scripts before </body>')->setRows(10),
            ]);
        } else {
            $fields->removeByName('GTM');
        }

        // Schema
        if (in_array('Schema', $enabledPanels) && $enabledPanels['Schema']) {
            // Individual properties on the global schema
            $schemaCols = new GridFieldEditableColumns();
            $schemaCols->setDisplayFields([
                'Title' => [
                    'title'    => 'Name',
                    'callback' => function($record, $column, $grid) {
                        return TextField::create($column)
                            ->setAttribute('placeholder', 'Name');
                    }
                ],
                'Value' => [
                    'title'    => 'Value',
                    'callback' => function($record, $column, $grid) {
                        return TextField::create($column)
                            ->setAttribute('placeholder', 'A description');
                    }
                ]
            ]);

            $schemaConf = GridFieldConfig::create()
                ->addComponents(
                    $schemaCols,
                    new GridFieldButtonRow(),
                    new GridFieldToolbarHeader(),
                    new GridFieldDetailForm(),
                    new GridFieldTitleHeader(),
                    $propertyAdd = new GridFieldAddNewInlineButton('toolbar-header-right'),
                    new GridFieldDeleteAction(false),
                );

            $propertyAdd->setTitle('Add Property');

            $fields->addFieldsToTab('Root.Schema.Properties', [
                GridField::create(
                    'SchemaProperties',
                    'Schema Properties',
                    $this->SchemaProperties(),
                    $schemaConf
                )
            ]);

            // Objects with their own properties
            $schemaGridConf = GridFieldConfig_RelationEditor::create();
            $fields->addFieldToTab('Root.Schema.Objects', GridField::create(
                'SchemaObjects',
                'Schema Objects',
                $this->SchemaObjects(),
                $schemaGridConf
            ));

            // Output
            $fields->addFieldToTab('Root.Schema.Output', TextareaField::create('SchemaCode', 'Generated Schema')->setRows(30)->setReadonly(true));
        } else {
            $fields->removeByName('Schema');
        }

        // Places
        if (in_array('Places', $enabledPanels) && $enabledPanels['Places']) {
            if (Environment::getEnv('GOOGLE_MAPS_API_KEY')) {
                $reviewGridConf = GridFieldConfig_RecordEditor::create();
                $grid = GridField::create(
                    'Places',
                    'Places',
                    GooglePlace::get(),
                    $reviewGridConf
                );
                $fields->addFieldToTab('Root.Places.Main', $grid);
            } else {
                $fields->addFieldToTab('Root.Places.Main', LiteralField::create('PlacesNotice', '<p class="message warning">Google Maps API key is not set</p>'));
            }
        } else {
            $fields->removeByName('Places');
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
    public function getCMSActions(): FieldList
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
    public function CMSEditLink(): string
    {
        return GoogleLeftAndMain::singleton()->Link();
    }

    /**
     * Get the current sites GoogleConfig, and creates a new one through
     * {@link make_google_config()} if none is found.
     *
     * @return GoogleConfig
     */
    public static function current_google_config(): GoogleConfig
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
    public function requireDefaultRecords(): void
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
    public static function make_google_config(): GoogleConfig
    {
        $config = GoogleConfig::create();
        $config->write();

        return $config;
    }

    /**
     * Add $GoogleConfig to all SSViewers
     */
    public static function get_template_global_variables(): array
    {
        return [
            'GoogleConfig' => 'current_google_config',
        ];
    }

    /**
     * Get all places
     *
     * @return DataList
     */
    public function getPlaces(): DataList
    {
        return GooglePlace::get();
    }

    public function getSchemaCode(): string
    {
        $schemaData = [
            '@context' => 'http://schema.org',
        ];
    
        foreach ($this->SchemaProperties() as $property) {
            if ($property->Title) {
                $schemaData[$property->Title] = $property->Value;
            } else {
                $schemaData[] = $property->Value;
            }
        }
    
        foreach ($this->SchemaObjects() as $object) {
            $schemaData['@graph'][] = json_decode($object->getSchemaCode(), true);
        }
    
        $formattedSchemaData = json_encode($schemaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
        $schemaStr = '<script type="application/ld+json">' . PHP_EOL;
        $schemaStr .= $formattedSchemaData . PHP_EOL;
        $schemaStr .= '</script>';
    
        return $schemaStr;
    }
}
