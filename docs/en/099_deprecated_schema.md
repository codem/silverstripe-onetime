# Deprecated schema

This older schema is deprecated and will be removed in future versions

Use the current schema to have finer per-field controls.

```php
<?php

namespace MyOrg;

use SilverStripe\ORM\DataObject;

class MyDataObject extends DataObject {

    private static $secret_fields = [
        'SomeSecret', // a field called 'SomeSecret'
        'AnAPIPassword'// nother field called 'AnAPIPassword'
    ];
    
    
    private static $secrets_provider = "AmazonKMS";// this record uses the AmazonKMS provider

    // etc
}
```
