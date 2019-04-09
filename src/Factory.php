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
use Bakame\DiceRoller\Contract\Profiler;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Exception\IllegalValue;
use Bakame\DiceRoller\Exception\TooFewSides;
use Bakame\DiceRoller\Exception\TooManyObjects;
use Bakame\DiceRoller\Exception\UnknownAlgorithm;
use Bakame\DiceRoller\Exception\UnknownExpression;
use Bakame\DiceRoller\Modifier\Arithmetic;
use Bakame\DiceRoller\Modifier\DropKeep;
use Bakame\DiceRoller\Modifier\Explode;
use Bakame\DiceRoller\Profiler\LogProfiler;
use Psr\Log\NullLogger;
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
     * @var Profiler
     */
    private $profiler;

    /**
     * new Instance.
     *
     * @param ?Parser   $parser
     * @param ?Profiler $profiler
     */
    public function __construct(?Parser $parser = null, ?Profiler $profiler = null)
    {
        $this->parser = $parser ?? new ExpressionParser();
        $this->profiler = $profiler ?? new LogProfiler(new NullLogger());
    }

    /**
     * Returns a new rollable object from a string expression.
     */
    public function newInstance(string $expression): Rollable
    {
        $parsed = $this->parser->parse($expression);

        return $this->create($parsed);
    }

    /**
     * Returns a new rollable object from a parsed expression.
     */
    private function create(array $parsed): Rollable
    {
        $rollable = array_reduce($parsed, [$this, 'addRollable'], new Cup());
        $rollable->setProfiler($this->profiler);

        return $this->flattenRollable($rollable);
    }

    /**
     * Adds a Rollable item to a pool.
     *
     * @throws IllegalValue
     * @throws TooFewSides
     * @throws UnknownExpression
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
     * @throws IllegalValue
     * @throws TooFewSides
     * @throws UnknownExpression
     */
    private function createRollable(array $parts): Rollable
    {
        if (isset($parts['composite'])) {
            return $this->create($parts['composite']);
        }

        $die = $this->createDice($parts['simple']['type']);
        $rollable = Cup::fromRollable($die, $parts['simple']['quantity']);
        $rollable->setProfiler($this->profiler);

        return $rollable;
    }

    /**
     * Parse Rollable definition.
     *
     * @throws TooFewSides
     * @throws UnknownExpression
     */
    private function createDice(string $expression): Dice
    {
        if ('DF' === $expression) {
            return new FudgeDie();
        }

        if ('D%' === $expression) {
            return new PercentileDie();
        }

        if (false !== strpos($expression, '[')) {
            return CustomDie::fromString($expression);
        }

        return SidedDie::fromString($expression);
    }

    /**
     * Decorates the Rollable object with modifiers objects.
     *
     * @throws TooManyObjects
     * @throws IllegalValue
     * @throws UnknownAlgorithm
     */
    private function decorate(Rollable $rollable, array $matches): Rollable
    {
        if ('arithmetic' === $matches['modifier']) {
            $modifier = new Arithmetic($rollable, $matches['operator'], $matches['value']);
            $modifier->setProfiler($this->profiler);

            return $modifier;
        }

        if ('dropkeep' === $matches['modifier']) {
            $modifier = new DropKeep($rollable, $matches['operator'], $matches['value']);
            $modifier->setProfiler($this->profiler);

            return $modifier;
        }

        $modifier = new Explode($rollable, $matches['operator'], $matches['value']);
        $modifier->setProfiler($this->profiler);

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
