<?php
namespace Codem\OneTime\Tests;

use Codem\OneTime\HasSecrets;
use Codem\OneTime\PartialValue;
use Aws\Kms\Exception\KmsException;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Session;
use SilverStripe\ORM\DB;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\Form;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;

/**
 * Functional Tests for HasSecrets
 */
class ExtensionTest extends FunctionalTest
{
    const ONE_FIELDVALUE = 'Plain Text One';
    const TWO_FIELDVALUE = 'Plain Text Two';

    const ONE_FIELDNAME = 'FieldTestOne';
    const TWO_FIELDNAME = 'FieldTestTwo';

    protected $usesDatabase = true;

    protected static $fixture_file = "ExtensionTest.yml";

    protected $theme_base_dir = '/vendor/MrJamesEllis/silverstripe-onetime/tests';// TODO another way?

    protected static $extra_dataobjects = [
        TestKmsDataObject::class,
        TestLocalDataObject::class,
        TestClearLocalDataObject::class,
        TestLocalPage::class,
        TestClearPage::class,
        TestKmsPage::class,
    ];

    public function testKmsFormSubmission()
    {

        $test = $this;

        $this->useTestTheme($this->theme_base_dir, 'onetimetest', function () use ($test) {

            TestKmsDataObject::add_extension( HasSecrets::class );

            $one_fieldname = self::ONE_FIELDNAME;
            $one_plain_value = self::ONE_FIELDVALUE;
            $two_fieldname = self::TWO_FIELDNAME;
            $two_plain_value = self::TWO_FIELDVALUE;

            $input_field_one = HasSecrets::getAlteredFieldName($one_fieldname);
            $input_field_two = HasSecrets::getAlteredFieldName($two_fieldname);

            $data = [
                $input_field_one => $one_plain_value,
                $input_field_two => $two_plain_value,
            ];

            $page = TestKmsPage::get()->filter('URLSegment','kms-test-page')->first();
            $page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

            // POST to the controller
            $form_id = 'Form_Form';
            $response = $test->get( $page->Link() );
            $response = $test->submitForm(
                $form_id,
                'action_doSubmit',
                $data
            );

            // get submitted data from Controller
            $session = Controller::curr()->getRequest()->getSession();
            $record = $session->get('TestKmsDataObject_record');

            $this->assertTrue($record instanceof TestKmsDataObject);

            $test->assertTrue(isset($record->$one_fieldname) && $record->$one_fieldname !== "");
            $test->assertTrue(isset($record->$two_fieldname) && $record->$two_fieldname !== "");

            // the encrypted values must not match the plain text values
            $test->assertNotEquals($record->$one_fieldname, self::ONE_FIELDVALUE);
            $test->assertNotEquals($record->$two_fieldname, self::TWO_FIELDVALUE);

            $one_decrypted = $record->decrypt(self::ONE_FIELDNAME);
            $two_decrypted = $record->decrypt(self::TWO_FIELDNAME);

            // test decryption values match plaintext values
            $test->assertEquals($one_decrypted, self::ONE_FIELDVALUE);
            $test->assertEquals($two_decrypted, self::TWO_FIELDVALUE);

        });

    }

