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

trait ProfilerAware
{
    /**
     * @var Profiler
     */
    private $profiler;

    public function setProfiler(?Profiler $profiler = null): void
    {
        $this->profiler = $profiler ?? new NullProfiler();
    }

    public function getProfiler(): Profiler
    {
        return $this->profiler;
    }
}
