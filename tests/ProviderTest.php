<?php
namespace Codem\OneTime;
use Aws\Kms\Exception\KmsException;
/**
 * Test various providers within OneTime
 * Hint: /path/to/php -q ./vendor/bin/phpunit --filter testAmazonKMS ./onetime/tests/ProviderTest.php
 */
class ProviderTest extends \SapphireTest {
    
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
     * @todo support per request encryption context in tests
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
            // we should not be allowed to pass > 4096 byte strings to KMS
            $long_plain_string = str_repeat('a', 4097);
            // @returns base64 encoded string of the encrypted plain text
            $encrypted = $inst->encrypt($long_plain_string);
            // if here, maybe the limits have changed?
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue($e instanceof KmsException);
        }
        
    }
}