<?php
namespace Codem\OneTime\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class TestLocalPage extends SiteTree implements TestOnly
{
    private static $table_name = 'OneTimeTestLocalPage';
}
