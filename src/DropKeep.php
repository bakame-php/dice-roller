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

final class DropKeep implements Rollable
{
    const DROP_HIGHEST = 'dh';
    const DROP_LOWEST = 'dl';
    const KEEP_HIGHEST = 'kh';
    const KEEP_LOWEST = 'kl';

    const OPERATOR = [
        self::DROP_HIGHEST => 'dropHighest',
        self::DROP_LOWEST => 'dropLowest',
        self::KEEP_HIGHEST => 'keepHighest',
        self::KEEP_LOWEST => 'keepLowest',
    ];

    /**
     * The Cup object to decorate
     *
     * @var Cup
     */
    private $rollable;

    /**
     * The threshold number of rollable object
     *
     * @var int
     */
    private $threshold;

    /**
     * The method name associated with a given operator
     *
     * @var string
     */
    private $method;

    /**
     * new instance
     *
     * @param Rollable $rollable
     * @param string   $operator
     * @param int      $threshold
     *
     * @throws Exception if the algorithm is not recognized
     * @throws Exception if the Cup is not valid
     */
    public function __construct(Rollable $rollable, string $operator, int $threshold)
    {
        if (!$rollable instanceof Cup) {
            $rollable = new Cup($rollable);
        }

        $this->validate($rollable, $operator, $threshold);
        $this->rollable = $rollable;
        $this->threshold = $threshold;
        $this->method = self::OPERATOR[$operator];
    }

    /**
     * Validate the modifier properties
     *
     * @param Cup    $rollable
     * @param string $operator
     * @param int    $threshold
     *
     * @throws Exception if the algorithm is not recognized
     * @throws Exception if the Cup is not valid
     */
    private function validate(Cup $rollable, string $operator, int $threshold)
    {
        if (count($rollable) < $threshold) {
            throw new Exception(sprintf('The number of rollable objects `%s` MUST be lesser or equal to the threshold value `%s`', count($rollable), $threshold));
        }

        if (!isset(self::OPERATOR[$operator])) {
            throw new Exception(sprintf('Unknown or unsupported operator `%s`', $operator));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $str = (string) $this->rollable;
        if (false !== strpos($str, '+')) {
            $str = '('.$str.')';
        }

        return $str
            .strtoupper(array_search($this->method, self::OPERATOR))
            .$this->threshold;
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
     * Returns the modifier threshold.
     *
     * @return int
     */
    public function getThreshold(): int
    {
        return $this->threshold;
    }

    /**
     * Returns the modifier operator.
     *
     * @return string
     */
    public function getOperator(): string
    {
        return array_search($this->method, self::OPERATOR);
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): Roll
    {
        $children = [];
        foreach ($this->rollable as $rollable) {
            $children[] = $rollable->roll();
        }

        uasort($children, function (Roll $roll1, Roll $roll2) {
            return $roll1->getResult() <=> $roll2->getResult();
        });
        $children = array_values($children);

        $sum = 0;
        foreach ($children as $offset => $roll) {
            if ($this->isValid($offset)) {
                $sum += $roll->getResult();
                continue;
            }

            $roll->setStatus(Roll::DROP_ROLL);
        }

        return new Result($this, $sum, $children);
    }

    /**
     * Tell whether the current RollInterface object should be kept
     * for result calculation depending on its offset.
     *
     * @param int $offset
     *
     * @return bool
     */
    private function isValid(int $offset): bool
    {
        if ($offset > $this->threshold - 1 && in_array($this->method, ['keepHighest', 'dropLowest'])) {
            return true;
        }

        if ($offset <= $this->threshold - 1 && in_array($this->method, ['keepLowest', 'dropHighest'])) {
            return true;
        }

        return false;
    }

    /**
     * Computes the sum to be return.
     *
     * @param string $method One of the Rollable method
     *
     * @return int
     */
    private function calculate(string $method): int
    {
        $res = [];
        foreach ($this->rollable as $rollable) {
            $innerRoll = $rollable->$method();
            $res[] = $innerRoll;
        }

        $retained = $this->{$this->method}($res);

        return array_sum($retained);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        return $this->calculate('getMinimum');
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        return $this->calculate('getMaximum');
    }

    /**
     * Returns the drop highest value.
     *
     * @param int[] $sum
     *
     * @return int[]
     */
    private function dropHighest(array $sum): array
    {
        uasort($sum, [$this, 'drop']);

        return array_slice($sum, $this->threshold);
    }

    /**
     * Sorting algorithm.
     *
     * @param array $data1
     * @param array $data2
     *
     * @return int
     */
    private function drop(int $data1, int $data2): int
    {
        return $data1['roll'] <=> $data2['roll'];
    }

    /**
     * Returns the drop lowest value.
     *
     * @param int[] $sum
     *
     * @return int[]
     */
    private function dropLowest(array $sum): array
    {
        uasort($sum, [$this, 'drop']);

        return array_slice($sum, $this->threshold);
    }

    /**
     * Returns the keep highest value.
     *
     * @param int[] $sum
     *
     * @return int[]
     */
    private function keepHighest(array $sum): array
    {
        uasort($sum, [$this, 'drop']);
        rsort($sum);

        return array_slice($sum, 0, $this->threshold);
    }

    /**
     * Returns the keep lowest value.
     *
     * @param int[] $sum
     *
     * @return int[]
     */
    private function keepLowest(array $sum): array
    {
        uasort($sum, [$this, 'drop']);
        rsort($sum);

        return array_slice($sum, 0, $this->threshold);
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

        return new self($rollable, $this->getOperator(), $this->threshold);
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
        if ($operator === $this->getOperator()) {
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
     * @param int $value
     * @param int $threshold
     *
     * @return self
     */
    public function withThreshold(int $threshold): self
    {
        if ($threshold === $this->threshold) {
            return $this;
        }

        return new self($this->rollable, $this->getOperator(), $threshold);
    }
}
