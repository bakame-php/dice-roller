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

use Bakame\DiceRoller\Contract\AcceptsTracer;
use Bakame\DiceRoller\Contract\Dice;
use Bakame\DiceRoller\Contract\Parser;
use Bakame\DiceRoller\Contract\Pool;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Dice\CustomDie;
use Bakame\DiceRoller\Dice\FudgeDie;
use Bakame\DiceRoller\Dice\PercentileDie;
use Bakame\DiceRoller\Dice\SidedDie;
use Bakame\DiceRoller\Exception\SyntaxError;
use Bakame\DiceRoller\Exception\UnknownNotation;
use Bakame\DiceRoller\Modifier\Arithmetic;
use Bakame\DiceRoller\Modifier\DropKeep;
use Bakame\DiceRoller\Modifier\Explode;
use Bakame\DiceRoller\Tracer\NullTracer;
use function array_reduce;
use function count;
use function iterator_to_array;
use function strpos;

final class Factory
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * new Instance.
     *
     * @param ?Parser $parser
     * @param ?Tracer $tracer
     */
    public function __construct(?Parser $parser = null, ?Tracer $tracer = null)
    {
        $this->parser = $parser ?? new NotationParser();
        $this->tracer = $tracer ?? new NullTracer();
    }

    /**
     * Returns a new rollable object from a string expression.
     */
    public function newInstance(string $notation): Rollable
    {
        $parsed = $this->parser->parse($notation);

        return $this->create($parsed);
    }

    /**
     * Returns a new rollable object from a parsed expression.
     */
    private function create(array $parsed): Rollable
    {
        $rollable = array_reduce($parsed, [$this, 'addRollable'], new Cup());
        $rollable->setTracer($this->tracer);

        return $this->flattenRollable($rollable);
    }

    /**
     * Adds a Rollable item to a pool.
     *
     * @throws SyntaxError
     * @throws UnknownNotation
     */
    private function addRollable(Cup $pool, array $parts): Cup
    {
        $rollable = $this->createRollable($parts['definition']);
        $rollable = array_reduce($parts['modifiers'], [$this, 'decorate'], $rollable);
        $rollable = $this->flattenRollable($rollable);

        return $pool->withAddedRollable($rollable);
    }

    /**
     * Generates the Pool from the expression matched pattern.
     *
     * @throws SyntaxError
     * @throws UnknownNotation
     */
    private function createRollable(array $parts): Rollable
    {
        if (isset($parts['composite'])) {
            return $this->create($parts['composite']);
        }

        $die = $this->createDice($parts['simple']['type']);
        if ($die instanceof AcceptsTracer) {
            $die->setTracer($this->tracer);
        }

        $rollable = Cup::fromRollable($die, $parts['simple']['quantity']);
        $rollable->setTracer($this->tracer);

        return $rollable;
    }

    /**
     * Parse Rollable definition.
     *
     * @throws SyntaxError
     * @throws UnknownNotation
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
     * @throws UnknownNotation
     */
    private function decorate(Rollable $rollable, array $matches): Rollable
    {
        if ('arithmetic' === $matches['modifier']) {
            $modifier = new Arithmetic($rollable, $matches['operator'], $matches['value']);
            $modifier->setTracer($this->tracer);

            return $modifier;
        }

        if ('dropkeep' === $matches['modifier']) {
            $modifier = new DropKeep($rollable, $matches['operator'], $matches['value']);
            $modifier->setTracer($this->tracer);

            return $modifier;
        }

        $modifier = new Explode($rollable, $matches['operator'], $matches['value']);
        $modifier->setTracer($this->tracer);

        return $modifier;
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

        $arr = iterator_to_array($rollable, false);

        return $arr[0];
    }
}
