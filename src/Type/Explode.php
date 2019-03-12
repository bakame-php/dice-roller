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

namespace Bakame\DiceRoller\Type;

use Bakame\DiceRoller\Exception\IllegalValue;
use Bakame\DiceRoller\Exception\UnknownAlgorithm;
use Bakame\DiceRoller\Profiler\Profiler;
use function array_map;
use function array_sum;
use function implode;
use function in_array;
use function sprintf;
use function strpos;
use const PHP_INT_MAX;

final class Explode implements Rollable
{
    const EQUALS = '=';
    const GREATER_THAN = '>';
    const LESSER_THAN = '<';

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
     * @var \Bakame\DiceRoller\Profiler\Profiler|null
     */
    private $profiler;

    /**
     * new instance.
     *
     *
     * @param  ?Profiler        $profiler
     * @throws UnknownAlgorithm if the comparator is not recognized
     * @throws IllegalValue     if the Cup triggers infinite loop
     */
    public function __construct(Pool $pool, string $compare, int $threshold = null, ?Profiler $profiler = null)
    {
        if (!in_array($compare, [self::EQUALS, self::GREATER_THAN, self::LESSER_THAN], true)) {
            throw new UnknownAlgorithm(sprintf('The submitted compared string `%s` is invalid or unsuported', $compare));
        }
        $this->compare = $compare;
        $this->threshold = $threshold;
        if (!$this->isValidPool($pool)) {
            throw new IllegalValue(sprintf('This collection %s will generate a infinite loop', $pool->toString()));
        }
        $this->pool = $pool;
        $this->profiler = $profiler;
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
        $min = $rollable->getMinimum();
        $max = $rollable->getMaximum();
        $threshold = $this->threshold ?? $max;

        if (self::GREATER_THAN === $this->compare) {
            return $threshold > $min;
        }

        if (self::LESSER_THAN === $this->compare) {
            $threshold = $this->threshold ?? $min;
            return $threshold < $max;
        }

        return $min !== $max || $threshold !== $max;
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
        $str = $this->pool->toString();
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
        if (self::EQUALS === $this->compare && in_array($this->threshold, [null, 1], true)) {
            return '';
        }

        return $this->compare.$this->threshold;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $retval = $this->pool->getMinimum();
        if (null === $this->profiler) {
            return $retval;
        }

        $this->profiler->addOperation(__METHOD__, $this, (string) $retval, $retval);

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        if (null === $this->profiler) {
            return PHP_INT_MAX;
        }

        $this->profiler->addOperation(__METHOD__, $this, (string) PHP_INT_MAX, PHP_INT_MAX);

        return PHP_INT_MAX;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $sum = [];
        foreach ($this->pool as $innerRoll) {
            $sum = $this->calculate($sum, $innerRoll);
        }

        $retval = (int) array_sum($sum);
        if (null === $this->profiler) {
            return $retval;
        }

        $this->profiler->addOperation(__METHOD__, $this, $this->setTrace($sum), $retval);

        return $retval;
    }

    /**
     * Add the result of the Rollable::roll method to the submitted sum.
     */
    private function calculate(array $sum, Rollable $rollable): array
    {
        $threshold = $this->threshold ?? $rollable->getMaximum();
        do {
            $res = $rollable->roll();
            $sum[] = $res;
        } while ($this->isValid($res, $threshold));

        return $sum;
    }

    /**
     * Returns whether we should call the rollable again.
     */
    private function isValid(int $result, int $threshold): bool
    {
        if (self::EQUALS == $this->compare) {
            return $result === $threshold;
        }

        if (self::GREATER_THAN === $this->compare) {
            return $result > $threshold;
        }

        return $result < $threshold;
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
