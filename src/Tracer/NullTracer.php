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

namespace Bakame\DiceRoller\Tracer;

use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\Tracer;

final class NullTracer implements Tracer
{
    public function append(Roll $roll): void
    {
    }
}
