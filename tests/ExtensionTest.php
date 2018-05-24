<?php
namespace Codem\OneTime;
use Aws\Kms\Exception\KmsException;

/**
 * Functional Tests for HasSecrets
 */
class ExtensionTest extends \FunctionalTest {

  const ONE_FIELDVALUE = 'Plain Text One';
  const TWO_FIELDVALUE = 'Plain Text Two';

  const ONE_FIELDNAME = 'FieldTestOne';
  const TWO_FIELDNAME = 'FieldTestTwo';

  protected $usesDatabase = true;

  public function testKmsFormSubmission() {

    \Object::add_extension('Codem\OneTime\TestKmsDataObject','Codem\OneTime\HasSecrets');

    $test = new TestKmsDataObject();

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

    $response = $this->get('Codem\OneTime\OneTimeKmsTestController');
    $response = $this->submitForm(
      $form_id,
      'action_doSubmit',
      $data
    );

    // get submitted data from Controller
    $submission_data = \Session::get('TestKmsDataObject_data');

    $this->assertTrue( is_array($submission_data) );

    $one_fieldname = HasSecrets::getAlteredFieldName(self::ONE_FIELDNAME);
    $two_fieldname = HasSecrets::getAlteredFieldName(self::TWO_FIELDNAME);
    $this->assertTrue( array_key_exists( $one_fieldname, $submission_data) );
    $this->assertTrue( array_key_exists( $two_fieldname, $submission_data) );

    $this->assertEquals( $submission_data[ $one_fieldname ], self::ONE_FIELDVALUE );
    $this->assertEquals( $submission_data[ $two_fieldname ], self::TWO_FIELDVALUE );

    $test->$one_fieldname = $submission_data[ $one_fieldname ];
    $test->$two_fieldname = $submission_data[ $two_fieldname ];

    $id = $test->write();

    // test onBefore and onAfterWrite has performed
    $record = TestKmsDataObject::get()->byId($id);
    $this->assertTrue( $record instanceof TestKmsDataObject);

    // get values saved
    $values = $record->getQueriedDatabaseFields();

    $this->assertTrue( !empty($values[ self::ONE_FIELDNAME ] ) );
    $this->assertTrue( !empty($values[ self::TWO_FIELDNAME ] ) );

    $one_decrypted = $record->decrypt( self::ONE_FIELDNAME );
    $two_decrypted = $record->decrypt( self::TWO_FIELDNAME );

    // test decryption values match
    $this->assertEquals( $one_decrypted,  self::ONE_FIELDVALUE );
    $this->assertEquals( $two_decrypted,  self::TWO_FIELDVALUE );

  }

  public function testLocalFormSubmission() {

    \Object::add_extension('Codem\OneTime\TestLocalDataObject','Codem\OneTime\HasSecrets');

    $test = new TestLocalDataObject();

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

    $response = $this->get('Codem\OneTime\OneTimeLocalTestController');
    $response = $this->submitForm(
      $form_id,
      'action_doSubmit',
      $data
    );

    // get submitted data from Controller
    $submission_data = \Session::get('TestLocalDataObject_data');

    //\Session::clear('TestLocalDataObject_data');
    $this->assertTrue( is_array($submission_data) );

    $one_fieldname = HasSecrets::getAlteredFieldName(self::ONE_FIELDNAME);
    $two_fieldname = HasSecrets::getAlteredFieldName(self::TWO_FIELDNAME);
    $this->assertTrue( array_key_exists( $one_fieldname, $submission_data) );
    $this->assertTrue( array_key_exists( $two_fieldname, $submission_data) );

    $this->assertEquals( $submission_data[ $one_fieldname ], self::ONE_FIELDVALUE );
    $this->assertEquals( $submission_data[ $two_fieldname ], self::TWO_FIELDVALUE );

    $test->$one_fieldname = $submission_data[ $one_fieldname ];
    $test->$two_fieldname = $submission_data[ $two_fieldname ];

    $id = $test->write();

    // test onBefore and onAfterWrite has performed
    $record = TestLocalDataObject::get()->byId($id);
    $this->assertTrue( $record instanceof TestLocalDataObject);

    // get values saved
    $values = $record->getQueriedDatabaseFields();

    $this->assertTrue( !empty($values[ self::ONE_FIELDNAME ] ) );
    $this->assertTrue( !empty($values[ self::TWO_FIELDNAME ] ) );

    $one_decrypted = $record->decrypt( self::ONE_FIELDNAME );
    $two_decrypted = $record->decrypt( self::TWO_FIELDNAME );

    // test decryption values match
    $this->assertEquals( $one_decrypted,  self::ONE_FIELDVALUE );
    $this->assertEquals( $two_decrypted,  self::TWO_FIELDVALUE );


  }

