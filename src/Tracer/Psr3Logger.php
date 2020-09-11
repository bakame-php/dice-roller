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

use Psr\Log\AbstractLogger;
use function strtr;

final class Psr3Logger extends AbstractLogger
{
    private array $logs = [];

    public function log($level, $message, array $context = []): void
    {
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{'.$key.'}'] = $val;
        }

        $this->logs[$level] = $this->logs[$level] ?? [];
        $this->logs[$level][] = strtr($message, $replace);
    }

    /**
     * Retrieves the logs from the memory.
     *
     * @param ?string $level
     */
    public function getLogs(?string $level = null): array
    {
        if (null === $level) {
            return $this->logs;
        }

        return $this->logs[$level] ?? [];
    }

    /**
     * Clear the log messages.
     *
     * @param ?string $level
     */
    public function clear(?string $level = null): void
    {
        if (null === $level) {
            $this->logs = [];

            return;
        }

        unset($this->logs[$level]);
    }
}
