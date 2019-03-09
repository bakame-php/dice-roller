<?php

/**
 * This file is part of the League.csv library
 *
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/bakame-php/dice-roller/
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
     * Create a new Cup containing only on type of Rollable object.
     *
     * @throws Exception if the quantity is lesser than 0
     */
    public static function createFromRollable(int $quantity, Rollable $rollable): self
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

        return new self(...$items);
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
        $reduce = static function (int $result, Rollable $rollable): int {
            return $result + $rollable->roll();
        };

        return $this->calculate($reduce);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $reduce = static function (int $result, Rollable $rollable): int {
            return $result + $rollable->getMinimum();
        };

        return $this->calculate($reduce);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $reduce = static function (int $result, Rollable $rollable): int {
            return $result + $rollable->getMaximum();
        };

        return $this->calculate($reduce);
    }

    private function calculate(callable $calculate): int
    {
        return array_reduce($this->items, $calculate, 0);
    }
}
