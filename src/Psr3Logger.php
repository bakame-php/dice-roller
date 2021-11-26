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

use Psr\Log\AbstractLogger;
use Stringable;
use function strtr;

final class Psr3Logger extends AbstractLogger
{
    private array $logs = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{'.$key.'}'] = $val;
        }

        $this->logs[$level] = $this->logs[$level] ?? [];
        $this->logs[$level][] = strtr((string) $message, $replace);
    }

    /**
     * Retrieves the logs from the memory.
     */
    public function getLogs(string|null $level = null): array
    {
        if (null === $level) {
            return $this->logs;
        }

        return $this->logs[$level] ?? [];
    }

    /**
     * Clear the log messages.
     */
    public function clear(string|null $level = null): void
    {
        if (null === $level) {
            $this->logs = [];

            return;
        }

        unset($this->logs[$level]);
    }
}
