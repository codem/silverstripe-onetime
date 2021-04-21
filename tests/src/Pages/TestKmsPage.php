<?php
namespace Codem\OneTime\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class TestKmsPage extends SiteTree implements TestOnly
{
    private static $table_name = 'OneTimeTestKmsPage';
}
