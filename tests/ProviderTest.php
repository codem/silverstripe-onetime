<?php
namespace Codem\OneTime\Tests;

use Aws\Kms\Exception\KmsException;
use SilverStripe\Dev\SapphireTest;
use Exception;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class ProviderTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        TestProviderKmsDataObject::class
    ];

    /**
     * Test for the local provider, which shouldn't do anything to the values
     */
    public function testLocal()
    {
        $plain = "Just a test";
        $inst = new ProviderLocal();
        $encrypted = $inst->encrypt($plain);
        $this->assertEquals($encrypted, $plain);
        $decrypted = $inst->decrypt($encrypted);
        $this->assertEquals($decrypted, $plain);
    }

    /**
     * Test for the AmazonKMS provider
     */
    public function testAmazonKMS()
    {
        $plain = "Just a test";
        $inst = new ProviderAmazonKMS();
        // @returns base64 encoded string of the encrypted plain text
        $encrypted = $inst->encrypt($plain);
        // compare
        $re_encoded = base64_encode(base64_decode($encrypted));
        $this->assertEquals($encrypted, $re_encoded);
        // compare decryption
        $decrypted = $inst->decrypt($encrypted);
        $this->assertEquals($decrypted, $plain);

        // test with 4096 bytes of data
        $long_plain_string = str_repeat('a', 4096);
        // @returns base64 encoded string of the encrypted plain text
        $encrypted = $inst->encrypt($long_plain_string);
        // compare
        $re_encoded = base64_encode(base64_decode($encrypted));
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
        } catch (Exception $e) {
            $this->assertTrue($e instanceof KmsException);
        }
    }

    /**
     * Test for the AmazonKMS provider
     */
    public function testAmazonKMSWithContext()
    {
        $plain = "Test with context";
        $context = [
            'context_key' => 'context_value'
        ];
        $inst = new ProviderAmazonKMS;
        // @returns base64 encoded string of the encrypted plain text
        $encrypted = $inst->encrypt($plain, $context);
        // compare
        $re_encoded = base64_encode(base64_decode($encrypted));
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
        } catch (Exception $e) {
            //decrypting with an invalid context will throw a KmsException
            $this->assertTrue($e instanceof KmsException);
        }
    }
}

class TestProviderKmsDataObject extends DataObject implements TestOnly
{
    private static $secret_fields = array('FieldTestOne','FieldTestTwo');
    private static $secrets_provider = 'AmazonKMS';

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'TestProviderKmsDataObject';

    /**
     * Database fields
     * @var array
     */
    private static $db = array(
        'FieldTestOne' => 'Text',
        'FieldTestTwo' => 'Text',
    );
}
