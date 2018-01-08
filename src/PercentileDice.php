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
     * {@inheritdoc}
     */
    public function __toString()
    {
        return 'D%';
    }

    /**
     * Returns the side count
     *
     * @return int
     */
    public function count()
    {
        return 100;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        return 100;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): Roll
    {
        return new Result($this, random_int(1, 100));
    }
}
