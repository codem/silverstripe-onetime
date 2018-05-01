# OneTime

A Silverstripe 3.x module for storing private string values using a one-time operation.
Once the value is saved it is not shown again in the relevant field.

To update the value, add the plain text secret to the relevant field and save the record, the old value will be overwritten.

> To protect secure data in transmission it's a wise idea to encrypt your site using an SSL certificate

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

2. When the value of  'SomeSecret' is provided, that field will have a Right Title of 'A value exists for this configuration entry', the same will happen when a value for 'AnAPIPassword' is provided. A checkbox is then shown allowing you to clear the value.

## Providers
There are two providers currently supported

### Local
The values are stored in the local database in plain text and are not shown in the relevant fields.
This provider only helps to avoid users with certain CMS/Admin seeing secret values.

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

You may also not provide the access_key & secret and instead rely on other authentication methods provided by AWS.

The IAM user with the relevant access_key and secret must have encrypt/decrypt privileges set up. You should not use your AWS root/admin user for this.

#### Context

Encryption Context is optional and assists with logging of encrypt/decrypt requests.

If you want to use this option, add it to the above configuration like so:
```
  encryption_context:
    AContextKey: 'some_context_value'
```

Read [https://docs.aws.amazon.com/kms/latest/developerguide/encryption-context.html](Encryption Context documentation) at AWS for more information.

## Decrypting
When you wish to get the field value back, simply call decrypt() on your DataObject:
```
$plaintext = $instance->decrypt('SomeEncryptedSecret');
```
You can then use that value in your application, e.g by passing it to an API.

## Visibility of secrets on entry

Your secret values will be visible when entered into the field. If your admin/website is not hosted over a secure connection, they will be visible in transit.

## Field value truncation
Encrypted values will be longer than the plain text version entered into the field.

If your field is set as a Varchar field, you may experience truncation of the encrypted value when the database insert occurs. Rather than this module automatically changing your field types, it's recommended that you specify "Text" as the field type for the relevant fields.

You can then display the resulting ```TextareaField``` as a ```TextField``` in your DataObject's getCmsFields if required.

## LICENSE

Per composer.json
