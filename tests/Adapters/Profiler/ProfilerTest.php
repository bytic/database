<?php

namespace Nip\Database\Tests\Adapters\Profiler;

use Nip\Database\Adapters\Profiler\Profiler;
use Nip\Database\Adapters\Profiler\QueryProfile;
use Nip\Database\Tests\AbstractTest;

/**
 * Class ProfilerTest
 * @package Nip\Tests\Database\Adapters\Profiler
 */
class ProfilerTest extends AbstractTest
{


    public function testNewProfile()
    {
        $profile = $this->object->newProfile(36);

        self::assertInstanceOf(QueryProfile::class, $profile);
    }

    protected function setUp() : void
    {
        parent::setUp();
        $this->object = new Profiler();
    }
}
