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

use function array_map;
use function array_slice;
use function array_sum;
use function count;
use function implode;
use function in_array;
use function iterator_to_array;
use function rsort;
use function strtoupper;
use function uasort;

final class DropKeep implements \JsonSerializable, Modifier, EnablesDeepTracing
{
    private const DROP_HIGHEST = 'DH';
    private const DROP_LOWEST = 'DL';
    private const KEEP_HIGHEST = 'KH';
    private const KEEP_LOWEST = 'KL';

    private const ALGORITHM_LIST = [
        self::KEEP_HIGHEST,
        self::KEEP_LOWEST,
        self::DROP_HIGHEST,
        self::DROP_LOWEST,
    ];

    private Pool $pool;

    /**
     * The threshold number of Rollable object.
     */
    private int $threshold;

    private string $algorithm;

    private Tracer $tracer;

    private bool $isDecoratedRollable = false;

    private function __construct(Rollable $pool, string $algorithm, int $threshold, Tracer $tracer = null)
    {
        $algorithm = strtoupper($algorithm);
        if (!in_array($algorithm, self::ALGORITHM_LIST, true)) {
            throw SyntaxError::dueToInvalidOperator($algorithm);
        }

        if (!$pool instanceof Pool) {
            $this->isDecoratedRollable = true;
            $pool = new Cup($pool);
        }

        if (count($pool) < $threshold) {
            throw SyntaxError::dueToTooManyInstancesToRoll(count($pool), $threshold);
        }

        $this->pool = $pool;
        $this->algorithm = $algorithm;
        $this->threshold = $threshold;
        $this->setTracer($tracer ?? new NullTracer());
    }

    public static function dropLowest(Rollable $pool, int $threshold, Tracer $tracer = null): self
    {
        return new self($pool, self::DROP_LOWEST, $threshold, $tracer);
    }

    public static function dropHighest(Rollable $pool, int $threshold, Tracer $tracer = null): self
    {
        return new self($pool, self::DROP_HIGHEST, $threshold, $tracer);
    }

    public static function keepLowest(Rollable $pool, int $threshold, Tracer $tracer = null): self
    {
        return new self($pool, self::KEEP_LOWEST, $threshold, $tracer);
    }

    public static function keepHighest(Rollable $pool, int $threshold, Tracer $tracer = null): self
    {
        return new self($pool, self::KEEP_HIGHEST, $threshold, $tracer);
    }

    public static function fromAlgorithm(Rollable $rollable, string $algorithm, int $threshold, Tracer $tracer = null): self
    {
        return new self($rollable, $algorithm, $threshold, $tracer);
    }

    public function getTracer(): Tracer
    {
        return $this->tracer;
    }

    public function setTracer(Tracer $tracer): void
    {
        $this->tracer = $tracer;
    }

    public function setTracerRecursively(Tracer $tracer): void
    {
        $this->setTracer($tracer);
        if ($this->pool instanceof EnablesDeepTracing) {
            $this->pool->setTracerRecursively($tracer);
        } elseif ($this->pool instanceof SupportsTracing) {
            $this->pool->setTracer($tracer);
        }
    }

    public function getInnerRollable(): Rollable
    {
        if (!$this->isDecoratedRollable) {
            return $this->pool;
        }

        /** @var array<Rollable> $arr */
        $arr = iterator_to_array($this->pool);

        return $arr[0];
    }

    public function jsonSerialize(): string
    {
        return $this->notation();
    }

    public function notation(): string
    {
        $str = $this->pool->notation();
        if (str_contains($str, '+')) {
            $str = '('.$str.')';
        }

        return $str.$this->algorithm.$this->threshold;
    }

    public function roll(): Roll
    {
        $values = [];
        foreach ($this->pool as $rollable) {
            $values[] = $rollable->roll()->value();
        }

        return $this->decorate($values, __METHOD__);
    }

    public function minimum(): int
    {
        $values = [];
        foreach ($this->pool as $rollable) {
            $values[] = $rollable->minimum();
        }

        return $this->decorate($values, __METHOD__)->value();
    }

    public function maximum(): int
    {
        $values = [];
        foreach ($this->pool as $rollable) {
            $values[] = $rollable->maximum();
        }

        return $this->decorate($values, __METHOD__)->value();
    }

    /**
     * Decorates the operation returned value.
     *
     * @param array<int> $values
     */
    private function decorate(array $values, string $method): Roll
    {
        $result = $this->slice($values);
        $operation = implode(' + ', array_map(fn (int $value): string => (0 > $value) ? '('.$value.')' : ''.$value, $result));
        $roll = new Toss((int) array_sum($result), $operation, TossContext::fromRolling($this, $method));

        $this->tracer->append($roll);

        return $roll;
    }

    /** @param array<int> $values  */
    private function slice(array $values): array
    {
        uasort($values, fn (int $data1, int $data2): int => $data1 <=> $data2);

        if (self::DROP_HIGHEST === $this->algorithm) {
            return array_slice($values, 0, $this->threshold);
        }

        if (self::DROP_LOWEST === $this->algorithm) {
            return array_slice($values, $this->threshold);
        }

        rsort($values);

        if (self::KEEP_HIGHEST === $this->algorithm) {
            return array_slice($values, 0, $this->threshold);
        }

        return array_slice($values, $this->threshold);
    }
}
