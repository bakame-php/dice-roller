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

namespace Bakame\DiceRoller\Exception;

use Bakame\DiceRoller\Contract\CanNotBeRolled;

class IntGeneratorError extends \LogicException implements CanNotBeRolled
{
    public static function dueToRollValue(): self
    {
        return new self('Minimum value must be less than or equal to the maximum value');
    }
}
