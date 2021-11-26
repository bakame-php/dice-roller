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

use InvalidArgumentException;

final class SyntaxError extends InvalidArgumentException implements CanNotBeRolled
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function dueToTooFewSides(int $size): self
    {
        return new self('Your die must have at least 2 sides, `'.$size.'` given.');
    }

    public static function dueToInvalidNotation(string $notation): self
    {
        return new self('The die format `'.$notation.'` is invalid or not supported.');
    }

    public static function dueToInvalidModifier(string $algo): self
    {
        return new self('The modifier `'.$algo.'` is invalid or not supported.');
    }

    public static function dueToTooManyInstancesToRoll(int $size, int $threshold): self
    {
        return new self('The number of objects to roll `'.$size.'` MUST be lesser or equal to `'.$threshold.'`.');
    }

    public static function dueToTooFewInstancesToRoll(int $quantity): self
    {
        return new self('The quantity of dice `'.$quantity.'` is not valid. Should be > 0 .');
    }

    public static function dueToInvalidOperator(string $operator): self
    {
        return new self('Invalid or Unsupported operator `'.$operator.'`.');
    }

    public static function dueToOperatorAndValueMismatched(string $operator, int $value): self
    {
        return new self('The submitted value `'.$value.'` is invalid for the given `'.$operator.'` operator.');
    }

    public static function dueToInfiniteLoop(Pool $pool): self
    {
        return new self('This collection `'.$pool->notation().'` will generate a infinite loop.');
    }
}
