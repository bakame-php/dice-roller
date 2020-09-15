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

use Bakame\DiceRoller\Contract\RandomIntGenerator;
use Bakame\DiceRoller\Exception\IntGeneratorError;
use function random_int;

final class SystemRandomInt implements RandomIntGenerator
{
    public function generateInt(int $minimum, int $maximum): int
    {
        if ($maximum < $minimum) {
            throw IntGeneratorError::dueToRollValue();
        }

        return random_int($minimum, $maximum);
    }
}
