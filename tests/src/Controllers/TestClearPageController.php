<?php
namespace Codem\OneTime\Tests;

use SilverStripe\Control\Session;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\Form;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use PageController;

/**
 * Controller for 'Local' saving
 */
class TestClearPageController extends PageController implements TestOnly
{
    private static $allowed_actions = [
        'Form'
    ];

    public function Form()
    {
        $session = $this->getRequest()->getSession();
        $test = $session->get('TestClearLocalDataObject_record');
        $fields = $test->getCmsFields();
        $actions = FieldList::create(
            FormAction::create('doSubmit')
        );

        $form = new Form(
            $this,
            'Form',
            $fields,
            $actions
        );

        return $form;
    }

    public function doSubmit($data, $form, $request)
    {
        $session = $this->getRequest()->getSession();
        $test = $session->get('TestClearLocalDataObject_record');
        // the act of writing the record will clear the values
        $test->write();
    }
}
