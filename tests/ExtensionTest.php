<?php
namespace Codem\OneTime;

use Aws\Kms\Exception\KmsException;
use FunctionalTest;
use DataObject;
use Object;
use Session;
use DB;
use FieldList;
use FormAction;
use Form;
use Controller;
use TestOnly;

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

    protected $extraDataObjects = [
        TestKmsDataObject::class,
        TestLocalDataObject::class,
        TestClearLocalDataObject::class
    ];

    public function testKmsFormSubmission()
    {
        Object::add_extension('Codem\OneTime\TestKmsDataObject', 'Codem\OneTime\HasSecrets');

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

        // POST to the controller
        $form_id = 'Form_Form';
        $response = $this->post('Codem\OneTime\OneTimeKmsTestController', $data);
        $response = $this->submitForm(
            $form_id,
            'action_doSubmit',
            $data
        );

        // get submitted data from Controller
        $test = Session::get('TestKmsDataObject_record');

        $this->assertTrue(!empty($test->$one_fieldname));
        $this->assertTrue(!empty($test->$two_fieldname));

        $this->assertNotEquals($test->$one_fieldname, self::ONE_FIELDVALUE);
        $this->assertNotEquals($test->$two_fieldname, self::TWO_FIELDVALUE);

        $one_decrypted = $test->decrypt(self::ONE_FIELDNAME);
        $two_decrypted = $test->decrypt(self::TWO_FIELDNAME);

        // test decryption values match
        $this->assertEquals($one_decrypted, self::ONE_FIELDVALUE);
        $this->assertEquals($two_decrypted, self::TWO_FIELDVALUE);
    }

    public function testLocalFormSubmission()
    {
        Object::add_extension('Codem\OneTime\TestLocalDataObject', 'Codem\OneTime\HasSecrets');

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

        $form_id = 'Form_Form';
        $response = $this->post('Codem\OneTime\OneTimeLocalTestController', $data);
        $response = $this->submitForm(
            $form_id,
            'action_doSubmit',
            $data
        );

        // get submitted data from Controller
        $test = Session::get('TestLocalDataObject_record');

        $this->assertTrue(!empty($test->$one_fieldname));
        $this->assertTrue(!empty($test->$two_fieldname));

        $this->assertEquals($test->$one_fieldname, self::ONE_FIELDVALUE);
        $this->assertEquals($test->$two_fieldname, self::TWO_FIELDVALUE);

        $one_decrypted = $test->decrypt(self::ONE_FIELDNAME);
        $two_decrypted = $test->decrypt(self::TWO_FIELDNAME);

        // test decryption values match
        $this->assertEquals($one_decrypted, self::ONE_FIELDVALUE);
        $this->assertEquals($two_decrypted, self::TWO_FIELDVALUE);
    }

    /**
     * test that a values can be cleared
     */
    public function testClearValuesFormSubmission()
    {
        Object::add_extension('Codem\OneTime\TestClearLocalDataObject', 'Codem\OneTime\HasSecrets');


        $one_fieldname = self::ONE_FIELDNAME;
        $one_plain_value = self::ONE_FIELDVALUE;
        $two_fieldname = self::TWO_FIELDNAME;
        $two_plain_value = self::TWO_FIELDVALUE;

        // create a record (clear values only appear for existing records)
        $test = new TestClearLocalDataObject();
        $id = $test->write();

        // force store some values in the fields
        DB::Query("UPDATE `Codem\OneTime\TestClearLocalDataObject`\n"
              . " SET {$one_fieldname} = '" . $one_plain_value . "',"
              . " {$two_fieldname} = '" . $two_plain_value . "'"
              . " WHERE ID = {$id}");

        $test = TestClearLocalDataObject::get()->byId($id);
        $test_values = $test->getQueriedDatabaseFields();

        Session::set('TestClearLocalDataObject_record', $test);

        $this->assertEquals($one_plain_value, $test_values[ self::ONE_FIELDNAME ]);
        $this->assertEquals($two_plain_value, $test_values[ self::TWO_FIELDNAME ]);

        $clear_field_one = HasSecrets::getClearFieldName($one_fieldname);
        $clear_field_two = HasSecrets::getClearFieldName($two_fieldname);
        $input_field_one = HasSecrets::getAlteredFieldName($one_fieldname);
        $input_field_two = HasSecrets::getAlteredFieldName($two_fieldname);

        $data = [
            // these field values can be submitted, but the clear checkbox values will remove them
            $input_field_one => $one_plain_value,
            $input_field_two => $two_plain_value,
            $clear_field_one => 1,
            $clear_field_two => 1,
        ];

        $form_id = 'Form_Form';
        $response = $this->post('Codem\OneTime\OneTimeClearTestController', $data);
        $response = $this->submitForm(
            $form_id,
            'action_doSubmit',
            $data
        );

        // get submitted data from Controller
        $test = Session::get('TestClearLocalDataObject_record');

        $this->assertTrue(isset($test->$one_fieldname), "Field {$one_fieldname} is not set");
        $this->assertTrue(isset($test->$two_fieldname), "Field {$two_fieldname} is not set");
        $this->assertTrue($test->$one_fieldname === "", "Field {$one_fieldname} is not empty string");
        $this->assertTrue($test->$two_fieldname === "", "Field {$two_fieldname} is not empty string");
    }
}


/**
 * Controller handling saving of KMS data
 */
class OneTimeKmsTestController extends Controller implements TestOnly
{
    private static $allowed_actions = array(
        'Form'
    );

    protected $template = 'BlankPage';

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
        $test->write();
        Session::set('TestKmsDataObject_record', $test);
    }
}

/**
 * Test DataObject for KMS values
 */
class TestKmsDataObject extends DataObject implements TestOnly
{
    private static $secret_fields = array('FieldTestOne','FieldTestTwo');
    private static $secrets_provider = 'AmazonKMS';

    /**
     * Database fields
     * @var array
     */
    private static $db = array(
        'FieldTestOne' => 'Text',
        'FieldTestTwo' => 'Text',
    );
}


/**
 * Controller for 'Local' saving
 */
class OneTimeLocalTestController extends Controller implements TestOnly
{
    private static $allowed_actions = array(
        'Form'
    );

    protected $template = 'BlankPage';

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
        Session::set('TestLocalDataObject_record', $test);
    }
}


/**
 * DataObject for Local
 */
class TestLocalDataObject extends DataObject implements TestOnly
{
    private static $secret_fields = array('FieldTestOne','FieldTestTwo');
    private static $secrets_provider = 'Local';

    /**
     * Database fields
     * @var array
     */
    private static $db = array(
        'FieldTestOne' => 'Text',
        'FieldTestTwo' => 'Text',
    );
}


/**
 * Controller for 'Local' saving
 */
class OneTimeClearTestController extends Controller implements TestOnly
{
    private static $allowed_actions = array(
        'Form'
    );

    protected $template = 'BlankPage';

    public function Form()
    {
        $test = Session::get('TestClearLocalDataObject_record');
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
        $test = Session::get('TestClearLocalDataObject_record');
        $test->write();
    }
}


/**
 * DataObject for Local
 */
class TestClearLocalDataObject extends DataObject implements TestOnly
{
    private static $secret_fields = array('FieldTestOne','FieldTestTwo');
    private static $secrets_provider = 'Local';

    /**
     * Database fields
     * @var array
     */
    private static $db = array(
        'FieldTestOne' => 'Text',
        'FieldTestTwo' => 'Text',
    );
}
