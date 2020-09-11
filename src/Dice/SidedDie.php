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
use Bakame\DiceRoller\Contract\RandomIntGenerator;
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\SupportsTracing;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Exception\SyntaxError;
use Bakame\DiceRoller\SystemRandomInt;
use Bakame\DiceRoller\Toss;
use Bakame\DiceRoller\TossContext;
use Bakame\DiceRoller\Tracer\NullTracer;
use function preg_match;

final class SidedDie implements Dice, SupportsTracing
{
    private const REGEXP_NOTATION = '/^d(?<sides>\d+)$/i';

    private int $sides;

    private RandomIntGenerator $randomIntGenerator;

    private Tracer $tracer;

    /**
     * @throws SyntaxError if a SimpleDice contains less than 2 sides
     */
    public function __construct(int $sides, RandomIntGenerator $randomIntGenerator = null, Tracer $tracer = null)
    {
        if (2 > $sides) {
            throw SyntaxError::dueToTooFewSides($sides);
        }

        $this->sides = $sides;
        $this->randomIntGenerator = $randomIntGenerator ?? new SystemRandomInt();
        $this->tracer = $tracer ?? new NullTracer();
    }

    /**
     * New instance from a dice notation.
     *
     * @throws SyntaxError if the dice notation is not valid.
     */
    public static function fromNotation(string $notation, RandomIntGenerator $randomIntGenerator = null): self
    {
        if (1 !== preg_match(self::REGEXP_NOTATION, $notation, $matches)) {
            throw SyntaxError::dueToInvalidNotation($notation);
        }

        return new self((int) $matches['sides'], $randomIntGenerator);
    }

    public function setTracer(Tracer $tracer): void
    {
        $this->tracer = $tracer;
    }

    public function jsonSerialize(): string
    {
        return $this->notation();
    }

    public function notation(): string
    {
        return 'D'.$this->sides;
    }

    public function size(): int
    {
        return $this->sides;
    }

    public function minimum(): int
    {
        $roll = new Toss(1, '1', new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    public function maximum(): int
    {
        $roll = new Toss($this->sides, (string) $this->sides, new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    public function roll(): Roll
    {
        $result = $this->randomIntGenerator->generateInt(1, $this->sides);

        $roll = new Toss($result, (string) $result, new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll;
    }
}