    public function testLocalFormSubmission()
    {

        $test = $this;

        $this->useTestTheme($this->theme_base_dir, 'onetimetest', function () use ($test) {

            TestLocalDataObject::add_extension( HasSecrets::class );

            $one_fieldname = self::ONE_FIELDNAME;
            $one_plain_value = self::ONE_FIELDVALUE;
            $two_fieldname = self::TWO_FIELDNAME;
            $two_plain_value = self::TWO_FIELDVALUE;

            $input_field_one = HasSecrets::getAlteredFieldName($one_fieldname);
            $input_field_two = HasSecrets::getAlteredFieldName($two_fieldname);

            $data = [
                $input_field_one => $one_plain_value,
                $input_field_two => $two_plain_value,
            ];

            $page = TestLocalPage::get()->filter('URLSegment','local-test-page')->first();
            $page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

            $form_id = 'Form_Form';
            // switch to page
            $response = $test->get( $page->Link() );
            // submit form
            $response = $test->submitForm(
                $form_id,
                'action_doSubmit',
                $data
            );

            // get submitted data from Controller
            $session = Controller::curr()->getRequest()->getSession();
            $record = $session->get('TestLocalDataObject_record');
            $this->assertTrue($record instanceof TestLocalDataObject);

            $test->assertTrue(!empty($record->$one_fieldname));
            $test->assertTrue(!empty($record->$two_fieldname));

            $test->assertEquals($record->$one_fieldname, self::ONE_FIELDVALUE);
            $test->assertEquals($record->$two_fieldname, self::TWO_FIELDVALUE);

            $one_decrypted = $record->decrypt(self::ONE_FIELDNAME);
            $two_decrypted = $record->decrypt(self::TWO_FIELDNAME);

            // test decryption values match
            $test->assertEquals($one_decrypted, self::ONE_FIELDVALUE);
            $test->assertEquals($two_decrypted, self::TWO_FIELDVALUE);

        });

    }

    /**
     * test that a values can be cleared
     */
    public function testClearValuesFormSubmission()
    {
        $test = $this;

        $this->useTestTheme($this->theme_base_dir, 'onetimetest', function () use ($test) {

            TestClearLocalDataObject::add_extension( HasSecrets::class );

            $one_fieldname = self::ONE_FIELDNAME;
            $one_plain_value = self::ONE_FIELDVALUE;
            $two_fieldname = self::TWO_FIELDNAME;
            $two_plain_value = self::TWO_FIELDVALUE;

            // create a record (clear values only appear for existing records)
            $test_object = new TestClearLocalDataObject();
            $id = $test_object->write();

            // force store some values in the fields
            DB::Query("UPDATE `TestClearLocalDataObject`\n"
                  . " SET {$one_fieldname} = '" . $one_plain_value . "',"
                  . " {$two_fieldname} = '" . $two_plain_value . "'"
                  . " WHERE ID = {$id}");

            $test_object = TestClearLocalDataObject::get()->byId($id);
            $test_values = $test_object->getQueriedDatabaseFields();

            $session = Controller::curr()->getRequest()->getSession();

            $session->set('TestClearLocalDataObject_record', $test_object);

            $test->assertEquals($one_plain_value, $test_values[ self::ONE_FIELDNAME ]);
            $test->assertEquals($two_plain_value, $test_values[ self::TWO_FIELDNAME ]);

            $clear_field_one = HasSecrets::getClearFieldName($one_fieldname);
            $clear_field_two = HasSecrets::getClearFieldName($two_fieldname);
            $input_field_one = HasSecrets::getAlteredFieldName($one_fieldname);
            $input_field_two = HasSecrets::getAlteredFieldName($two_fieldname);

            $data = [
                // these field values can be submitted, but the clear checkbox values will remove them
                $input_field_one => $one_plain_value,
                $input_field_two => $two_plain_value,
                // clear checkbox values
                $clear_field_one => 1,
                $clear_field_two => 1,
            ];

            $page = TestClearPage::get()->filter('URLSegment','clear-test-page')->first();
            $page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

            $form_id = 'Form_Form';
            // switch to page
            $response = $test->get( $page->Link() );
            // submit form
            $response = $test->submitForm(
                $form_id,
                'action_doSubmit',
                $data
            );

            // get submitted data from Controller - at this point the values should be cleared
            $record = $session->get('TestClearLocalDataObject_record');

            // the field names should be present
            $test->assertTrue(isset($record->$one_fieldname), "Field {$one_fieldname} is not set");
            $test->assertTrue(isset($record->$two_fieldname), "Field {$two_fieldname} is not set");
            // but the values must be an empty string
            $test->assertTrue($record->$one_fieldname === "", "Field {$one_fieldname} is not empty string");
            $test->assertTrue($record->$two_fieldname === "", "Field {$two_fieldname} is not empty string");

        });

    }
}
