<?php declare(strict_types=1);

namespace Nucleware\TimeMachine\Tests;

use Nucleware\TimeMachine\TimeMachine;
use Nucleware\TimeMachine\TimeMachineState;
use PHPUnit\Framework\TestCase;

class TimeMachineTest extends TestCase
{
    public function testCreate()
    {
        $machine = new TimeMachine([
            new TimeMachineState('state-name', null, null),
        ]);
        $this->assertEquals(true, $machine->isState('state-name'));
    }

    public function testCreateEmpty()
    {
        $this->expectException(\LogicException::class);

        $machine = new TimeMachine([]);
    }

    public function testIntervals()
    {
        $machine = new TimeMachine([
            new TimeMachineState('first',
                null,
                $this->createDateTime('Y-m-d H:i:s T', '2019-01-01 01:00:00 UTC')),
            new TimeMachineState('state-1',
                $this->createDateTime('Y-m-d H:i:s T', '2019-01-01 01:00:00 UTC'),
                $this->createDateTime('Y-m-d H:i:s T', '2019-01-02 01:00:00 UTC')),
            new TimeMachineState('last',
                $this->createDateTime('Y-m-d H:i:s T', '2019-01-02 01:00:00 UTC'),
                null),
        ]);

        $timeEpoch = $this->createDateTime('U', '0');
        $timeEndOfTime = $this->createDateTime('U', (string)PHP_INT_MAX);

        $tests[] = [
            'time' => $timeEpoch,
            'is' => 'first',
            'before' => ['first' => false, 'state-1' => true, 'last' => true],
            'after'=> ['first' => false, 'state-1' => false, 'last' => false],
            'interval' => [['first', 'state-1', true], ['state-1', 'last', false]],
        ];

        $tests[] = [
            'time' => $timeEndOfTime,
            'is' => 'last',
            'before' => ['first' => false, 'state-1' => false, 'last' => false],
            'after' => ['first' => true, 'state-1' => true, 'last' => false],
            'interval' => [['first', 'state-1', false], ['state-1', 'last', true]],
        ];

        $tests[] = [
            'time' => $this->createDateTime('Y-m-d H:i:s T', '2019-01-01 12:00:00 UTC'),
            'is' => 'state-1',
            'before' => ['first' => false, 'state-1' => false, 'last' => true],
            'after' => ['first' => true, 'state-1' => false, 'last' => false],
            'interval' => [['first', 'state-1', true], ['state-1', 'last', true]],
        ];

        foreach ($tests as $test) {
            $machine->setDateTime($test['time']);
            $this->assertTrue($machine->isState($test['is']));
            foreach ($test['before'] as $state => $expect) {
                $this->assertEquals($expect, $machine->isBefore($state));
            }
            foreach ($test['after'] as $state => $expect) {
                $this->assertEquals($expect, $machine->isAfter($state));
            }
            $this->assertTrue($machine->isInInterval('first', 'last'));
            foreach ($test['interval'] as $testVals) {
                $this->assertEquals($testVals[2], $machine->isInInterval($testVals[0], $testVals[1]));
            }
        }
    }

    public function testFinite()
    {
        $machine = new TimeMachine([
            new TimeMachineState('finite',
                $this->createDateTime('Y-m-d H:i:s T', '2019-01-01 01:00:00 UTC'),
                $this->createDateTime('Y-m-d H:i:s T', '2019-01-02 01:00:00 UTC')),
        ]);

        $machine->setDateTime($this->createDateTime('Y-m-d H:i:s T', '2019-01-01 00:00:00 UTC'));
        $this->assertNull($machine->currentState());
        $this->assertTrue($machine->isBefore('finite'));
        $this->assertFalse($machine->isAfter('finite'));

        $machine->setDateTime($this->createDateTime('Y-m-d H:i:s T', '2019-01-02 02:00:00 UTC'));
        $this->assertNull($machine->currentState());
        $this->assertFalse($machine->isBefore('finite'));
        $this->assertTrue($machine->isAfter('finite'));
    }

    public function testGap()
    {
        $machine = new TimeMachine([
            new TimeMachineState('state-1',
                $this->createDateTime('Y-m-d H:i:s T', '2019-01-01 01:00:00 UTC'),
                $this->createDateTime('Y-m-d H:i:s T', '2019-01-01 02:00:00 UTC')),
            new TimeMachineState('state-2',
                $this->createDateTime('Y-m-d H:i:s T', '2019-01-01 03:00:00 UTC'),
                $this->createDateTime('Y-m-d H:i:s T', '2019-01-01 04:00:00 UTC')),
        ]);

        $machine->setDateTime($this->createDateTime('Y-m-d H:i:s T', '2019-01-01 02:30:00 UTC'));
        $this->assertNull($machine->currentState());
        $this->assertTrue($machine->isAfter('state-1'));
        $this->assertTrue($machine->isBefore('state-2'));
        $this->assertTrue($machine->isInInterval('state-1', 'state-2'));
        $this->assertFalse($machine->isState('state-1'));
        $this->assertFalse($machine->isState('state-1'));
        $this->assertFalse($machine->isBefore('state-1'));
        $this->assertFalse($machine->isAfter('state-2'));
    }

    private function createDateTime(string $format, string $time) : \DateTime
    {
        $dateTime = \DateTime::createFromFormat($format, $time);
        if ($dateTime === false) {
            throw new \LogicException("Failed to create DateTime object");
        }

        return $dateTime;
    }
}
