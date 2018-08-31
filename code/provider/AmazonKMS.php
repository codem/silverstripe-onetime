<?php
namespace Codem\OneTime;
use Aws\Kms\KmsClient as KmsClient;
use Config;
use Exception;
use SS_Log;

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
		// these are optional, if not provided SDK will attempt to get creds from metadata server
		$access_key = Config::inst()->get('Codem\OneTime\ProviderAmazonKMS', 'access_key');
		$secret = Config::inst()->get('Codem\OneTime\ProviderAmazonKMS', 'secret');

		// your AWS region
		$aws_region = Config::inst()->get('Codem\OneTime\ProviderAmazonKMS', 'aws_region');

		$args = [
			'region' => $aws_region,
			'version' => '2014-11-01',//lock to this version
			'proxy' => []
		];

		// access_key and secret provided
		if(!empty($access_key) && !empty($secret)) {
			$args['credentials'] = [
				'key' => $access_key,
				'secret' => $secret,
			];
		}

		// handle proxies
		$proxy = getenv('HTTP_PROXY');
		if($proxy) {
			SS_Log::log("Setting proxy {$proxy}", SS_Log::DEBUG);
			$args['http']['proxy'] = $proxy;
		}

		$kms = KmsClient::factory( $args );
		return $kms;
	}

	/**
	 * Ref: https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-kms-2014-11-01.html
	 * Ref: https://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/configuration.html
	 */
	public function encrypt($value, $encryption_context = array()) {
		$key_id = Config::inst()->get('Codem\OneTime\ProviderAmazonKMS', 'key_id');
		if(empty($key_id)) {
			throw new Exception("Cannot supply an empty key");
		}
		if(empty($encryption_context)) {
			$encryption_context = Config::inst()->get('Codem\OneTime\ProviderAmazonKMS', 'encryption_context');
		}
		$kms = $this->getClient();
		$args = [
			'KeyId' => $key_id,
			'Plaintext' => $value
		];
		if(!empty( $encryption_context ) && is_array($encryption_context)) {
			$args['EncryptionContext'] = $encryption_context;
		}
		$result = $kms->encrypt($args);
		return base64_encode($result->get('CiphertextBlob'));
	}

	/*
	 * https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-kms-2014-11-01.html#decrypt
	 */
	public function decrypt($encrypted_value, $encryption_context = array()) {

		$kms = $this->getClient();

		// Decrypt - should match $orig
		$args = [
		    'CiphertextBlob' => base64_decode($encrypted_value),
		];

		if(empty($encryption_context)) {
			$encryption_context = Config::inst()->get('Codem\OneTime\ProviderAmazonKMS', 'encryption_context');
		}

		if(!empty( $encryption_context ) && is_array($encryption_context)) {
			$args['EncryptionContext'] = $encryption_context;
		}
		$result = $kms->decrypt($args);
		return $result->get('Plaintext');
	}
}
