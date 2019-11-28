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

use Bakame\DiceRoller\Contract\Roll;

final class Toss implements Roll, \JsonSerializable
{
    /**
     * @var int
     */
    private $value;

    /**
     * @var string
     */
    private $operation;

    public function __construct(int $value, string $operation)
    {
        $this->value = $value;
        $this->operation = $operation;
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
    public function toString(): string
    {
        return (string) $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function asArray(): array
    {
        return [
            'operation' => $this->operation,
            'value' => $this->value,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
