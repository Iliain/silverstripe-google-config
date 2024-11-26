<?php

namespace Iliain\GoogleConfig\Admin;

use SilverStripe\Forms\Form;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormAction;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Forms\HiddenField;
use SilverStripe\View\Requirements;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ValidationResult;
use Iliain\GoogleConfig\Models\GoogleConfig;
use SilverStripe\Versioned\RecursivePublishable;

class GoogleLeftAndMain extends LeftAndMain
{
    /**
     * @var string
     */
    private static $url_segment = 'google';

    /**
     * @var string
     */
    private static $url_rule = '/$Action/$ID/$OtherID';

    /**
     * @var int
     */
    private static $menu_priority = -1;

    /**
     * @var string
     */
    private static $menu_title = 'Google';

    /**
     * @var string
     */
    private static $menu_icon_class = 'font-icon-globe';

    /**
     * @var string
     */
    private static $tree_class = GoogleConfig::class;

    /**
     * @var array
     */
    private static $required_permission_codes = 'CMS_ACCESS_GoogleConfig';

    /**
     * Initialises the {@link GoogleConfig} controller.
     */
    public function init()
    {
        parent::init();
        if (class_exists(SiteTree::class)) {
            Requirements::javascript('silverstripe/cms: client/dist/js/bundle.js');
        }
    }

    /**
     * @param null $id Not used.
     * @param null $fields Not used.
     *
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $googleConfig = GoogleConfig::current_google_config();
        $fields = $googleConfig->getCMSFields();

        // Tell the CMS what URL the preview should show
        $home = Director::absoluteBaseURL();
        $fields->push(new HiddenField('PreviewURL', 'Preview URL', $home));

        // Added in-line to the form, but plucked into different view by LeftAndMain.Preview.js upon load
        /** @skipUpgrade */
        $fields->push($navField = new LiteralField('SilverStripeNavigator', $this->getSilverStripeNavigator()));
        $navField->setAllowHTML(true);

        // Retrieve validator, if one has been setup (e.g. via data extensions).
        if ($googleConfig->hasMethod("getCMSValidator")) {
            $validator = $googleConfig->getCMSValidator();
        } else {
            $validator = null;
        }

        $actions = $googleConfig->getCMSActions();
        $negotiator = $this->getResponseNegotiator();
        /** @var Form $form */
        $form = Form::create(
            $this,
            'EditForm',
            $fields,
            $actions,
            $validator
        )->setHTMLID('Form_EditForm');
        $form->setValidationResponseCallback(function (ValidationResult $errors) use ($negotiator, $form) {
            $request = $this->getRequest();
            if ($request->isAjax() && $negotiator) {
                $result = $form->forTemplate();
                return $negotiator->respond($request, array(
                    'CurrentForm' => function () use ($result) {
                        return $result;
                    }
                ));
            }
        });
        $form->addExtraClass('flexbox-area-grow fill-height cms-content cms-edit-form');
        $form->setAttribute('data-pjax-fragment', 'CurrentForm');

        if ($form->Fields()->hasTabSet()) {
            $form->Fields()->findOrMakeTab('Root')->setTemplate('SilverStripe\\Forms\\CMSTabSet');
        }
        $form->setHTMLID('Form_EditForm');
        $form->loadDataFrom($googleConfig);
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

        // Use <button> to allow full jQuery UI styling
        $actions = $actions->dataFields();
        if ($actions) {
            /** @var FormAction $action */
            foreach ($actions as $action) {
                $action->setUseButtonTag(true);
            }
        }

        $this->extend('updateEditForm', $form);

        return $form;
    }

    /**
     * Save the current sites {@link SiteConfig} into the database.
     *
     * @param array $data
     * @param Form $form
     * @return String
     */
    public function save_googleconfig($data, $form)
    {
        $data = $form->getData();
        $googleConfig = DataObject::get_by_id(GoogleConfig::class, $data['ID']);
        $form->saveInto($googleConfig);
        $googleConfig->write();
        if ($googleConfig->hasExtension(RecursivePublishable::class)) {
            $googleConfig->publishRecursive();
        }
        $this->response->addHeader(
            'X-Status',
            rawurlencode(_t('SilverStripe\\Admin\\LeftAndMain.SAVEDUP', 'Saved.'))
        );
        return $form->forTemplate();
    }

    public function Breadcrumbs($unlinked = false)
    {
        return new ArrayList(array(
            new ArrayData(array(
                'Title' => static::menu_title(),
                'Link' => $this->Link()
            ))
        ));
    }
}
