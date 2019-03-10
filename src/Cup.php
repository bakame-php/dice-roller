<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bakame\DiceRoller;

use Bakame\DiceRoller\Exception\IllegalValue;
use Countable;
use Iterator;
use IteratorAggregate;

final class Cup implements Countable, IteratorAggregate, Rollable
{
    /**
     * @var Rollable[]
     */
    private $items = [];

    /**
     * @var Profiler|null
     */
    private $profiler;

    /**
     * Create a new Cup containing only on type of Rollable object.
     *
     * @param  ?Profiler $profiler
     * @throws Exception if the quantity is lesser than 0
     */
    public static function createFromRollable(int $quantity, Rollable $rollable, ?Profiler $profiler = null): self
    {
        if ($quantity < 1) {
            throw new IllegalValue(sprintf('The quantity of dice `%s` is not valid', $quantity));
        }

        if (!self::isValid($rollable)) {
            return new self();
        }

        $items = [$rollable];
        for ($i = 0; $i < $quantity - 1; ++$i) {
            $items[] = clone $rollable;
        }

        $cup = new self(...$items);
        $cup->setProfiler($profiler);

        return $cup;
    }

    /**
     * New instance.
     *
     * @param Rollable ...$items a list of Rollable objects
     */
    public function __construct(Rollable ...$items)
    {
        $this->items = array_filter($items, [$this, 'isValid']);
    }

    /**
     * Add or remove a profiler to record the object actions
     * using a logger.
     * @param ?Profiler $profiler
     */
    public function setProfiler(?Profiler $profiler): void
    {
        $this->profiler = $profiler;
    }

    /**
     * Tell whether the submitted Rollable can be added to the collection.
     */
    private static function isValid(Rollable $rollable): bool
    {
        return !$rollable instanceof self || count($rollable) > 0;
    }

    /**
     * Return an instance with the added Rollable object.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified Rollable object.
     */
    public function withRollable(Rollable $rollable): self
    {
        $items = array_filter(array_merge($this->items, [$rollable]), [$this, 'isValid']);
        if ($items === $this->items) {
            return $this;
        }

        $cup = new self();
        $cup->items = $items;
        $cup->profiler = $this->profiler;

        return $cup;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        if (0 == count($this->items)) {
            return '0';
        }

        $mapper = static function (Rollable $rollable): string {
            return $rollable->toString();
        };

        $walker = static function (&$value, $offset): void {
            $value = $value > 1 ? $value.$offset : $offset;
        };

        $parts = array_map($mapper, $this->items);
        $pool = array_count_values($parts);
        array_walk($pool, $walker);

        return implode('+', $pool);
    }

    /**
     * Returns the number of Rollable objects.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Iterator
    {
        foreach ($this->items as $rollable) {
            yield $rollable;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $mapper = static function (Rollable $rollable): int {
            return $rollable->roll();
        };

        $sum = array_map($mapper, $this->items);
        $retval = (int) array_sum($sum);

        if (null !== $this->profiler) {
            $this->profiler->profile(__METHOD__, $this, $this->setTrace($sum), $retval);
        }

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $mapper = static function (Rollable $rollable): int {
            return $rollable->getMinimum();
        };

        $sum = array_map($mapper, $this->items);
        $retval = (int) array_sum($sum);

        if (null !== $this->profiler) {
            $this->profiler->profile(__METHOD__, $this, $this->setTrace($sum), $retval);
        }

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $mapper = static function (Rollable $rollable): int {
            return $rollable->getMaximum();
        };

        $sum = array_map($mapper, $this->items);
        $retval = (int) array_sum($sum);

        if (null !== $this->profiler) {
            $this->profiler->profile(__METHOD__, $this, $this->setTrace($sum), $retval);
        }

        return $retval;
    }

    /**
     * Format the trace as string.
     */
    private function setTrace(array $traces): string
    {
        $mapper = static function (int $value): string {
            return '('.$value.')';
        };

        $arr = array_map($mapper, $traces);

        return implode(' + ', $arr);
    }
}
