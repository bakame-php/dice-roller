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
use Bakame\DiceRoller\Tracer\NullTracer;
use Iterator;
use function array_count_values;
use function array_filter;
use function array_map;
use function array_merge;
use function array_sum;
use function array_walk;
use function count;
use function implode;
use function sprintf;

final class Cup implements Pool, Traceable
{
    /**
     * @var Rollable[]
     */
    private $items = [];

    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * Create a new Cup containing only on type of Rollable object.
     *
     * @param ?Tracer $tracer
     *
     * @throws IllegalValue if the quantity is lesser than 0
     */
    public static function createFromRollable(int $quantity, Rollable $rollable, ?Tracer $tracer = null): self
    {
        if ($quantity < 1) {
            throw new IllegalValue(sprintf('The quantity of dice `%s` is not valid', $quantity));
        }

        $tracer = $tracer ?? new NullTracer();
        if (!self::isValid($rollable)) {
            return new self($tracer);
        }

        $items = [$rollable];
        for ($i = 0; $i < $quantity - 1; ++$i) {
            $items[] = clone $rollable;
        }

        return (new self($tracer))->withAddedRollable(...$items);
    }

    /**
     * Cup constructor.
     *
     * @param ?Tracer $tracer
     */
    public function __construct(?Tracer $tracer = null)
    {
        $this->tracer = $tracer ?? new NullTracer();
    }

    /**
     * Tell whether the submitted Rollable can be added to the collection.
     */
    private static function isValid(Rollable $rollable): bool
    {
        return !$rollable instanceof Pool || !$rollable->isEmpty();
    }

    /**
     * Return an instance with the added Rollable object.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified Rollable object.
     * @param Rollable ...$items
     */
    public function withAddedRollable(Rollable ...$items): self
    {
        $items = array_filter(array_merge($this->items, $items), [$this, 'isValid']);
        if ($items === $this->items) {
            return $this;
        }

        $cup = new self($this->tracer);
        $cup->items = $items;

        return $cup;
    }

    /**
     * {@inheritdoc}
     */
    public function getTracer(): Tracer
    {
        return $this->tracer;
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

        $this->tracer->addTrace($this, __METHOD__, $retval, $this->setTrace($sum));

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

        $this->tracer->addTrace($this, __METHOD__, $retval, $this->setTrace($sum));

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

        $this->tracer->addTrace($this, __METHOD__, $retval, $this->setTrace($sum));

        return $retval;
    }

    /**
     * Format the trace as string.
     */
    private function setTrace(array $traces): string
    {
        $mapper = static function (int $value): string {
            $str = ''.$value;
            if (0 > $value) {
                return '('.$str.')';
            }

            return $str;
        };

        $arr = array_map($mapper, $traces);

        return implode(' + ', $arr);
    }
}
