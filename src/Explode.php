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

use JsonSerializable;
use function array_map;
use function array_sum;
use function count;
use function implode;
use function in_array;
use function iterator_to_array;
use const PHP_INT_MAX;

final class Explode implements JsonSerializable, Modifier, EnablesDeepTracing
{
    private const EQ = '=';
    private const GT = '>';
    private const LT = '<';
    private const ALGORITHM_LIST = [
        self::LT,
        self::GT,
        self::EQ,
    ];

    private Pool $pool;

    private ?int $threshold;

    private string $compare;

    private Tracer $tracer;

    private bool $isRollableWrapped = false;

    /**
     * @throws \Bakame\DiceRoller\SyntaxError if the comparison is unknown or not supported
     * @throws \Bakame\DiceRoller\SyntaxError if the Cup triggers infinite loop
     */
    private function __construct(Rollable $pool, string $compare, int $threshold = null, Tracer $tracer = null)
    {
        if (!in_array($compare, self::ALGORITHM_LIST, true)) {
            throw SyntaxError::dueToInvalidOperator($compare);
        }

        $this->compare = $compare;
        $this->threshold = $threshold;

        if (!$pool instanceof Pool) {
            $this->isRollableWrapped = true;
            $pool = new Cup($pool);
        }

        if (!$this->isValidPool($pool)) {
            throw SyntaxError::dueToInfiniteLoop($pool);
        }

        $this->pool = $pool;
        $this->setTracer($tracer ?? new NullTracer());
    }

    public static function equals(Rollable $rollable, ?int $threshold, Tracer $tracer = null): self
    {
        return new self($rollable, self::EQ, $threshold, $tracer);
    }

    public static function greaterThan(Rollable $rollable, ?int $threshold, Tracer $tracer = null): self
    {
        return new self($rollable, self::GT, $threshold, $tracer);
    }

    public static function lesserThan(Rollable $rollable, ?int $threshold, Tracer $tracer = null): self
    {
        return new self($rollable, self::LT, $threshold, $tracer);
    }

    public static function fromAlgorithm(Rollable $rollable, string $compare, ?int $threshold, Tracer $tracer = null): self
    {
        return new self($rollable, $compare, $threshold, $tracer);
    }

    /**
     * Tells whether the Pool can be used.
     */
    private function isValidPool(Pool $pool): bool
    {
        foreach ($pool as $rollable) {
            if (!$this->isValidRollable($rollable)) {
                return false;
            }
        }

        return 0 !== count($pool);
    }

    /**
     * Tells whether a Rollable object is in valid state.
     */
    private function isValidRollable(Rollable $rollable): bool
    {
        $min = $rollable->minimum();
        $max = $rollable->maximum();
        $threshold = $this->threshold ?? $max;

        if (self::GT === $this->compare) {
            return $threshold > $min;
        }

        if (self::LT === $this->compare) {
            $threshold = $this->threshold ?? $min;
            return $threshold < $max;
        }

        return $min !== $max || $threshold !== $max;
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
        if (!$this->isRollableWrapped) {
            return $this->pool;
        }

        return iterator_to_array($this->pool, false)[0];
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

        return $str.'!'.$this->getAnnotationSuffix();
    }

    /**
     * Return the modifier dice annotation.
     */
    private function getAnnotationSuffix(): string
    {
        if (self::EQ === $this->compare && in_array($this->threshold, [null, 1], true)) {
            return '';
        }

        return $this->compare.($this->threshold ?? '');
    }

    public function minimum(): int
    {
        $minimum = $this->pool->minimum();
        $roll = new Toss($minimum, (string) $minimum, TossContext::fromRolling($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    public function maximum(): int
    {
        $roll = new Toss(PHP_INT_MAX, (string) PHP_INT_MAX, TossContext::fromRolling($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    public function roll(): Roll
    {
        $values = [];
        foreach ($this->pool as $rollable) {
            $values = $this->calculate($values, $rollable);
        }

        $roll = new Toss(
            (int) array_sum($values),
            implode(' + ', array_map(fn (int $value): string => (0 > $value) ? '('.$value.')' : ''.$value, $values)),
            TossContext::fromRolling($this, __METHOD__, ['totalRollsCount' => count($values)])
        );

        $this->tracer->append($roll);

        return $roll;
    }

    /**
     * Add the result of the Rollable::roll method to the submitted sum.
     */
    private function calculate(array $sum, Rollable $rollable): array
    {
        $threshold = $this->threshold ?? $rollable->maximum();
        do {
            $value = $rollable->roll()->value();
            $sum[] = $value;
        } while ($this->isValid($value, $threshold));

        return $sum;
    }

    /**
     * Returns whether we should call the rollable again.
     */
    private function isValid(int $result, int $threshold): bool
    {
        return  match (true) {
            self::EQ == $this->compare => $result === $threshold,
            self::GT === $this->compare => $result > $threshold,
            default => $result < $threshold,
        };
    }
}
