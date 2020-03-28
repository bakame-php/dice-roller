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

namespace Bakame\DiceRoller\Exception;

use Bakame\DiceRoller\Contract\CanNotBeRolled;
use Bakame\DiceRoller\Contract\Pool;

final class SyntaxError extends \InvalidArgumentException implements CanNotBeRolled
{
    public static function dueToTooFewSides(int $size): self
    {
        return new self(sprintf('Your die must have at least 2 sides, `%s` given.', $size));
    }

    public static function dueToInvalidNotation(string $notation): self
    {
        return new self(sprintf('The die format `%s` is invalid or not supported.', $notation));
    }

    public static function dueToInvalidModifier(string $algo): self
    {
        return new self(sprintf('The modifier `%s` is invalid or not supported', $algo));
    }

    public static function dueToTooManyRollableInstances(Pool $pool, int $threshold): self
    {
        return new self(sprintf('The number of rollable objects `%s` MUST be lesser or equal to the threshold value `%s`', count($pool), $threshold));
    }

    public static function dueToTooFewRollableInstances(int $quantity): self
    {
        return new self(sprintf('The quantity of dice `%s` is not valid. Should be > 0', $quantity));
    }

    public static function dueToInvalidOperator(string $operator): self
    {
        return new self(sprintf('Invalid or Unsupported operator `%s`', $operator));
    }

    public static function dueToOperatorAndValueMismatched(string $operator, int $value): self
    {
        return new self(sprintf('The submitted value `%s` is invalid for the given `%s` operator', $value, $operator));
    }

    public static function dueToInfiniteLoop(Pool $pool): self
    {
        return new self(sprintf('This collection %s will generate a infinite loop', $pool->notation()));
    }
}
