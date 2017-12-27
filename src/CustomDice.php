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

use Countable;

final class CustomDice implements Countable, Rollable
{
    /**
     * @var string
     */
    private $trace;

    /**
     * @var int[]
     */
    private $values = [];

    /**
     * New instance
     *
     * @param int ...$values
     */
    public function __construct(int ...$values)
    {
        if (2 > count($values)) {
            throw new Exception(sprintf('Your dice must have at least 2 sides, `%s` given.', count($values)));
        }

        $this->trace = '';
        $this->values = $values;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->trace = '';

        return 'D['.implode(',', $this->values).']';
    }

    /**
     * Returns the side count
     *
     * @return int
     */
    public function count()
    {
        $this->trace = '';

        return count($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $this->trace = '';

        return min($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $this->trace = '';

        return max($this->values);
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
    public function roll(): int
    {
        $index = random_int(1, count($this->values) - 1);
        $roll = $this->values[$index];
        $this->trace = (string) $roll;

        return $roll;
    }
}
