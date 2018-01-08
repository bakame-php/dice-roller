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
use IteratorAggregate;

interface Roll extends Countable, IteratorAggregate
{
    const KEEP_ROLL = 1;
    const DROP_ROLL = 2;

    /**
     * Returns the roll result
     *
     * @return int|null
     */
    public function getResult();

    /**
     * Returns the rollable annotation
     *
     * @return int
     */
    public function getAnnotation(): string;

    /**
     * Returns the roll expression
     *
     * @return string
     */
    public function getExpression(): string;

    /**
     * Tell whether the roll value will be kept
     *
     * @return bool
     */
    public function isOK(): bool;
}
