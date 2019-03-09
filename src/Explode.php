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

final class Explode implements Rollable
{
    const EQUALS = '=';
    const GREATER_THAN = '>';
    const LESSER_THAN = '<';

    /**
     * The Cup object to decorate.
     *
     * @var Cup
     */
    private $rollable;

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
     * new instance.
     *
     * @throws Exception if the comparator is not recognized
     * @throws Exception if the Cup is not valid
     */
    public function __construct(Cup $rollable, string $compare, int $threshold = null)
    {
        if (!in_array($compare, [self::EQUALS, self::GREATER_THAN, self::LESSER_THAN], true)) {
            throw new Exception(sprintf('The submitted compared string `%s` is invalid or unsuported', $compare));
        }
        $this->compare = $compare;
        $this->threshold = $threshold;
        if (!$this->isValidCollection($rollable)) {
            throw new Exception(sprintf('This expression %s will generate a infinite loop', (string) $this));
        }
        $this->rollable = $rollable;
    }

    /**
     * Tells whether the Rollable collection can be used.
     */
    private function isValidCollection(Cup $collection): bool
    {
        $state = false;
        foreach ($collection as $rollable) {
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
    public function toString()
    {
        $str = (string) $this->rollable;
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
        return $this->rollable->getMinimum();
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        return PHP_INT_MAX;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $sum = 0;
        foreach ($this->rollable as $innerRoll) {
            $sum = $this->calculate($sum, $innerRoll);
        }

        return $sum;
    }

    /**
     * Add the result of the Rollable::roll method to the submitted sum.
     */
    private function calculate(int $sum, Rollable $rollable): int
    {
        $threshold = $this->threshold ?? $rollable->getMaximum();
        do {
            $res = $rollable->roll();
            $sum += $res;
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
}
