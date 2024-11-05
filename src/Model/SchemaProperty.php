<?php

namespace Iliain\GoogleConfig\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;

class SchemaProperty extends DataObject 
{
    /**
     * @var string
     */
    private static $table_name= 'SchemaProperty';

    /**
     * @var string
     */
    private static $singular_name = 'Property';

    /**
     * @var string
     */
    private static $plural_name = 'Properties';

    /**
     * @var array
     */
    private static $db = [
        'Title'     => 'Varchar(255)',
        'Value'     => 'Text',
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
        'SchemaProperties'  => SchemaProperty::class
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Title'             => 'Title',
    ];

    /**
     * @var array
     */
    private static $searchable_fields = [
        'Title'
    ];

    /**
     * @return FieldList
     */
    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }
}
