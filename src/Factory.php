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
use function array_reduce;
use function count;
use function iterator_to_array;
use function strpos;

final class Factory
{
    use ProfilerAware;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * Factory constructor.
     *
     * @param ?Parser   $parser
     * @param ?Profiler $profiler
     */
    public function __construct(?Parser $parser = null, ?Profiler $profiler = null)
    {
        $this->parser = $parser ?? new ExpressionParser();
        $this->setProfiler($profiler);
    }

    /**
     * Returns a new rollable object from a string expression.
     */
    public function newInstance(string $expression): Rollable
    {
        $rollable = array_reduce($this->parser->parse($expression), [$this, 'addRollable'], new Cup());
        $rollable->setProfiler($this->profiler);

        return $this->flattenRollable($rollable);
    }

    /**
     * Adds a Rollable item to a pool.
     *
     * @throws IllegalValue
     * @throws TooFewSides
     * @throws TooManyObjects
     * @throws UnknownAlgorithm
     * @throws UnknownExpression
     */
    private function addRollable(Cup $pool, array $parts): Cup
    {
        $modifiers = $parts['modifiers'] ?? [];
        $rollable = array_reduce($modifiers, [$this, 'decorate'], $this->createRollable($parts));

        return $pool->withAddedRollable($this->flattenRollable($rollable));
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
        if ([] === $parts) {
            $rollable = new Cup();
            $rollable->setProfiler($this->profiler);

            return $rollable;
        }

        if (isset($parts['compositePool'])) {
            return $this->newInstance($parts['compositePool']['expression']);
        }

        return Cup::createFromRollable(
            $this->createDice($parts['pool']['type']),
            $parts['pool']['quantity'],
            $this->profiler
        );
    }

    /**
     * Parse Rollable definition.
     *
     * @throws TooFewSides
     * @throws UnknownExpression
     */
    private function createDice(string $definition): Dice
    {
        if ('DF' === $definition) {
            return new FudgeDie();
        }

        if ('D%' === $definition) {
            return new PercentileDie();
        }

        if (false !== strpos($definition, '[')) {
            return CustomDie::fromString($definition);
        }

        return SidedDie::fromString($definition);
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
