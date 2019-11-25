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

namespace Bakame\DiceRoller\Contract;

interface Tracer
{
    /**
     * @param array $optionals optional data that can be added to the Trace objects.
     */
    public function createTrace(
        string $source,
        Rollable $subject,
        string $operation,
        int $result,
        array $optionals = []
    ): Trace;

    /**
     * Record a Rollable operation.
     */
    public function addTrace(Trace $trace): void;
}
