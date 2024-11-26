<?php

namespace Iliain\GoogleConfig\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\GridField\GridField;
use Iliain\GoogleConfig\Models\GoogleConfig;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;

class SchemaObject extends DataObject 
{
    /**
     * @var string
     */
    private static $table_name= 'SchemaObject';

    /**
     * @var string
     */
    private static $singular_name = 'Object';

    /**
     * @var string
     */
    private static $plural_name = 'Objects';

    /**
     * @var array
     */
    private static $db = [
        'Title'     => 'Varchar(255)',
        'Type'      => 'Varchar(255)',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'GoogleConfig'      => GoogleConfig::class,
        'SchemaObject'      => SchemaObject::class
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'SchemaObjects'     => SchemaObject::class,
        'SchemaProperties'  => SchemaProperty::class
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Title'                     => 'Name',
        'SchemaProperties.Count'    => 'Properties',
        'SchemaObjects.Count'       => 'Objects'
    ];

    /**
     * @var array
     */
    private static $searchable_fields = [
        'Title'
    ];

    /**
     * @return array
     */
    private static $cascade_deletes = [
        'SchemaObjects',
        'SchemaProperties'
    ];

    /**
     * @return FieldList
     */
    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $fields->removeByName(['GoogleConfigID', 'SchemaProperties', 'SchemaObjectID', 'SchemaObjects']);

        $fields->dataFieldByName('Title')
            ->setTitle('Name')
            ->setDescription('This is used only for the object; you\'ll need to add a separate property for the name if required.');

        $fields->insertAfter('Title', TextField::create('Type', 'Type'));

        if ($this->ID) {
            // Properties
            $schemaCols = new GridFieldEditableColumns();
            $schemaCols->setDisplayFields([
                'Title' => [
                    'title'    => 'Name',
                    'callback' => function($record, $column, $grid) {
                        return TextField::create($column);
                    }
                ],
                'Value' => [
                    'title'    => 'Value',
                    'callback' => function($record, $column, $grid) {
                        return TextField::create($column);
                    }
                ],
            ]);

            $schemaConf = GridFieldConfig::create()
                ->addComponents(
                    $schemaCols,
                    new GridFieldButtonRow(),
                    new GridFieldToolbarHeader(),
                    new GridFieldDetailForm(),
                    new GridFieldTitleHeader(),
                    $propertyAdd = new GridFieldAddNewInlineButton('toolbar-header-right'),
                    new GridFieldDeleteAction(false)
                );

            $propertyAdd->setTitle('Add Property');

            $fields->addFieldsToTab('Root.Main', [
                GridField::create(
                    'SchemaProperties',
                    'Schema Properties',
                    $this->SchemaProperties(),
                    $schemaConf
                ),
                LiteralField::create('SchemaSpace', '<br><br>')
            ]);

            // Objects
            $schemaGridConf = GridFieldConfig_RelationEditor::create();
            $fields->addFieldToTab('Root.Main', GridField::create(
                'SchemaObjects',
                'Schema Objects',
                $this->SchemaObjects(),
                $schemaGridConf
            ));
        } else {
            $fields->addFieldToTab('Root.Main', LiteralField::create('SchemaNotice', '<p class="message warning">You need to save before you can add properties or objects.</p>'));
        }

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    public function getSchemaCode(): string
    {
        $schemaData = [];

        if ($this->Type) {
            $schemaData['@type'] = $this->Type;
        }
    
        foreach ($this->SchemaProperties() as $property) {
            if ($property->Title) {
                $schemaData[$property->Title] = $property->Value;
            } else {
                $schemaData[] = $property->Value;
            }
        }

        if ($this->SchemaObjects()->count()) {
            foreach ($this->SchemaObjects() as $object) {
                $schemaData[$object->Title] = json_decode($object->getSchemaCode(), true);
            }
        }

    
        return json_encode($schemaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function canView($member = null): bool
    {
        return true;
    }

    public function canEdit($member = null): bool
    {
        return Permission::check('CMS_ACCESS_GoogleConfig', 'any', $member);
    }

    public function canCreate($member = null, $context = []): bool
    {
        return $this->canEdit($member);
    }

    public function canDelete($member = null): bool
    {
        return $this->canEdit($member);
    }
}
