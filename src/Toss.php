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

use JsonSerializable;

final class Toss implements JsonSerializable, Roll
{
    public function __construct(
        private int $value,
        private string $operation,
        private Context|null $context = null
    ) {
    }

    public function value(): int
    {
        return $this->value;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function context(): Context|null
    {
        return $this->context;
    }

    public function asString(): string
    {
        return (string) $this->value;
    }

    public function info(): array
    {
        $roll = ['value' => $this->value, 'operation' => $this->operation];
        if (null === $this->context) {
            return $roll;
        }

        return $roll + $this->context->asArray();
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
