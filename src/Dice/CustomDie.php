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
use function array_map;
use function count;
use function explode;
use function implode;
use function max;
use function min;
use function preg_match;

final class CustomDie implements Dice, SupportsTracing
{
    private const REGEXP_NOTATION = '/^d\[(?<definition>(\s?(-?\d+)\s?,)*(\s?-?\d+)\s?)\]$/i';

    private array $values = [];

    private RandomIntGenerator $randomIntGenerator;

    private Tracer $tracer;

    /**
     * @param array<int> $values
     *
     * @throws SyntaxError if the number of side is invalid
     */
    private function __construct(array $values, RandomIntGenerator $randomIntGenerator = null, Tracer $tracer = null)
    {
        $nbSides = count($values);
        if (2 > $nbSides) {
            throw SyntaxError::dueToTooFewSides($nbSides);
        }

        $this->randomIntGenerator = $randomIntGenerator ?? new SystemRandomInt();
        $this->values = $values;
        $this->tracer = $tracer ?? new NullTracer();
    }

    /**
     * New instance from a dice notation.
     *
     * @throws SyntaxError if the notation is not supported or invalid
     */
    public static function fromNotation(string $notation, RandomIntGenerator $randomIntGenerator = null, Tracer $tracer = null): self
    {
        if (1 !== preg_match(self::REGEXP_NOTATION, $notation, $matches)) {
            throw SyntaxError::dueToInvalidNotation($notation);
        }

        $sides = array_map(fn (string $value): int => (int) trim($value), explode(',', $matches['definition']));

        return new self($sides, $randomIntGenerator, $tracer);
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
        return 'D['.implode(',', $this->values).']';
    }

    public function size(): int
    {
        return count($this->values);
    }

    public function minimum(): int
    {
        return $this->generate(min($this->values), __METHOD__)->value();
    }

    private function generate(int $value, string $method): Roll
    {
        $roll = new Toss($value, (string) $value, new TossContext($this, $method));

        $this->tracer->append($roll);

        return $roll;
    }

    public function maximum(): int
    {
        return $this->generate(max($this->values), __METHOD__)->value();
    }

    public function roll(): Roll
    {
        $index = $this->randomIntGenerator->generateInt(0, count($this->values) - 1);

        return $this->generate($this->values[$index], __METHOD__);
    }
}
