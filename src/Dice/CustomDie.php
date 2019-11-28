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

namespace Bakame\DiceRoller\Dice;

use Bakame\DiceRoller\Contract\Dice;
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Exception\TooFewSides;
use Bakame\DiceRoller\Exception\UnknownExpression;
use Bakame\DiceRoller\Toss;
use function array_map;
use function count;
use function explode;
use function implode;
use function max;
use function min;
use function preg_match;
use function random_int;
use function sprintf;

final class CustomDie implements Dice
{
    private const REGEXP_EXPRESSION = '/^d\[(?<definition>((-?\d+),)*(-?\d+))\]$/i';

    /**
     * @var int[]
     */
    private $values = [];

    /**
     * New instance.
     *
     * @param int ...$values
     *
     * @throws TooFewSides
     */
    public function __construct(int ...$values)
    {
        if (2 > count($values)) {
            throw new TooFewSides(sprintf('Your die must have at least 2 sides, `%s` given.', count($values)));
        }

        $this->values = $values;
    }

    /**
     * new instance from a string expression.
     *
     * @throws TooFewSides
     * @throws UnknownExpression
     */
    public static function fromExpression(string $expression): self
    {
        if (1 !== preg_match(self::REGEXP_EXPRESSION, $expression, $matches)) {
            throw new UnknownExpression(sprintf('the submitted die format `%s` is invalid.', $expression));
        }

        $mapper = function (string $value): int {
            return (int) $value;
        };

        $sides = array_map($mapper, explode(',', $matches['definition']));

        return new self(...$sides);
    }

    /**
     * {@inheritdoc}
     */
    public function expression(): string
    {
        return 'D['.implode(',', $this->values).']';
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return count($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function minimum(): int
    {
        return min($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function maximum(): int
    {
        return max($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): Roll
    {
        $index = random_int(0, count($this->values) - 1);
        $result = $this->values[$index];

        return new Toss($result, (string) $result);
    }
}
