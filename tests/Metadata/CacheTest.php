<?php

namespace Nip\Database\Tests\Metadata;

use Nip\Database\Tests\AbstractTest;

/**
 * Class CacheTest
 * @package Nip\Database\Tests\Metadata
 */
class CacheTest extends AbstractTest
{

    public function testActiveByDefault()
    {
        $cache = new \Nip\Database\Metadata\Cache();

        self::assertTrue($cache->isActive());
    }
}
