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

use Bakame\DiceRoller\Contract\Pool;
use Bakame\DiceRoller\Contract\Profiler;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\Traceable;
use Bakame\DiceRoller\Exception\IllegalValue;
use Bakame\DiceRoller\Profiler\ProfilerAware;
use Iterator;
use function array_count_values;
use function array_filter;
use function array_map;
use function array_sum;
use function array_walk;
use function count;
use function implode;
use function sprintf;

final class Cup implements Pool, Traceable
{
    use ProfilerAware;

    /**
     * @var Rollable[]
     */
    private $items = [];

    /**
     * @var string
     */
    private $trace = '';

    /**
     * Cup constructor.
     *
     * @param Rollable ...$items
     */
    public function __construct(Rollable ...$items)
    {
        $this->items = array_filter($items, [$this, 'isValid']);
        $this->setProfiler();
    }

    /**
     * Tell whether the submitted Rollable can be added to the collection.
     */
    private static function isValid(Rollable $rollable): bool
    {
        return !$rollable instanceof Pool || !$rollable->isEmpty();
    }

    /**
     * Create a new Cup containing only on type of Rollable object.
     *
     * @param ?Profiler $profiler
     *
     * @throws IllegalValue
     */
    public static function createFromRollable(Rollable $rollable, int $quantity = 1, ?Profiler $profiler = null): self
    {
        if ($quantity < 1) {
            throw new IllegalValue(sprintf('The quantity of dice `%s` is not valid', $quantity));
        }

        if (!self::isValid($rollable)) {
            $new = new self();
            $new->setProfiler($profiler);

            return $new;
        }

        $items = [$rollable];
        for ($i = 0; $i < $quantity - 1; ++$i) {
            $items[] = clone $rollable;
        }

        $new = new self(...$items);
        $new->setProfiler($profiler);

        return $new;
    }

    /**
     * Return an instance with the added Rollable object.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified Rollable object.
     *
     * @param Rollable ...$items
     */
    public function withAddedRollable(Rollable ...$items): self
    {
        $items = array_filter($items, [$this, 'isValid']);
        if ([] === $items) {
            return $this;
        }

        $pool = new self();
        $pool->items = array_merge($this->items, $items);
        $pool->profiler = $this->profiler;

        return $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function getTrace(): string
    {
        return $this->trace;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty(): bool
    {
        return [] === $this->items;
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        if (0 == count($this->items)) {
            return '0';
        }

        $mapper = function (Rollable $rollable): string {
            return $rollable->toString();
        };

        $walker = function (&$value, $offset): void {
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
     * Returns an external Iterator which enables iteration
     * on each contained Rollable object.
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
        $sum = [];
        foreach ($this->items as $rollable) {
            $sum[] = $rollable->roll();
        }

        $retval = (int) array_sum($sum);
        $this->setTrace($sum);

        $this->profiler->addTrace($this, __METHOD__, $retval, $this->trace);

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $sum = [];
        foreach ($this->items as $rollable) {
            $sum[] = $rollable->getMinimum();
        }

        $retval = (int) array_sum($sum);
        $this->setTrace($sum);

        $this->profiler->addTrace($this, __METHOD__, $retval, $this->trace);

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $sum = [];
        foreach ($this->items as $rollable) {
            $sum[] = $rollable->getMaximum();
        }

        $retval = (int) array_sum($sum);
        $this->setTrace($sum);

        $this->profiler->addTrace($this, __METHOD__, $retval, $this->trace);

        return $retval;
    }

    /**
     * Format the trace as string.
     *
     * @param int[] $traces
     */
    private function setTrace(array $traces): void
    {
        $mapper = function (int $value): string {
            if (0 > $value) {
                return '('.$value.')';
            }

            return (string) $value;
        };

        $arr = array_map($mapper, $traces);

        $this->trace = implode(' + ', $arr);
    }
}
