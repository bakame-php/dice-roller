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
use Bakame\DiceRoller\Profiler\ProfilerAware;
use function array_map;
use function count;
use function iterator_to_array;
use function strpos;
use function strtoupper;

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
     * Returns a new Cup Instance from a string pattern.
     */
    public function newInstance(string $expression): Rollable
    {
        $pool = $this->createComplexPool($expression);

        return $this->flattenRollable($pool);
    }

    /**
     * Creates a complex mixed Pool.
     */
    private function createComplexPool(string $expression): Pool
    {
        $poolsExpArray = $this->parser->extractPool($expression);
        $poolsObjArray = array_map([$this, 'createPoolFromString'], $poolsExpArray);

        $pool = new Cup(...$poolsObjArray);
        $pool->setProfiler($this->profiler);

        return $pool;
    }

    /**
     * Returns a collection of equals dice.
     *
     * @throws IllegalValue
     * @throws TooFewSides
     * @throws TooManyObjects
     * @throws UnknownAlgorithm
     * @throws UnknownExpression
     */
    private function createPoolFromString(string $expression): Rollable
    {
        $parts = $this->parser->parsePool($expression);
        if ([] === $parts) {
            $rollable = new Cup();
            $rollable->setProfiler($this->profiler);

            return $rollable;
        }

        $pool = $this->createPool($parts['pool']);
        $pool = $this->decorate($pool, $parts['modifiers']);

        return $this->flattenRollable($pool);
    }

    /**
     * Extracts the Rollable object from a Pool with only one item.
     */
    private function flattenRollable(Rollable $rollable): Rollable
    {
        if (!$rollable instanceof Pool || 1 !== count($rollable)) {
            return $rollable;
        }

        $arr = iterator_to_array($rollable, false);

        return $arr[0];
    }

    /**
     * Generates the Cup from the expression matched pattern.
     *
     * @throws IllegalValue
     * @throws TooFewSides
     * @throws UnknownExpression
     */
    private function createPool(array $matches): Pool
    {
        if (isset($matches['mixed'])) {
            return $this->createComplexPool($matches['mixed']);
        }

        return Cup::createFromRollable(
            $this->createDiceFromString($matches['type']),
            (int) $matches['quantity'],
            $this->profiler
        );
    }

    /**
     * Parse Rollable definition.
     *
     * @throws TooFewSides
     * @throws UnknownExpression
     */
    private function createDiceFromString(string $definition): Dice
    {
        $definition = strtoupper($definition);
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
     * Decorates the Rollable object with some decorator.
     *
     * @throws IllegalValue
     * @throws TooManyObjects
     * @throws UnknownAlgorithm
     */
    private function decorate(Rollable $rollable, array $modifiers): Rollable
    {
        foreach ($modifiers as $modifier) {
            $rollable = $this->addDecorator($rollable, $modifier);
        }

        return $rollable;
    }

    /**
     * Decorates the Rollable object with modifiers objects.
     *
     * @throws TooManyObjects
     * @throws IllegalValue
     * @throws UnknownAlgorithm
     */
    private function addDecorator(Rollable $rollable, array $matches): Rollable
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
}
