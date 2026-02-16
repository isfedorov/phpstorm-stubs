<?php

namespace StubTests\Framework\Runner;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use StubTests\Sources\Runner\PhpVersionRange;

class VersionRangeExtension implements PreparationStartedSubscriber
{

    public function notify(PreparationStarted $event): void
    {
        /** @var TestMethod $test */
        $test = $event->test();
        $ref = new \ReflectionMethod($test->className(), $test->methodName());
        if ($ref->getAttributes(PhpVersionRange::class)) {

        }
    }
}