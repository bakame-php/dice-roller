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

    /**
     * @param ?Parser $parser
     */
    public function __construct(?Parser $parser = null)
    {
        $this->parser = $parser ?? new NotationParser();
    }

    /**
     * Returns a new rollable object from a dice notation.
     *
     * @param ?Tracer $tracer
     */
    public function newInstance(string $notation, ?Tracer $tracer = null): Rollable
    {
        $parsed = $this->parser->parse($notation);
        $tracer = $tracer ?? new NullTracer();

        return $this->create($parsed, $tracer);
    }

    /**
     * Returns a new Rollable object from a parsed dice notation.
     */
    private function create(array $parsed, Tracer $tracer): Rollable
    {
        $rollable = new Cup();
        $rollable->setTracer($tracer);
        foreach ($parsed as $parts) {
            $rollable = $this->addRollable($rollable, $parts, $tracer);
        }

        return $this->flattenRollable($rollable);
    }

    /**
     * Adds a Rollable item to a pool.
     */
    private function addRollable(Cup $pool, array $parts, Tracer $tracer): Cup
    {
        $rollable = $this->createRollable($parts['definition'], $tracer);
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
    private function createRollable(array $parts, Tracer $tracer): Rollable
    {
        if (isset($parts['composite'])) {
            return $this->create($parts['composite'], $tracer);
        }

        $die = $this->createDice($parts['simple']['type']);
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
    private function createDice(string $notation): Dice
    {
        if ('DF' === $notation) {
            return new FudgeDie();
        }

        if ('D%' === $notation) {
            return new PercentileDie();
        }

        if (false !== strpos($notation, '[')) {
            return CustomDie::fromNotation($notation);
        }

        return SidedDie::fromNotation($notation);
    }

    /**
     * Decorates the Rollable object with modifiers objects.
     *
     * @throws SyntaxError
     */
    private function decorate(Rollable $rollable, array $matches): Rollable
    {
        if ('arithmetic' === $matches['modifier']) {
            return new Arithmetic($rollable, $matches['operator'], $matches['value']);
        }

        if ('dropkeep' === $matches['modifier']) {
            return new DropKeep($rollable, $matches['operator'], $matches['value']);
        }

        return new Explode($rollable, $matches['operator'], $matches['value']);
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
