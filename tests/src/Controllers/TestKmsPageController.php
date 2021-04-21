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
 * Controller handling saving of KMS data
 */
class TestKmsPageController extends PageController implements TestOnly
{
    private static $allowed_actions = [
        'Form'
    ];

    public function Form()
    {
        $test = TestKmsDataObject::create();
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
        $test = new TestKmsDataObject($data);
        // writing this will call encrypt()
        $test->write();
        $session = $this->getRequest()->getSession();
        $session->set('TestKmsDataObject_record', $test);
    }
}
