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
     * @var string
     */
    private $trace;

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->trace = '';

        return 'D%';
    }

    /**
     * Returns the side count
     *
     * @return int
     */
    public function count()
    {
        $this->trace = '';

        return 100;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $this->trace = '';

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $this->trace = '';

        return 100;
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
        $roll = random_int(1, 100);
        $this->trace = (string) $roll;

        return $roll;
    }
}
