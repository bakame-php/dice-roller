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

namespace Bakame\DiceRoller;

use Bakame\DiceRoller\Contract\CanNotBeRolled;

final class CanNotGenerateInt extends \LogicException implements CanNotBeRolled
{
    private function __construct(string $message = '')
    {
        parent::__construct($message);
    }

    public static function dueToMismatchedValue(): self
    {
        return new self('Minimum value must be less than or equal to the maximum value.');
    }
}
