<?php
namespace Codem\OneTime\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

/**
 * DataObject for Local
 */
class TestClearLocalDataObject extends DataObject implements TestOnly
{

    private static $onetime_field_schema = [
        'FieldTestOne' => [ 'provider' => 'Local', 'partial' => false ],
        'FieldTestTwo' => [ 'provider' => 'Local', 'partial' => false ]
    ];

    private static $table_name = 'TestClearLocalDataObject';

    /**
     * Database fields
     * @var array
     */
    private static $db = array(
        'FieldTestOne' => 'Text',
        'FieldTestTwo' => 'Text',
    );
}
