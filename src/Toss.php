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
    /**
     * @var int
     */
    private $value;

    /**
     * @var string
     */
    private $operation;

    /**
     * @var Context|null
     */
    private $context;

    public function __construct(int $value, string $operation, ?Context $context = null)
    {
        $this->value = $value;
        $this->operation = $operation;
        $this->context = $context;
    }

    /**
     * {@inheritDoc}
     */
    public function value(): int
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function operation(): string
    {
        return $this->operation;
    }

    /**
     * {@inheritDoc}
     */
    public function context(): ?Context
    {
        return $this->context;
    }

    /**
     * {@inheritDoc}
     */
    public function asString(): string
    {
        return (string) $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function asArray(): array
    {
        $roll = [
            'operation' => $this->operation,
            'value' => $this->value,
        ];

        if (null === $this->context) {
            return $roll;
        }

        return $roll + $this->context->asArray();
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): int
    {
        return $this->value;
    }
}