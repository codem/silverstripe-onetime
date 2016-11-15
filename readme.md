# OneTime

A Silverstripe module for storing private string values using a one-time operation.
Once the value is saved it is not shown again in the relevant field.

To update the value, add it to the relevant field and save the record, the old value will be overwritten.

To protect secure data in transmission, it's a wise idea to encrypt your site, or at least /admin, using an SSL certificate.

## Requirements
Per composer.json

## Providers
There are two providers currently supported

### Local
The values are stored in the local database in plain text and are not shown in the relevant fields.
This method helps to avoid users with CMS/Admin login privileges seeing secret values.

### AmazonKMS
The values are stored encrypted in the local database and are not shown in the relevant fields. Encryption and decryption is handled via the AWS client.

You can use the decrypted values in your application, for instance submitting a consumer secret to an API.

AmazonKMS requires an AWS Key, Secret Key, Key ID and AWS Region value to be available, add them to your site's configuration yaml like so:

```
Codem\OneTime\ProviderAmazonKMS:
  access_key: 'access_key'
  secret: 'secret'
  aws_region: 'an-aws-region'
  key_id: 'a-kms-keyid'
```

Encryption Context is optional and assists with logging of encrypt/decrypt requests (https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-kms-2014-11-01.html#encrypt)
If you want to use this option, add it to the above like so:
```
  encryption_context:
    AContextKey: 'some_context_value'
```

The IAM user with the relevant access_key and secret must have encrypt/decrypt privileges set up. You should not use your AWS root/admin user for this.

## LICENSE

Per composer.json
