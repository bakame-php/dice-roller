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

namespace Bakame\DiceRoller\Tracer;

use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\TraceContext;
use Bakame\DiceRoller\Contract\Tracer;
use Countable;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use function sprintf;

final class MemoryTracer implements Countable, IteratorAggregate, JsonSerializable, Tracer
{
    /**
     * @var array<int, array{context:TraceContext, value:Roll}>
     */
    private $collection = [];

    public function addTrace(Roll $roll, TraceContext $context): void
    {
        $this->collection[] = ['context' => $context, 'value' => $roll];
    }

    public function count(): int
    {
        return count($this->collection);
    }

    public function isEmpty(): bool
    {
        return [] === $this->collection;
    }

    public function getIterator(): Iterator
    {
        foreach ($this->collection as $trace) {
            yield $trace;
        }
    }

    public function clear(): void
    {
        $this->collection = [];
    }

    public function jsonSerialize(): array
    {
        $mapper = static function (array  $trace): array {
            return $trace['context']->asArray() + $trace['value']->asArray();
        };

        return array_map($mapper, $this->collection);
    }

    /**
     * Returns the trace specified at a given offset.
     *
     * @throws \OutOfBoundsException If the offset is illegal for the current instance
     */
    public function get(int $offset): array
    {
        $index = $this->filterOffset($offset);
        if (null !== $index) {
            return $this->collection[$index];
        }

        throw new \OutOfBoundsException(sprintf('%s is an invalid offset in the current instance', $offset));
    }

    /**
     * Filter and format the Sequence offset.
     *
     * This methods allows the support of negative offset
     *
     * if no offset is found null is returned otherwise the return type is int
     */
    private function filterOffset(int $offset): ?int
    {
        if ([] === $this->collection) {
            return null;
        }

        $max = count($this->collection);
        if (0 > $max + $offset) {
            return null;
        }

        if (0 > $max - $offset - 1) {
            return null;
        }

        if (0 > $offset) {
            return $max + $offset;
        }

        return $offset;
    }
}
