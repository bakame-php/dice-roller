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
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\SupportsTracing;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Exception\SyntaxError;
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

final class Cup implements Pool, SupportsTracing
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
     * Cup constructor.
     *
     * @param Rollable ...$items
     */
    public function __construct(Rollable ...$items)
    {
        $this->items = array_filter($items, [$this, 'isValid']);
        $this->setTracer(new NullTracer());
    }

    /**
     * Tell whether the submitted Rollable can be added to the collection.
     */
    private function isValid(Rollable $rollable): bool
    {
        return !$rollable instanceof Pool || !$rollable->isEmpty();
    }
    /**
     * {@inheritDoc}
     */
    public function setTracer(Tracer $tracer): void
    {
        $this->tracer = $tracer;
    }

    /**
     * Create a new Cup containing only on type of Rollable object.
     *
     * @throws SyntaxError
     */
    public static function fromRollable(Rollable $rollable, int $quantity = 1): self
    {
        if ($quantity < 1) {
            throw new SyntaxError(sprintf('The quantity of dice `%s` is not valid. Should be > 0', $quantity));
        }
        --$quantity;

        $items = [$rollable];
        for ($i = 0; $i < $quantity; ++$i) {
            $items[] = clone $rollable;
        }

        return new self(...$items);
    }
    /**
     * {@inheritDoc}
     */
    public function isEmpty(): bool
    {
        return [] === $this->items;
    }

    /**
     * Returns the number of Rollable objects.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Returns an external Iterator to iterate on each contained Rollable object.
     */
    public function getIterator(): Iterator
    {
        foreach ($this->items as $rollable) {
            yield $rollable;
        }
    }

    public function jsonSerialize(): string
    {
        return $this->notation();
    }
    /**
     * {@inheritDoc}
     */
    public function notation(): string
    {
        if ([] === $this->items) {
            return '0';
        }

        $mapper = function (Rollable $rollable): string {
            return $rollable->notation();
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
     * {@inheritDoc}
     */
    public function roll(): Roll
    {
        $sum = [];
        foreach ($this->items as $rollable) {
            $sum[] = $rollable->roll()->value();
        }

        return $this->decorate($sum, __METHOD__);
    }
    /**
     * {@inheritDoc}
     */
    public function minimum(): int
    {
        $sum = [];
        foreach ($this->items as $rollable) {
            $sum[] = $rollable->minimum();
        }

        return $this->decorate($sum, __METHOD__)->value();
    }
    /**
     * {@inheritDoc}
     */
    public function maximum(): int
    {
        $sum = [];
        foreach ($this->items as $rollable) {
            $sum[] = $rollable->maximum();
        }

        return $this->decorate($sum, __METHOD__)->value();
    }

    /**
     * Decorates the operation returned value.
     */
    private function decorate(array $sum, string $method): Roll
    {
        $mapper = static function ($value) {
            if (0 > $value) {
                return '('.$value.')';
            }

            return $value;
        };

        $result = (int) array_sum($sum);
        $operation = implode(' + ', array_map($mapper, $sum));
        $roll = new Toss($result, $operation, new TossContext($this, $method));

        $this->tracer->append($roll);

        return $roll;
    }

    /**
     * Returns an instance with the added Rollable objects.
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
        $pool->tracer = $this->tracer;

        return $pool;
    }
}
