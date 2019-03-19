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

use Bakame\DiceRoller\Decorator\Arithmetic;
use Bakame\DiceRoller\Decorator\DropKeep;
use Bakame\DiceRoller\Decorator\Explode;
use Bakame\DiceRoller\Exception\UnknownAlgorithm;
use Bakame\DiceRoller\Exception\UnknownExpression;
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
     * @var ExpressionParser
     */
    private $parser;

    /**
     * Factory constructor.
     *
     * @param ?Profiler $profiler
     */
    public function __construct(ExpressionParser $parser, ?Profiler $profiler = null)
    {
        $this->parser = $parser;
        $this->setProfiler($profiler);
    }

    /**
     * Returns a new Cup Instance from a string pattern.
     */
    public function newInstance(string $expression): Rollable
    {
        $parts = $this->parser->extractPool($expression);
        $dices = array_map([$this, 'parsePool'], $parts);

        if (1 === count($dices)) {
            return array_shift($dices);
        }

        $rollable = new Cup(...$dices);
        $rollable->setProfiler($this->profiler);

        return $rollable;
    }

    /**
     * Returns a collection of equals dice.
     *
     * @throws UnknownExpression
     * @throws UnknownAlgorithm
     */
    private function parsePool(string $expression): Rollable
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
     */
    private function createSimplePool(array $matches): Pool
    {
        $quantity = (int) $matches['quantity'];
        $definition = $matches['size'];
        $definition = strtolower($definition);

        return Cup::createFromRollable($quantity, $this->createDiceFromString($definition), $this->profiler);
    }

    /**
     * Parse Rollable definition.
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
     */
    private function createComplexPool(array $matches): Pool
    {
        $dices = [];
        foreach ($this->parser->extractPool($matches['mixed']) as $part) {
            $dices[] = $this->parsePool($part);
        }

        $pool = new Cup(...$dices);
        $pool->setProfiler($this->profiler);

        return $pool;
    }

    private function decorate(Pool $rollable, array $modifiers): Rollable
    {
        foreach ($modifiers as $modifier) {
            $rollable = $this->addDecorator($rollable, $modifier);
        }

        return $rollable;
    }

    /**
     * Decorates the Rollable object with the DropKeep or the Explode Modifier.
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
     */
    private function addDropKeep(Rollable $rollable, string $algo, array $matches): Rollable
    {
        $rollable = new DropKeep($rollable, $algo, (int) $matches['threshold']);
        $rollable->setProfiler($this->profiler);

        return $rollable;
    }

    /**
     * Decorates the Rollable object with the ExplodeModifier modifier.
     */
    private function addExplode(Rollable $rollable, string $compare, array $matches): Rollable
    {
        if ('' == $compare) {
            $compare = Explode::EQUALS;
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
     */
    private function addArithmetic(Rollable $rollable, array $matches): Rollable
    {
        $rollable = new Arithmetic($rollable, $matches['operator'], (int) $matches['value']);
        $rollable->setProfiler($this->profiler);

        return $rollable;
    }
}
