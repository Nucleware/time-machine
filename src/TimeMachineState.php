<?php declare(strict_types=1);

namespace Nucleware\TimeMachine;

class TimeMachineState
{
    /** @var string */
    private $name;
    /** @var \DateTimeInterface|null */
    private $begin;
    /** @var \DateTimeInterface|null */
    private $end;

    public function __construct(string $name, ?\DateTimeInterface $begin, ?\DateTimeInterface $end)
    {
        if ($begin !== null && $end !== null && $begin->diff($end)->invert === 1) {
            throw new \LogicException("The state must begin before it ends.");
        }

        $this->name = $name;
        $this->begin = $begin;
        $this->end = $end;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getBegin() : ?\DateTimeInterface
    {
        return $this->begin;
    }

    public function getEnd() : ?\DateTimeInterface
    {
        return $this->end;
    }

    public function isActive(?\DateTimeInterface $dateTime = null) : bool
    {
        if ($dateTime === null) {
            $dateTime = new \DateTime();
        }

        $begun = $this->begin === null || $this->begin->getTimestamp() <= $dateTime->getTimestamp();
        $ongoing = $this->end === null || $dateTime->getTimestamp() < $this->end->getTimestamp();

        return $begun && $ongoing;
    }
}
