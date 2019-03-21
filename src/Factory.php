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
use function array_shift;
use function count;
use function explode;
use function iterator_to_array;
use function strpos;
use const FILTER_REQUIRE_ARRAY;
use const FILTER_VALIDATE_INT;

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
        $poolsArray = $this->parser->extractPool($expression);
        $pools = array_map([$this, 'createPoolFromString'], $poolsArray);

        if (1 === count($pools)) {
            return array_shift($pools);
        }

        $rollable = new Cup(...$pools);
        $rollable->setProfiler($this->profiler);

        return $rollable;
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
     * @throws TooManyObjects
     * @throws UnknownAlgorithm
     * @throws UnknownExpression
     */
    private function createPool(array $matches): Pool
    {
        if (isset($matches['mixed'])) {
            return $this->createComplexPool($matches);
        }

        return $this->createSimplePool($matches);
    }

    /**
     * Creates a simple Uniformed Pool.
     *
     * @throws IllegalValue
     * @throws TooFewSides
     */
    private function createSimplePool(array $matches): Pool
    {
        $quantity = (int) $matches['quantity'];
        $definition = $matches['size'];
        $definition = strtolower($definition);

        return Cup::createFromRollable($this->createDiceFromString($definition), $quantity, $this->profiler);
    }

    /**
     * Parse Rollable definition.
     *
     * @throws TooFewSides
     */
    private function createDiceFromString(string $definition): Rollable
    {
        if (false !== ($size = filter_var($definition, FILTER_VALIDATE_INT))) {
            return new SidedDie($size);
        }

        $definition = strtolower($definition);
        if ('f' === $definition) {
            return new FudgeDie();
        }

        if ('%' === $definition) {
            return new PercentileDie();
        }

        $sides = explode(',', substr($definition, 1, -1));
        $sides = (array) filter_var($sides, FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

        return new CustomDie(...$sides);
    }

    /**
     * Creates a complex mixed Pool.
     *
     * @throws IllegalValue
     * @throws TooFewSides
     * @throws TooManyObjects
     * @throws UnknownAlgorithm
     * @throws UnknownExpression
     */
    private function createComplexPool(array $matches): Pool
    {
        $dices = [];
        foreach ($this->parser->extractPool($matches['mixed']) as $part) {
            $dices[] = $this->createPoolFromString($part);
        }

        $pool = new Cup(...$dices);
        $pool->setProfiler($this->profiler);

        return $pool;
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
     * Decorates the Rollable object with the DropKeep or the Explode Modifier.
     *
     * @throws TooManyObjects
     * @throws IllegalValue
     * @throws UnknownAlgorithm
     */
    private function addDecorator(Rollable $rollable, array $matches): Rollable
    {
        if ('arithmetic' === $matches['type']) {
            return $this->addArithmetic($rollable, $matches);
        }

        $type = strtolower($matches['type']);
        if (0 !== strpos($type, '!')) {
            return $this->addDropKeep($rollable, $type, $matches);
        }

        return $this->addExplode($rollable, substr($type, 1), $matches);
    }

    /**
     * Decorates the Rollable object with the SortModifer modifier.
     *
     * @throws TooManyObjects
     * @throws UnknownAlgorithm
     */
    private function addDropKeep(Rollable $rollable, string $algo, array $matches): Rollable
    {
        $rollable = new DropKeep($rollable, $algo, (int) $matches['threshold']);
        $rollable->setProfiler($this->profiler);

        return $rollable;
    }

    /**
     * Decorates the Rollable object with the ExplodeModifier modifier.
     *
     * @throws IllegalValue
     * @throws UnknownAlgorithm
     */
    private function addExplode(Rollable $rollable, string $compare, array $matches): Rollable
    {
        if ('' == $compare) {
            $compare = Explode::EQ;
            $threshold = isset($matches['threshold']) ? (int) $matches['threshold'] : null;

            $rollable = new Explode($rollable, $compare, $threshold);
            $rollable->setProfiler($this->profiler);

            return $rollable;
        }

        $rollable = new Explode($rollable, $compare, (int) $matches['threshold']);
        $rollable->setProfiler($this->profiler);

        return $rollable;
    }

    /**
     * Decorates the Rollable object with up to 2 ArithmeticModifier.
     *
     * @throws IllegalValue
     * @throws UnknownAlgorithm
     */
    private function addArithmetic(Rollable $rollable, array $matches): Rollable
    {
        $rollable = new Arithmetic($rollable, $matches['operator'], (int) $matches['value']);
        $rollable->setProfiler($this->profiler);

        return $rollable;
    }
}
