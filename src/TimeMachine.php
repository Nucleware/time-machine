<?php declare(strict_types=1);

namespace Nucleware\TimeMachine;

class TimeMachine
{
    /** @var TimeMachineState[] */
    private $states;

    /** @var TimeMachineState|null */
    private $currentState;

    /** @var \DateTimeInterface */
    private $dateTime;

    /**
     * @param TimeMachineState[] $states
     */
    public function __construct(array $states, ?\DateTimeInterface $dateTime = null)
    {
        if (count($states) === 0) {
            throw new \LogicException("No states provided");
        }

        $this->states = [];
        foreach ($states as $state) {
            $this->states[$state->getName()] = $state;
        }

        $this->setDateTime($dateTime);
    }

    public function setDateTime(?\DateTimeInterface $dateTime = null) : void
    {
        if ($dateTime === null) {
            $dateTime = new \DateTime();
        }

        $this->dateTime = $dateTime;
        $this->updateCurrentState();
    }

    private function updateCurrentState() : void
    {
        foreach ($this->states as $state) {
            if ($state->isActive($this->dateTime)) {
                $this->currentState = $state;
                return;
            };
        }

        $this->currentState = null;
    }

    public function currentState() : ?TimeMachineState
    {
        return $this->currentState;
    }

    public function isBefore(string $stateName) : bool
    {
        $state = $this->getState($stateName);
        $stateBegin = $state->getBegin();
        return $stateBegin !== null && $this->dateTime->getTimestamp() < $stateBegin->getTimestamp();
    }

    public function isState(string $stateName) : bool
    {
        $state = $this->getState($stateName);
        return $this->currentState === $state;
    }

    public function isAfter(string $stateName) : bool
    {
        $state = $this->getState($stateName);
        $stateEnd = $state->getEnd();
        return $stateEnd !== null && $this->dateTime->getTimestamp() >= $stateEnd->getTimestamp();
    }

    public function isInInterval(string $stateNameBegin, string $stateNameEnd) : bool
    {
        $stateBegin = $this->getState($stateNameBegin);
        $stateEnd = $this->getState($stateNameEnd);
        $begin = $stateBegin->getBegin();
        $end = $stateEnd->getEnd();
        return ($begin === null || $begin->getTimestamp() <= $this->dateTime->getTimestamp())
            && ($end === null || $this->dateTime->getTimestamp() < $end->getTimestamp())
        ;
    }

    protected function getState(string $stateName) : TimeMachineState
    {
        if (!array_key_exists($stateName, $this->states)) {
            throw new \LogicException("Unknown state");
        }

        return $this->states[$stateName];
    }
}
