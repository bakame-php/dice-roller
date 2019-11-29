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

use Bakame\DiceRoller\Contract\AcceptsTracer;
use Bakame\DiceRoller\Contract\Modifier;
use Bakame\DiceRoller\Contract\Pool;
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Exception\SyntaxError;
use Bakame\DiceRoller\Exception\UnknownAlgorithm;
use Bakame\DiceRoller\Toss;
use Bakame\DiceRoller\TossContext;
use Bakame\DiceRoller\Tracer\NullTracer;
use function array_map;
use function array_sum;
use function count;
use function implode;
use function in_array;
use function iterator_to_array;
use function sprintf;
use function strpos;
use const PHP_INT_MAX;

final class Explode implements Modifier, AcceptsTracer
{
    const EQ = '=';
    const GT = '>';
    const LT = '<';

    /**
     * The RollableCollection to decorate.
     *
     * @var Pool
     */
    private $pool;

    /**
     * The threshold.
     *
     * @var int|null
     */
    private $threshold;

    /**
     * The comparison to use.
     *
     * @var string
     */
    private $compare;

    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var bool
     */
    private $is_rollable_wrapped = false;

    /**
     * new instance.
     *
     *
     * @throws UnknownAlgorithm if the comparator is not recognized
     * @throws SyntaxError      if the Cup triggers infinite loop
     */
    public function __construct(Rollable $pool, string $compare, int $threshold = null)
    {
        if (!$pool instanceof Pool) {
            $this->is_rollable_wrapped = true;
            $pool = new Cup($pool);
        }

        if (!in_array($compare, [self::EQ, self::GT, self::LT], true)) {
            throw new UnknownAlgorithm(sprintf('The submitted compared string `%s` is invalid or unsuported', $compare));
        }
        $this->compare = $compare;
        $this->threshold = $threshold;
        if (!$this->isValidPool($pool)) {
            throw new SyntaxError(sprintf('This collection %s will generate a infinite loop', $pool->notation()));
        }
        $this->pool = $pool;
        $this->setTracer(new NullTracer());
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

    /**
     * {@inheritdoc}
     */
    public function setTracer(Tracer $tracer): void
    {
        $this->tracer = $tracer;
    }

    /**
     * {@inheritdoc}
     */
    public function getInnerRollable(): Rollable
    {
        if (!$this->is_rollable_wrapped) {
            return $this->pool;
        }

        $arr = iterator_to_array($this->pool, false);

        return $arr[0];
    }

    /**
     * {@inheritdoc}
     */
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

        return $this->compare.$this->threshold;
    }

    /**
     * {@inheritdoc}
     */
    public function minimum(): int
    {
        $minimum = $this->pool->minimum();
        $roll = new Toss($minimum, (string) $minimum, new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    /**
     * {@inheritdoc}
     */
    public function maximum(): int
    {
        $roll = new Toss(PHP_INT_MAX, (string) PHP_INT_MAX, new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): Roll
    {
        $mapper = static function ($value) {
            if (0 > $value) {
                return '('.$value.')';
            }

            return $value;
        };

        $values = [];
        foreach ($this->pool as $rollable) {
            $values = $this->calculate($values, $rollable);
        }

        $result = (int) array_sum($values);
        $operation = implode(' + ', array_map($mapper, $values));
        $nbRolls = count($values);
        $roll = new Toss($result, $operation, new TossContext($this, __METHOD__, ['totalRollsCount' => $nbRolls]));

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