  /**
   * test that a values can be cleared
   */
  public function testClearValuesFormSubmission() {

    \Object::add_extension('Codem\OneTime\TestLocalDataObject','Codem\OneTime\HasSecrets');

    $test = new TestLocalDataObject();

    $one_fieldname = self::ONE_FIELDNAME;
    $one_plain_value = self::ONE_FIELDVALUE;
    $two_fieldname = self::TWO_FIELDNAME;
    $two_plain_value = self::TWO_FIELDVALUE;

    $id = $test->write();

    // force store some values in the fields
    \DB::Query("UPDATE `Codem\OneTime\TestLocalDataObject`\n"
              . " SET {$one_fieldname} = '" . $one_plain_value . "',"
              . " {$two_fieldname} = '" . $two_plain_value . "'"
              . " WHERE ID = {$id}");

    $test = TestLocalDataObject::get()->byId($id);
    $test_values = $test->getQueriedDatabaseFields();

    $this->assertEquals( $one_plain_value, $test_values[ self::ONE_FIELDNAME ] );
    $this->assertEquals( $two_plain_value, $test_values[ self::TWO_FIELDNAME ] );

    \Session::set('TestLocalDataObject_record', $test);

    $data = [
      $one_fieldname . '_CLEAR' => 1,
      $two_fieldname . '_CLEAR' => 1,
    ];

    \Session::clear('TestLocalDataObject_data');

    $form_id = 'Form_Form';
    $response = $this->get('Codem\OneTime\OneTimeLocalTestController');
    $response = $this->submitForm(
      $form_id,
      'action_doSubmit',
      $data
    );

    \Session::clear('TestLocalDataObject_record');

    // get submitted data from Controller
    $submission_data = \Session::get('TestLocalDataObject_data');
    \Session::clear('TestLocalDataObject_data');

    $this->assertTrue( is_array($submission_data) );

    $one_fieldname = HasSecrets::getAlteredFieldName(self::ONE_FIELDNAME);
    $two_fieldname = HasSecrets::getAlteredFieldName(self::TWO_FIELDNAME);

    $this->assertTrue( array_key_exists( self::ONE_FIELDNAME . '_CLEAR', $submission_data) );
    $this->assertTrue( array_key_exists( self::TWO_FIELDNAME . '_CLEAR', $submission_data) );

    $this->assertEquals( $submission_data[ self::ONE_FIELDNAME . '_CLEAR' ], 1 );
    $this->assertEquals( $submission_data[ self::TWO_FIELDNAME . '_CLEAR' ], 1 );

    // _CLEAR means clear the values
    $id = $test->write();

    $values = $test->getQueriedDatabaseFields();

    // check values are no longer available
    $this->assertTrue( empty($values[ self::ONE_FIELDNAME ] ) );
    $this->assertTrue( empty($values[ self::TWO_FIELDNAME ] ) );

  }
}


/**
 * Controller handling saving of KMS data
 */
class OneTimeKmsTestController extends \Controller implements \TestOnly {

    private static $allowed_actions = array(
        'Form'
    );

    protected $template = 'BlankPage';

    public function Form() {

      $test = new TestKmsDataObject();
      $fields = $test->getCmsFields();
      $actions = \FieldList::create(
        \FormAction::create('doSubmit')
      );

      $form = new \Form(
          $this,
          'Form',
          $fields,
          $actions
      );
      $form->saveInto($test);

      return $form;
    }

    public function doSubmit($data, $form, $request) {
      \Session::set('TestKmsDataObject_data', $data);
      return $this->redirectBack();
    }
}

/**
 * Test DataObject for KMS values
 */
class TestKmsDataObject extends \DataObject {
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
class OneTimeLocalTestController extends \Controller implements \TestOnly {

    private static $allowed_actions = array(
        'Form'
    );

    protected $template = 'BlankPage';

    public function Form() {

      $test = \Session::get('TestLocalDataObject_record');
      if(empty($test->ID)) {
        // if not create an empty one
        $test = new TestLocalDataObject();
      }
      $fields = $test->getCmsFields();
      //print_r($fields->dataFields());
      $actions = \FieldList::create(
        \FormAction::create('doSubmit')
      );

      $form = new \Form(
          $this,
          'Form',
          $fields,
          $actions
      );
      $form->saveInto($test);

      return $form;
    }

    public function doSubmit($data, $form, $request) {
      \Session::set('TestLocalDataObject_data', $data);
      return $this->redirectBack();
    }
}


/**
 * DataObject for Local
 */
class TestLocalDataObject extends \DataObject {
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
