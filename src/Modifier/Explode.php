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
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\Traceable;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Exception\IllegalValue;
use Bakame\DiceRoller\Exception\UnknownAlgorithm;
use Bakame\DiceRoller\Profiler\ProfilerAware;
use function array_map;
use function array_sum;
use function implode;
use function in_array;
use function sprintf;
use function strpos;
use const PHP_INT_MAX;

final class Explode implements Modifier, Traceable
{
    use ProfilerAware;

    const EQ = '=';
    const GT = '>';
    const LT = '<';

    /**
     * The RollableCollection to decorate.
     *
     * @var \Bakame\DiceRoller\Contract\Pool
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
     * @var string
     */
    private $trace = '';

    /**
     * new instance.
     *
     *
     * @throws UnknownAlgorithm if the comparator is not recognized
     * @throws IllegalValue     if the Cup triggers infinite loop
     */
    public function __construct(Rollable $pool, string $compare, int $threshold = null)
    {
        if (!$pool instanceof Pool) {
            $pool = new Cup($pool);
        }

        if (!in_array($compare, [self::EQ, self::GT, self::LT], true)) {
            throw new UnknownAlgorithm(sprintf('The submitted compared string `%s` is invalid or unsuported', $compare));
        }
        $this->compare = $compare;
        $this->threshold = $threshold;
        if (!$this->isValidPool($pool)) {
            throw new IllegalValue(sprintf('This collection %s will generate a infinite loop', $pool->toString()));
        }
        $this->pool = $pool;
        $this->setProfiler();
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
    public function getTrace(): string
    {
        return $this->trace;
    }

    /**
     * {@inheritdoc}
     */
    public function getInnerRollable(): Rollable
    {
        return $this->pool;
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
        if (self::EQ === $this->compare && in_array($this->threshold, [null, 1], true)) {
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

        $this->trace = (string) $retval;
        $this->profiler->addTrace($this, __METHOD__, $retval, $this->trace);

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $this->trace = (string) PHP_INT_MAX;
        $this->profiler->addTrace($this, __METHOD__, PHP_INT_MAX, $this->trace);

        return PHP_INT_MAX;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $innerRetval = [];
        foreach ($this->pool as $innerRoll) {
            $innerRetval = $this->calculate($innerRetval, $innerRoll);
        }

        $retval = (int) array_sum($innerRetval);
        $this->setTrace($innerRetval);

        $this->profiler->addTrace($this, __METHOD__, $retval, $this->trace);

        return $retval;
    }

    /**
     * Add the result of the Rollable::roll method to the submitted sum.
     */
    private function calculate(array $sum, Rollable $rollable): array
    {
        $threshold = $this->threshold ?? $rollable->getMaximum();
        do {
            $innerRetval = $rollable->roll();
            $sum[] = $innerRetval;
        } while ($this->isValid($innerRetval, $threshold));

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

            return ''.$value;
        };

        $arr = array_map($mapper, $traces);

        $this->trace = implode(' + ', $arr);
    }
}