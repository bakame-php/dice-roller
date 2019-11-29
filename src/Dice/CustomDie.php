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

use Bakame\DiceRoller\TossContext;
use Bakame\DiceRoller\Contract\AcceptsTracer;
use Bakame\DiceRoller\Contract\Dice;
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Exception\TooFewSides;
use Bakame\DiceRoller\Exception\UnknownNotation;
use Bakame\DiceRoller\Toss;
use Bakame\DiceRoller\Tracer\NullTracer;
use function array_map;
use function count;
use function explode;
use function implode;
use function max;
use function min;
use function preg_match;
use function random_int;
use function sprintf;

final class CustomDie implements Dice, AcceptsTracer
{
    private const REGEXP_NOTATION = '/^d\[(?<definition>((-?\d+),)*(-?\d+))\]$/i';

    /**
     * @var int[]
     */
    private $values = [];

    /**
     * @var Tracer
     */
    private $tracer;

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
        $this->setTracer(new NullTracer());
    }

    /**
     * new instance from a string expression.
     *
     * @throws TooFewSides
     * @throws UnknownNotation
     */
    public static function fromNotation(string $notation): self
    {
        if (1 !== preg_match(self::REGEXP_NOTATION, $notation, $matches)) {
            throw new UnknownNotation(sprintf('the submitted die format `%s` is invalid.', $notation));
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
    public function setTracer(Tracer $tracer): void
    {
        $this->tracer = $tracer;
    }

    /**
     * {@inheritdoc}
     */
    public function notation(): string
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
        $result = min($this->values);
        $roll = new Toss($result, (string) $result, new TossContext($this, __METHOD__));

        $this->tracer->addTrace($roll);

        return $roll->value();
    }

    /**
     * {@inheritdoc}
     */
    public function maximum(): int
    {
        $result = max($this->values);
        $roll = new Toss($result, (string) $result, new TossContext($this, __METHOD__));

        $this->tracer->addTrace($roll);

        return $roll->value();
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): Roll
    {
        $index = random_int(0, count($this->values) - 1);
        $result = $this->values[$index];
        $roll = new Toss($result, (string) $result, new TossContext($this, __METHOD__));

        $this->tracer->addTrace($roll);

        return $roll;
    }
}
