<?php
namespace Codem\OneTime;
use Aws\Kms\Exception\KmsException;

class ProviderTest extends \SapphireTest {

  protected $usesDatabase = true;
  
  protected $extraDataObjects = [
    TestProviderKmsDataObject::class
  ];

  /**
   * Test for the local provider, which shouldn't do anything to the values
   */
  public function testLocal() {
      $plain = "Just a test";
      $inst = new Provider\Local;
      $encrypted = $inst->encrypt($plain);
      $this->assertEquals($encrypted, $plain);
      $decrypted = $inst->decrypt($encrypted);
      $this->assertEquals($decrypted, $plain);
  }

  /**
   * Test for the AmazonKMS provider
   */
  public function testAmazonKMS() {
      $plain = "Just a test";
      $inst = new Provider\AmazonKMS;
      // @returns base64 encoded string of the encrypted plain text
      $encrypted = $inst->encrypt($plain);
      // compare
      $re_encoded = base64_encode(base64_decode( $encrypted ));
      $this->assertEquals($encrypted, $re_encoded);
      // compare decryption
      $decrypted = $inst->decrypt($encrypted);
      $this->assertEquals($decrypted, $plain);

      // test with 4096 bytes of data
      $long_plain_string = str_repeat('a', 4096);
      // @returns base64 encoded string of the encrypted plain text
      $encrypted = $inst->encrypt($long_plain_string);
      // compare
      $re_encoded = base64_encode(base64_decode( $encrypted ));
      $this->assertEquals($encrypted, $re_encoded);
      // compare decryption
      $decrypted = $inst->decrypt($encrypted);
      $this->assertEquals($decrypted, $long_plain_string);

      try {
          //this should fail with an Aws\Kms\Exception\KmsException
          // test with 4096 bytes of data
          $long_plain_string = str_repeat('a', 4097);
          // @returns base64 encoded string of the encrypted plain text
          $encrypted = $inst->encrypt($long_plain_string);
          // we should not be allowed to pass > 4096 byte strings to KMS
          $this->assertEquals(false, true);
      } catch (\Exception $e) {
          $this->assertTrue($e instanceof KmsException);
      }

  }

  /**
   * Test for the AmazonKMS provider
   */
  public function testAmazonKMSWithContext() {
      $plain = "Test with context";
      $context = [
        'context_key' => 'context_value'
      ];
      $inst = new Provider\AmazonKMS;
      // @returns base64 encoded string of the encrypted plain text
      $encrypted = $inst->encrypt($plain, $context);
      // compare
      $re_encoded = base64_encode(base64_decode( $encrypted ));
      $this->assertEquals($encrypted, $re_encoded);

      // compare decryption
      $decrypted = $inst->decrypt($encrypted, $context);
      $this->assertEquals($decrypted, $plain);

      try {
        $invalid_context = [
          'context_key' => 'some_other_value'
        ];
        $decrypted = $inst->decrypt($encrypted, $invalid_context);
        //we should not be here
        $this->assertEquals(false, true);
      } catch (\Exception $e) {
        //decrypting with an invalid context will throw a KmsException
        $this->assertTrue($e instanceof KmsException);
      }

  }

  public function testExtension() {

    \Object::add_extension('Codem\OneTime\TestProviderKmsDataObject','Codem\OneTime\HasSecrets');

    $test = new TestProviderKmsDataObject();

    $one_fieldname = 'FieldTestOne';
    $one_plain_value = 'Plain Text One';
    $two_fieldname = 'FieldTestTwo';
    $two_plain_value = 'Plain Text Two';

    $input_field_one = HasSecrets::getAlteredFieldName($one_fieldname);
    $input_field_two = HasSecrets::getAlteredFieldName($two_fieldname);

    $test->$input_field_one = $one_plain_value;
    $test->$input_field_two = $two_plain_value;
    $id = $test->write();

    $this->assertNotNull($id);

    $record = TestProviderKmsDataObject::get()->byId($id);

    $this->assertTrue($record && $record instanceof TestProviderKmsDataObject);

    $one_decrypted = $record->decrypt($one_fieldname);
    $this->assertEquals($one_decrypted, $one_plain_value);

    $two_decrypted = $record->decrypt($two_fieldname);
    $this->assertEquals($two_decrypted, $two_plain_value);

  }
}

class TestProviderKmsDataObject extends \DataObject implements \TestOnly {
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
