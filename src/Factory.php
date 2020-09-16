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

use Bakame\DiceRoller\Contract\Dice;
use Bakame\DiceRoller\Contract\Parser;
use Bakame\DiceRoller\Contract\Pool;
use Bakame\DiceRoller\Contract\RandomIntGenerator;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\SupportsTracing;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Dice\CustomDie;
use Bakame\DiceRoller\Dice\FudgeDie;
use Bakame\DiceRoller\Dice\PercentileDie;
use Bakame\DiceRoller\Dice\SidedDie;
use Bakame\DiceRoller\Exception\SyntaxError;
use Bakame\DiceRoller\Modifier\Arithmetic;
use Bakame\DiceRoller\Modifier\DropKeep;
use Bakame\DiceRoller\Modifier\Explode;
use Bakame\DiceRoller\Tracer\NullTracer;
use function count;
use function iterator_to_array;
use function strpos;

final class Factory
{
    private Parser $parser;

    public function __construct(Parser $parser = null)
    {
        $this->parser = $parser ?? new NotationParser();
    }

    public function newInstance(string $notation, Tracer $tracer = null, RandomIntGenerator $randomIntGenerator = null): Rollable
    {
        return $this->create(
            $this->parser->parse($notation),
            $randomIntGenerator ?? new SystemRandomInt(),
            $tracer ?? new NullTracer()
        );
    }

    /**
     * Returns a new Rollable object from a parsed dice notation.
     */
    private function create(array $parsed, RandomIntGenerator $randomIntGenerator, Tracer $tracer): Rollable
    {
        $rollable = new Cup();
        $rollable->setTracer($tracer);
        foreach ($parsed as $parts) {
            $rollable = $this->addRollable($rollable, $parts, $randomIntGenerator, $tracer);
        }

        return $this->flattenRollable($rollable);
    }

    /**
     * Adds a Rollable item to a pool.
     */
    private function addRollable(Cup $pool, array $parts, RandomIntGenerator $randomIntGenerator, Tracer $tracer): Cup
    {
        $rollable = $this->createRollable($parts['definition'], $randomIntGenerator, $tracer);
        foreach ($parts['modifiers'] as $matches) {
            $rollable = $this->decorate($rollable, $matches);
            if ($rollable instanceof SupportsTracing) {
                $rollable->setTracer($tracer);
            }
        }

        $rollable = $this->flattenRollable($rollable);

        return $pool->withAddedRollable($rollable);
    }

    /**
     * Generates the Pool from the dice notation matched pattern.
     *
     * @throws SyntaxError
     */
    private function createRollable(array $parts, RandomIntGenerator $randomIntGenerator, Tracer $tracer): Rollable
    {
        if (isset($parts['composite'])) {
            return $this->create($parts['composite'], $randomIntGenerator, $tracer);
        }

        $die = $this->createDice($parts['simple']['type'], $randomIntGenerator);
        if ($die instanceof SupportsTracing) {
            $die->setTracer($tracer);
        }

        $cup = Cup::fromRollable($die, (int) $parts['simple']['quantity']);
        $cup->setTracer($tracer);

        return $cup;
    }

    /**
     * Parse Rollable definition.
     *
     * @throws SyntaxError
     */
    private function createDice(string $notation, RandomIntGenerator $randomIntGenerator): Dice
    {
        if ('DF' === $notation) {
            return new FudgeDie($randomIntGenerator);
        }

        if ('D%' === $notation) {
            return new PercentileDie($randomIntGenerator);
        }

        if (false !== strpos($notation, '[')) {
            return CustomDie::fromNotation($notation, $randomIntGenerator);
        }

        return SidedDie::fromNotation($notation, $randomIntGenerator);
    }

    /**
     * Decorates the Rollable object with modifiers objects.
     *
     * @throws SyntaxError
     */
    private function decorate(Rollable $rollable, array $matches): Rollable
    {
        if ('arithmetic' === $matches['modifier']) {
            return $this->createArithmeticModifier($rollable, $matches['operator'], $matches['value']);
        }

        if ('dropkeep' === $matches['modifier']) {
            return $this->createDropKeepModifier($rollable, $matches['operator'], $matches['value']);
        }

        return $this->createExplodeModifier($rollable, $matches['operator'], $matches['value']);
    }

    /**
     * Decorates the Rollable object with modifiers objects.
     *
     * @throws SyntaxError
     */
    private function createArithmeticModifier(Rollable $rollable, string $operator, int $value): Rollable
    {
        if ('+' === $operator) {
            return Arithmetic::add($rollable, $value);
        }

        if ('-' === $operator) {
            return Arithmetic::sub($rollable, $value);
        }

        if ('*' === $operator) {
            return Arithmetic::mul($rollable, $value);
        }

        if ('/' === $operator) {
            return Arithmetic::div($rollable, $value);
        }

        if ('^' === $operator) {
            return Arithmetic::pow($rollable, $value);
        }

        throw SyntaxError::dueToInvalidOperator($operator);
    }

    /**
     * Decorates the Rollable object with modifiers objects.
     *
     * @throws SyntaxError
     */
    private function createDropKeepModifier(Rollable $rollable, string $operator, int $value): Rollable
    {
        if ('KH' === $operator) {
            return DropKeep::keepHighest($rollable, $value);
        }

        if ('KL' === $operator) {
            return DropKeep::keepLowest($rollable, $value);
        }

        if ('DH' === $operator) {
            return DropKeep::dropHighest($rollable, $value);
        }

        if ('DL' === $operator) {
            return DropKeep::dropLowest($rollable, $value);
        }

        throw SyntaxError::dueToInvalidOperator($operator);
    }

    private function createExplodeModifier(Rollable $rollable, string $operator, int $value): Rollable
    {
        if ('=' === $operator) {
            return Explode::equals($rollable, $value);
        }

        if ('>' === $operator) {
            return Explode::greaterThan($rollable, $value);
        }

        if ('<' === $operator) {
            return Explode::lesserThan($rollable, $value);
        }

        throw SyntaxError::dueToInvalidOperator($operator);
    }

    /**
     * Extracts the Rollable object from a Pool with only one item.
     */
    private function flattenRollable(Rollable $rollable): Rollable
    {
        if (!$rollable instanceof Pool) {
            return $rollable;
        }

        if (1 !== count($rollable)) {
            return $rollable;
        }

        return iterator_to_array($rollable, false)[0];
    }
}
