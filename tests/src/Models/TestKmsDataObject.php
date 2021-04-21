<?php
namespace Codem\OneTime\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

/**
 * Test DataObject for KMS values
 */
class TestKmsDataObject extends DataObject implements TestOnly
{

    private static $onetime_field_schema = [
        'FieldTestOne' => [ 'provider' => 'AmazonKMS', 'partial' => false ],
        'FieldTestTwo' => [ 'provider' => 'AmazonKMS', 'partial' => false ]
    ];

    private static $table_name = 'TestKmsDataObject';

    /**
     * Database fields
     * @var array
     */
    private static $db = array(
        'FieldTestOne' => 'Text',
        'FieldTestTwo' => 'Text',
    );
}
