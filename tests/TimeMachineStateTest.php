<?php declare(strict_types=1);

namespace Nucleware\TimeMachine\Tests;

use Nucleware\TimeMachine\TimeMachineState;
use PHPUnit\Framework\TestCase;

class TimeMachineStateTest extends TestCase
{
    public function testCreate()
    {
        /** @var \DateTime $begin */
        $begin = \DateTime::createFromFormat('Y-m-d H:i:s T', '2019-01-01 01:00:00 UTC');
        /** @var \DateTime $end */
        $end = \DateTime::createFromFormat('Y-m-d H:i:s T', '2019-01-01 02:00:00 UTC');

        $state = new TimeMachineState('state-name', $begin, $end);

        /** @var \DateTimeInterface $beginDateTime */
        $beginDateTime = $state->getBegin();
        /** @var \DateTimeInterface $endDateTime */
        $endDateTime = $state->getEnd();

        $this->assertNotNull($beginDateTime);
        $this->assertNotNull($endDateTime);
        $this->assertEquals($begin->getTimestamp(), $beginDateTime->getTimestamp());
        $this->assertEquals($end->getTimestamp(), $endDateTime->getTimestamp());
    }

    public function testReversed()
    {
        /** @var \DateTime $begin */
        $begin = \DateTime::createFromFormat('Y-m-d H:i:s T', '2019-01-01 01:00:00 UTC');
        /** @var \DateTime $end */
        $end = \DateTime::createFromFormat('Y-m-d H:i:s T', '2019-01-01 02:00:00 UTC');

        $this->expectException(\LogicException::class);

        $state = new TimeMachineState('state-name', $end, $begin);
    }

    public function testIsActive()
    {
        /** @var \DateTime $begin */
        $begin = \DateTime::createFromFormat('Y-m-d H:i:s T', '2019-01-01 01:00:00 UTC');
        /** @var \DateTime $end */
        $end = \DateTime::createFromFormat('Y-m-d H:i:s T', '2019-01-01 02:00:00 UTC');
        /** @var \DateTime $timeBefore */
        $timeBefore = \DateTime::createFromFormat('Y-m-d H:i:s T', '2019-01-01 00:00:00 UTC');
        /** @var \DateTime $timeDuring */
        $timeDuring = \DateTime::createFromFormat('Y-m-d H:i:s T', '2019-01-01 01:30:00 UTC');
        /** @var \DateTime $timeAfter */
        $timeAfter = \DateTime::createFromFormat('Y-m-d H:i:s T', '2019-01-01 02:30:00 UTC');

        $state = new TimeMachineState('state-name', $begin, $end);

        $this->assertFalse($state->isActive($timeBefore));
        $this->assertTrue($state->isActive($begin));
        $this->assertTrue($state->isActive($timeDuring));
        $this->assertFalse($state->isActive($end));
        $this->assertFalse($state->isActive($timeAfter));
    }

    public function testOpenEnded()
    {
        /** @var \DateTime $begin */
        $begin = \DateTime::createFromFormat('Y-m-d H:i:s T', '2019-01-01 01:00:00 UTC');
        /** @var \DateTime $end */
        $end = \DateTime::createFromFormat('Y-m-d H:i:s T', '2019-01-01 02:00:00 UTC');
        /** @var \DateTime $timeEpoch */
        $timeEpoch = \DateTime::createFromFormat('U', '0');
        /** @var \DateTime $timeEndOfTime */
        $timeEndOfTime = \DateTime::createFromFormat('U', (string)PHP_INT_MAX);

        $stateFirst = new TimeMachineState('state-first', null, $end);
        $stateLast = new TimeMachineState('state-last', $begin, null);
        $stateForever = new TimeMachineState('state-forever', null, null);

        $this->assertTrue($stateFirst->isActive($timeEpoch));
        $this->assertFalse($stateFirst->isActive($timeEndOfTime));
        $this->assertFalse($stateLast->isActive($timeEpoch));
        $this->assertTrue($stateLast->isActive($timeEndOfTime));
        $this->assertTrue($stateForever->isActive($timeEpoch));
        $this->assertTrue($stateForever->isActive($timeEndOfTime));
    }
}
