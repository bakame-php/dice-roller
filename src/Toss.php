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
use Bakame\DiceRoller\Contract\Rollable;

final class Toss implements Roll, \JsonSerializable
{
    /**
     * @var string
     */
    private $expression;

    /**
     * @var string
     */
    private $operation;

    /**
     * @var int
     */
    private $value;

    public function __construct(string $expression, string $operation, int $value)
    {
        $this->expression = $expression;
        $this->operation = $operation;
        $this->value = $value;
    }

    /**
     * Create a new instance from a generic Rollable instance.
     */
    public static function fromRollable(Rollable $rollable, int $value, string $operation): self
    {
        return new self($rollable->expression(), $operation, $value);
    }

    /**
     * Create a new instance from a Dice instance.
     */
    public static function fromDice(Rollable $rollable, int $value): self
    {
        return self::fromRollable($rollable, $value, (string) $value);
    }

    /**
     * {@inheritDoc}
     */
    public function expression(): string
    {
        return $this->expression;
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
    public function value(): int
    {
        return $this->value;
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
    public function toArray(): array
    {
        return [
            'expression' => $this->expression,
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
