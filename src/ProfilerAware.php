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

use Bakame\DiceRoller\Contract\Profiler;
use Bakame\DiceRoller\Profiler\NullProfiler;

trait ProfilerAware
{
    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * Profiler setter.
     * @param ?Profiler $profiler
     */
    public function setProfiler(?Profiler $profiler = null): void
    {
        $this->profiler = $profiler ?? new NullProfiler();
    }

    /**
     * Profiler getter.
     */
    public function getProfiler(): Profiler
    {
        return $this->profiler;
    }

    /**
     * Get the trace as string from a collection of internal roll results.
     *
     * @param int[] $rolls
     */
    private function getTraceAsString(array $rolls): string
    {
        $arr = [];
        foreach ($rolls as $value) {
            if (0 > $value) {
                $arr[] = '('.$value.')';
                continue;
            }
            $arr[] = $value;
        }

        return implode(' + ', $arr);
    }
}
