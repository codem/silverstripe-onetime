# OneTime

A Silverstripe module for storing private string values using a one-time operation.
Once the value is saved it is not shown again in the relevant field.

To update the value, add it to the relevant field and save the record, the old value will be overwritten.

To protect secure data in transmission it's a wise idea to encrypt your site, or at least /admin, using an SSL certificate.

## Requirements
Per composer.json

## Marking fields in DataObjects
In your site/module Yaml configuration, assign the following extension to the relevant DataObjects:

```
MyDataObject:
  extensions:
    - Codem\OneTime\HasSecrets
AnotherDataObject:
  extensions:
    - Codem\OneTime\HasSecrets
```

In the relevant DataObjects, set up private statics to mark certain fields as being handled by the module:
```
<?php
class MyDataObject extends DataObject {

  private static $secret_fields = array('SomeSecret','AnAPIPassword');
  private static $secrets_provider = "AmazonKMS";// other value is 'Local'

  // etc
}
```

Run a flush=1 so that Silverstripe can pick up the new statics and the open the relevant screen in the admin showing your DataObject EditForm.
If all goes well you should see the following, using the example above:

1. When the values of 'SomeSecret' or 'AnAPIPassword' have not been provided, the field will have a Right Title of 'No value exists yet for this configuration entry'

2. When the value of  'SomeSecret' is provided, that field will have a Right Title of 'A value exists for this configuration entry', the same will happen when a value for 'AnAPIPassword' is provided.

## Providers
There are two providers currently supported

### Local
The values are stored in the local database in plain text and are not shown in the relevant fields.
This method helps to avoid users with CMS/Admin login privileges seeing secret values.

### AmazonKMS
The values are stored encrypted in the local database and are not shown in the relevant fields. Encryption and decryption is handled via the AWS client.

You can use the decrypted values in your application, for instance submitting a consumer secret to an API.

AmazonKMS requires an AWS Key, Secret Key, Key ID and AWS Region value to be available, add them to your site's configuration Yaml like so:

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

## Field value truncation
Encrypted values will be longer than the plain text version entered into the field.

If your field is set as a Varchar field, you may experience truncation of the encrypted value when the database insert occurs. Rather than this module automatically changing your field types, it's recommended that you specify "Text" as the field type for the relevant fields.

You can then cast the resulting TextareaField as a TextField in your DataObject's getCmsFields if required.

## LICENSE

Per composer.json
