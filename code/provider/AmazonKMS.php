<?php
namespace Codem\OneTime;
use Codem\OneTime\BaseProvider as BaseProvider;
use Aws\Kms\KmsClient as KmsClient;
/**
 * AmazonKMS provider for encrypting and decrypting data
 * Configure the class properties in your yml
 * @todo support Aws::factory('/path/to/my_config.json');
 */
class ProviderAmazonKMS extends BaseProvider {

	private static $access_key = "";
	private static $secret = "";
	private static $aws_region = "";
	private static $key_id = "";
	private static $encryption_context = array();

	private function getClient() {
		$access_key = \Config::inst()->get('Codem\OneTime\ProviderAmazonKMS', 'access_key');
		$secret = \Config::inst()->get('Codem\OneTime\ProviderAmazonKMS', 'secret');
		$aws_region = \Config::inst()->get('Codem\OneTime\ProviderAmazonKMS', 'aws_region');

		$args = [
			'credentials' => [
				'key' => $access_key,
				'secret' => $secret,
			],
			'region' => $aws_region,
			'version' => '2014-11-01',//lock to this version
			'proxy' => []
		];

		// handle proxies
		$proxy = getenv('HTTP_PROXY');
		if($proxy) {
			$args['http']['proxy'] = $proxy;
		}

		$kms = KmsClient::factory( $args );
		return $kms;
	}

	/**
	 * Ref: https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-kms-2014-11-01.html
	 * Ref: https://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/configuration.html
	 */
	public function encrypt($value) {
		$key_id = \Config::inst()->get('Codem\OneTime\ProviderAmazonKMS', 'key_id');
		$encryption_context = \Config::inst()->get('Codem\OneTime\ProviderAmazonKMS', 'encryption_context');
		$kms = $this->getClient();
		$args = [
			'KeyId' => $key_id,
			'Plaintext' => $value
		];
		if(!empty( $encryption_context ) && is_array($encryption_context)) {
			$args['EncryptionContext'] = $encryption_context;
		}
		\SS_Log::log("Encrypting value", \SS_Log::DEBUG);
		$result = $kms->encrypt($args);
		return base64_encode($result->get('CiphertextBlob'));
	}

	/*
	 * https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-kms-2014-11-01.html#decrypt
	 */
	public function decrypt($encrypted_value) {

		$kms = $this->getClient();

		// Decrypt - should match $orig
		$args = [
		    'CiphertextBlob' => base64_decode($encrypted_value),
		];

		$encryption_context = \Config::inst()->get('Codem\OneTime\ProviderAmazonKMS', 'encryption_context');
		if(!empty( $encryption_context ) && is_array($encryption_context)) {
			$args['EncryptionContext'] = $encryption_context;
		}
		\SS_Log::log("Decrypting value", \SS_Log::DEBUG);
		$result = $kms->decrypt($args);
		return $result->get('Plaintext');
	}
}
