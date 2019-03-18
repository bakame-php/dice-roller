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

namespace Bakame\DiceRoller\Profiler;

use Bakame\DiceRoller\Profiler;
use Bakame\DiceRoller\Rollable;

final class NullProfiler implements Profiler
{
    public function addTrace(Rollable $rollable, string $method, int $roll, string $trace): void
    {
    }
}
