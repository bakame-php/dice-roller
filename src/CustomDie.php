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

use Bakame\DiceRoller\Exception\TooFewSides;
use Bakame\DiceRoller\Exception\UnknownExpression;
use function count;
use function implode;
use function max;
use function min;
use function random_int;
use function sprintf;

final class CustomDie implements Dice
{
    /**
     * @var int[]
     */
    private $values = [];

    /**
     * New instance.
     *
     * @param int ...$values
     */
    public function __construct(int ...$values)
    {
        if (2 > count($values)) {
            throw new TooFewSides(sprintf('Your dice must have at least 2 sides, `%s` given.', count($values)));
        }

        $this->values = $values;
    }

    /**
     * new instance from a string expression.
     *
     * @throws UnknownExpression if the expression is not valid.
     */
    public static function fromString(string $expression): self
    {
        if (1 === preg_match('/^d\[(?<definition>.*)\]$/i', $expression, $matches)) {
            $sides = explode(',', $matches['definition']);
            $sides = (array) filter_var(array_filter($sides, 'trim'), FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

            return new self(...$sides);
        }

        throw new UnknownExpression(sprintf('the submitted dice format `%s` is invalid ', $expression));
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return 'D['.implode(',', $this->values).']';
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): int
    {
        return count($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        return min($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        return max($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $index = random_int(1, count($this->values) - 1);
        $roll = $this->values[$index];

        return $roll;
    }
}
