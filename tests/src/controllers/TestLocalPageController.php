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
class TestLocalPageController extends PageController implements TestOnly
{
    private static $allowed_actions = [
        'Form'
    ];

    public function Form()
    {
        $test = new TestLocalDataObject();
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
        $test = new TestLocalDataObject($data);
        $test->write();

        $session = $this->getRequest()->getSession();
        $session->set('TestLocalDataObject_record', $test);
    }
}
