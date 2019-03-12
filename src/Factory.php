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
use Bakame\DiceRoller\Tracer\NullTracer;
use function array_shift;
use function count;
use function explode;
use function iterator_to_array;
use function preg_match;
use function sprintf;
use function stripos;
use function strpos;
use const FILTER_REQUIRE_ARRAY;
use const FILTER_VALIDATE_INT;

final class Factory
{
    private const POOL_PATTERN = ',^
        (?<dice>
            (?<simple>(?<quantity>\d*)d(?<size>\d+|f|\%|\[.*?\])?) # simple dice pattern
            |
            (?<complex>\((?<mixed>.+)\))                           # complex dice pattern
        )
        (?<modifier>.*)?                                           # modifier pattern
    $,xi';

    private const MODIFIER_PATTERN = ',^
        (?<algo>                             # modifier definition pattern
            (?<type>!|!>|!<|!=|dh|dl|kh|kl)? # modifier types - exploding and sorting
            (?<threshold>\d+)?               # modifier threshold
        )?
        (?<math1>                            # first arithmetic modifier pattern
            (?<operator1>\+|-|/|\*|\^)       # first arithmetic operator supported
            (?<value1>\d+)                   # first value to use to modify roll result
        )?
        (?<math2>                            # second arithmetic modifier pattern
            (?<operator2>\+|-|/|\*|\^)       # second arithmetic operator supported
            (?<value2>\d+)                   # second value to use to modify roll result
        )?
    $,xi';

    private const DEFAULT_SIZE_COUNT = '6';

    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * Factory constructor.
     *
     * @param ?Tracer $tracer
     */
    public function __construct(?Tracer $tracer = null)
    {
        $this->tracer = $tracer ?? new NullTracer();
    }

    /**
     * Returns a new Cup Instance from a string pattern.
     */
    public function newInstance(string $expression): Rollable
    {
        $parts = $this->explode($expression);
        if (1 !== count($parts)) {
            $dices = array_map([$this, 'parsePool'], $parts);

            return (new Cup($this->tracer))->withAddedRollable(...$dices);
        }

        $rollable = $this->parsePool(array_shift($parts));
        if (!$rollable instanceof Pool || 1 !== count($rollable)) {
            return $rollable;
        }

        $arr = iterator_to_array($rollable, false);

        return $arr[0];
    }

    /**
     * Explodes the given string into separate parts.
     *
     * @return string[]
     */
    private function explode(string $str): array
    {
        $parts = explode('+', $str);
        $res = [];
        foreach ($parts as $offset => $value) {
            if (0 == $offset) {
                $res[] = $value;
                continue;
            }

            $previous_offset = count($res) - 1;
            if (false === stripos($value, 'd')) {
                $res[$previous_offset] .= '+'.$value;
                continue;
            }

            if (false !== strpos($value, ')')
                && false !== strpos($res[$previous_offset], '(')) {
                $res[$previous_offset] .= '+'.$value;
                continue;
            }

            $res[] = $value;
        }

        return $res;
    }

    /**
     * Returns a collection of equals dice.
     *
     * @param string $str dice configuration string
     *
     * @throws UnknownExpression
     * @throws UnknownAlgorithm
     */
    private function parsePool(string $str): Rollable
    {
        if ('' === $str) {
            return new Cup($this->tracer);
        }

        if (1 !== preg_match(self::POOL_PATTERN, $str, $matches)) {
            throw new UnknownExpression(sprintf('the submitted dice format `%s` is invalid or not supported', $str));
        }

        $pool = $this->getPool($matches);

        if (1 === preg_match(self::MODIFIER_PATTERN, $matches['modifier'], $modifier_matches)) {
            return $this->addArithmetic($modifier_matches, $this->addComplexModifier($modifier_matches, $pool));
        }

        throw new UnknownAlgorithm(sprintf('the submitted modifier `%s` is invalid or not supported', $matches['modifier']));
    }

    /**
     * Generates the Cup from the expression matched pattern.
     */
    private function getPool(array $matches): Pool
    {
        if ('' !== $matches['complex']) {
            return $this->createComplexPool($matches);
        }

        return $this->createSimplePool($matches);
    }

    /**
     * Creates a simple Uniformed Pool.
     */
    private function createSimplePool(array $matches): Pool
    {
        $quantity = (int) ($matches['quantity'] ?? 1);
        if (0 === $quantity) {
            $quantity = 1;
        }

        $definition = $matches['size'] ?? self::DEFAULT_SIZE_COUNT;
        $definition = strtolower($definition);
        if ('' === $definition) {
            $definition = self::DEFAULT_SIZE_COUNT;
        }

        return Cup::createFromRollable($quantity, $this->parseDefinition($definition), $this->tracer);
    }

    /**
     * Parse Rollable definition.
     */
    private function parseDefinition(string $definition): Rollable
    {
        if (false !== ($size = filter_var($definition, FILTER_VALIDATE_INT))) {
            return new Dice($size);
        }

        $definition = strtolower($definition);
        if ('f' === $definition) {
            return new FudgeDice();
        }

        if ('%' === $definition) {
            return new PercentileDice();
        }

        $sides = explode(',', substr($definition, 1, -1));
        $sides = (array) filter_var($sides, FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

        return new CustomDice(...$sides);
    }

    /**
     * Creates a complex mixed Pool.
     */
    private function createComplexPool(array $matches): Pool
    {
        $dices = [];
        foreach ($this->explode($matches['mixed']) as $part) {
            $dices[] = $this->parsePool($part);
        }

        return (new Cup($this->tracer))->withAddedRollable(...$dices);
    }

    /**
     * Decorates the Rollable object with up to 2 ArithmeticModifier.
     */
    private function addArithmetic(array $matches, Rollable $rollable): Rollable
    {
        if (!isset($matches['math1'])) {
            return $rollable;
        }

        $rollable = new Arithmetic($rollable, $matches['operator1'], (int) $matches['value1'], $this->tracer);
        if (!isset($matches['math2'])) {
            return $rollable;
        }

        return new Arithmetic($rollable, $matches['operator2'], (int) $matches['value2'], $this->tracer);
    }

    /**
     * Decorates the Rollable object with the DropKeep or the Explode Modifier.
     */
    private function addComplexModifier(array $matches, Pool $rollable): Rollable
    {
        if ('' === $matches['algo']) {
            return $rollable;
        }

        $type = strtolower($matches['type']);
        if (0 !== strpos($type, '!')) {
            return $this->addDropKeep($type, $matches, $rollable);
        }

        return $this->addExplode(substr($type, 1), $matches, $rollable);
    }

    /**
     * Decorates the Rollable object with the SortModifer modifier.
     */
    private function addDropKeep(string $algo, array $matches, Pool $rollable): Rollable
    {
        $threshold = $matches['threshold'] ?? 1;

        return new Decorator\DropKeep($rollable, $algo, (int) $threshold, $this->tracer);
    }

    /**
     * Decorates the Rollable object with the ExplodeModifier modifier.
     */
    private function addExplode(string $compare, array $matches, Pool $rollable): Rollable
    {
        if ('' == $compare) {
            $compare = Decorator\Explode::EQUALS;
            $threshold = isset($matches['threshold']) ? (int) $matches['threshold'] : null;

            return new Decorator\Explode($rollable, $compare, $threshold, $this->tracer);
        }

        if (isset($matches['threshold'])) {
            return new Explode($rollable, $compare, (int) $matches['threshold'], $this->tracer);
        }

        throw new UnknownAlgorithm(sprintf('the submitted exploding modifier `%s` is invalid or not supported', $matches['algo']));
    }
}
