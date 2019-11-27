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
     * @var string
     */
    private $expression;

    public function __construct(int $value, string $operation, string $expression)
    {
        $this->value = $value;
        $this->operation = $operation;
        $this->expression = $expression;
    }

    /**
     * Create a new instance from a Rollable type.
     */
    public static function fromRollable(Rollable $rollable, int $value, string $operation): self
    {
        return new self($value, $operation, $rollable->expression());
    }

    /**
     * Create a new instance from a Dice type.
     */
    public static function fromDice(Rollable $rollable, int $value): self
    {
        return new self($value, (string) $value, $rollable->expression());
    }

    public function value(): int
    {
        return $this->value;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function expression(): string
    {
        return $this->expression;
    }

    public function toString(): string
    {
        return (string) $this->value;
    }
}
