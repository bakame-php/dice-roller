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

final class PercentileDice implements Countable, Rollable
{
    /**
     * @var array
     */
    private $stack = [];

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->stack = [];

        return 'D%';
    }

    /**
     * Returns the side count
     *
     * @return int
     */
    public function count()
    {
        $this->stack = [];

        return 100;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $this->stack = [];

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $this->stack = [];

        return 100;
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
        return $this->stack['roll'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $roll = random_int(1, 100);
        $this->stack = ['roll' => (string) $roll];

        return $roll;
    }
}
