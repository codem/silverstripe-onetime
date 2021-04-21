<?php
namespace Codem\OneTime\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class TestClearPage extends SiteTree implements TestOnly
{
    private static $table_name = 'OneTimeTestClearPage';
}
