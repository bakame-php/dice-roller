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

namespace Bakame\DiceRoller\Modifier;

use Bakame\DiceRoller\Contract\Modifier;
use Bakame\DiceRoller\Contract\Pool;
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\SupportsTracing;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Exception\SyntaxError;
use Bakame\DiceRoller\Toss;
use Bakame\DiceRoller\TossContext;
use Bakame\DiceRoller\Tracer\NullTracer;
use function array_map;
use function array_sum;
use function count;
use function implode;
use function in_array;
use function iterator_to_array;
use function strpos;
use const PHP_INT_MAX;

final class Explode implements Modifier, SupportsTracing
{
    const EQ = '=';
    const GT = '>';
    const LT = '<';

    private Pool $pool;

    private ?int $threshold;

    private string $compare;

    private Tracer $tracer;

    private bool $is_rollable_wrapped = false;

    /**
     * @throws SyntaxError if the Cup triggers infinite loop
     */
    private function __construct(Rollable $pool, string $compare, int $threshold = null, Tracer $tracer = null)
    {
        $this->compare = $compare;
        $this->threshold = $threshold;

        if (!$pool instanceof Pool) {
            $this->is_rollable_wrapped = true;
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

    /**
     * Tells whether the Pool can be used.
     */
    private function isValidPool(Pool $pool): bool
    {
        $state = false;
        foreach ($pool as $rollable) {
            $state = $this->isValidRollable($rollable);
            if (!$state) {
                return $state;
            }
        }

        return $state;
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

    public function setTracer(Tracer $tracer): void
    {
        $this->tracer = $tracer;
    }

    public function getInnerRollable(): Rollable
    {
        if (!$this->is_rollable_wrapped) {
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
        if (false !== strpos($str, '+')) {
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
        $roll = new Toss($minimum, (string) $minimum, new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    public function maximum(): int
    {
        $roll = new Toss(PHP_INT_MAX, (string) PHP_INT_MAX, new TossContext($this, __METHOD__));

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
            implode(' + ', array_map(fn ($value) => (0 > $value) ? '('.$value.')' : $value, $values)),
            new TossContext($this, __METHOD__, ['totalRollsCount' => count($values)])
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
        if (self::EQ == $this->compare) {
            return $result === $threshold;
        }

        if (self::GT === $this->compare) {
            return $result > $threshold;
        }

        return $result < $threshold;
    }
}
