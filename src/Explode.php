<?php
/**
* This file is part of the League.csv library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/bakame-php/dice-roller/
* @version 1.0.0
* @package bakame-php/dice-roller
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

    const OPERATOR = [
        self::EQUALS => 1,
        self::GREATER_THAN => 1,
        self::LESSER_THAN => 1,
    ];

    /**
     * The Cup object to decorate
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
    private $operator;

    /**
     * @var string
     */
    private $trace;

    /**
     * @var array
     */
    private $stack;

    /**
     * new instance
     *
     * @param Rollable $rollable
     * @param string   $operator
     * @param int|null $threshold
     */
    public function __construct(Rollable $rollable, string $operator, int $threshold = null)
    {
        if (!$rollable instanceof Cup) {
            $rollable = new Cup($rollable);
        }

        $this->validate($rollable, $operator, $threshold);

        $this->operator = $operator;
        $this->threshold = $threshold;
        $this->rollable = $rollable;
        $this->trace = '';
        $this->stack = [];
    }

    /**
     * Validate the modifier properties
     *
     * @param Cup      $collection
     * @param string   $operator
     * @param int|null $threshold
     *
     * @throws Exception if the comparator is not recognized
     * @throws Exception if the Cup is not valid
     */
    private function validate(Cup $collection, string $operator, $threshold)
    {
        if (!isset(self::OPERATOR[$operator])) {
            throw new Exception(sprintf('The submitted compared string `%s` is invalid or unsuported', $operator));
        }

        $state = false;
        foreach ($collection as $rollable) {
            if (!($state = $this->isValidRollable($rollable, $operator, $threshold))) {
                break;
            }
        }

        if (!$state) {
            throw new Exception(sprintf('This expression %s will generate a infinite loop', (string) $this));
        }
    }

    /**
     * Tells whether a Rollable object is in valid state
     *
     * @param Rollable $rollable
     * @param string   $operator
     * @param mixed    $threshold
     *
     * @return bool
     */
    private function isValidRollable(Rollable $rollable, string $operator, $threshold): bool
    {
        $min = $rollable->getMinimum();
        $max = $rollable->getMaximum();
        $threshold = $threshold ?? $max;

        if (self::GREATER_THAN === $operator) {
            return $threshold > $min;
        }

        if (self::LESSER_THAN === $operator) {
            $threshold = $threshold ?? $min;
            return $threshold < $max;
        }

        return $min !== $max || $threshold !== $max;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->trace = '';
        $this->stack = [];
        $str = (string) $this->rollable;
        if (false !== strpos($str, '+')) {
            $str = '('.$str.')';
        }

        return $str.'!'.$this->getAnnotationSuffix();
    }

    /**
     * Return the modifier dice annotation.
     *
     * @return string
     */
    private function getAnnotationSuffix()
    {
        if (self::EQUALS === $this->operator && in_array($this->threshold, [null, 1], true)) {
            return '';
        }

        return $this->operator.$this->threshold;
    }

    /**
     * Returns the inner rollable object.
     *
     * @return Rollable
     */
    public function getRollable(): Rollable
    {
        return $this->rollable;
    }

    /**
     * Returns the operator used by the modifier.
     *
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Returns the threshold used by the modifier
     *
     * @return int|null
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * {@inheritdoc}
     */
    public function getTrace(): array
    {
        return $this->stack;
    }

    /**
     * {@inheritdoc}
     */
    public function getTraceAsString(): string
    {
        return $this->trace;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $this->trace = '';
        $this->stack = [];

        return $this->rollable->getMinimum();
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $this->trace = '';
        $this->stack = [];

        return PHP_INT_MAX;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $sum = 0;
        $this->trace = '';
        $this->stack = ['roll' => '', 'inner_stack' => []];
        foreach ($this->rollable as $innerRoll) {
            $sum = $this->calculate($sum, $innerRoll);
        }

        $this->stack['roll'] = (string) $sum;

        return $sum;
    }

    /**
     * Add the result of the Rollable::roll method to the submitted sum.
     *
     * @param int      $pSum
     * @param Rollable $rollable
     *
     * @return int
     */
    private function calculate(int $pSum, Rollable $rollable): int
    {
        $stack = ['roll' => '', 'inner_stack' => []];
        $trace = [];
        $threshold = $this->threshold ?? $rollable->getMaximum();
        $sum = $pSum;
        do {
            $res = $rollable->roll();
            $sum += $res;
            $str = $rollable->getTraceAsString();
            if (false !== strpos($str, '+')) {
                $str = '('.$str.')';
            }
            $trace[] = $str;
            $stack['inner_stack'][] = $rollable->getTrace();
        } while ($this->isValid($res, $threshold));

        $trace = implode(' + ', $trace);
        if ('' !== $this->trace) {
            $trace = ' + '.$trace;
        }
        $stack['roll'] = (string) ($sum - $pSum);
        $this->trace .= $trace;
        $this->stack['inner_stack'][] = $stack;

        return $sum;
    }

    /**
     * Returns whether we should call the rollable again.
     *
     * @param int $result
     * @param int $threshold
     *
     * @return bool
     */
    private function isValid(int $result, int $threshold): bool
    {
        if (self::EQUALS == $this->operator) {
            return $result === $threshold;
        }

        if (self::GREATER_THAN === $this->operator) {
            return $result > $threshold;
        }

        return $result < $threshold;
    }

    /**
     * Return an instance with the specified Rollable object.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified Rollable object.
     *
     * @param Rollable $rollable
     *
     * @return self
     */
    public function withRollable(Rollable $rollable): self
    {
        if ($rollable == $this->rollable) {
            return $this;
        }

        return new self($rollable, $this->operator, $this->threshold);
    }

    /**
     * Return an instance with the specified operator.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified operator.
     *
     * @param string $operator
     *
     * @return self
     */
    public function withOperator(string $operator): self
    {
        if ($operator === $this->operator) {
            return $this;
        }

        return new self($this->rollable, $operator, $this->threshold);
    }

    /**
     * Return an instance with the specified threshold.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified threshold.
     *
     * @param int|null $threshold
     *
     * @return self
     */
    public function withThreshold($threshold): self
    {
        if ($threshold === $this->threshold) {
            return $this;
        }

        return new self($this->rollable, $this->operator, $threshold);
    }
}
