<?php

namespace Spiritix\NestedSet\Tests;

use Spiritix\NestedSet\NestedSet;

class NestedSetfTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceCreation()
    {
        $nestedSet = new NestedSet();
        $this->assertTrue($nestedSet instanceof NestedSet);
    }
}