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

use Bakame\DiceRoller\Contract\Context;
use Bakame\DiceRoller\Contract\Roll;

final class Toss implements Roll
{
    private int $value;

    private string $operation;

    private ?Context $context;

    public function __construct(int $value, string $operation, ?Context $context = null)
    {
        $this->value = $value;
        $this->operation = $operation;
        $this->context = $context;
    }

    public function value(): int
    {
        return $this->value;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function context(): ?Context
    {
        return $this->context;
    }

    public function asString(): string
    {
        return (string) $this->value;
    }

    public function info(): array
    {
        $roll = [
            'value' => $this->value,
            'operation' => $this->operation,
        ];

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
