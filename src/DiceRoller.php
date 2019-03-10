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

use Bakame\DiceRoller\Exception\UnknownAlgorithm;
use Bakame\DiceRoller\Exception\UnknownExpression;

final class DiceRoller
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
     * Returns a new Cup Instance from a string pattern.
     * @param ?Profiler $profiler
     */
    public static function parse(string $expression, ?Profiler $profiler = null): Rollable
    {
        $parts = self::explode($expression);
        if (1 !== count($parts)) {
            $dices = [];
            foreach ($parts as $part) {
                $dices[] = self::parsePool($part, $profiler);
            }

            $cup = new Cup(...$dices);
            $cup->setProfiler($profiler);

            return $cup;
        }

        $rollable = self::parsePool(array_shift($parts), $profiler);
        if (!$rollable instanceof Cup || 1 !== count($rollable)) {
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
    private static function explode(string $str): array
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
     * @param string    $str      dice configuration string
     * @param ?Profiler $profiler
     *
     * @throws Exception if the configuration string is not supported
     */
    private static function parsePool(string $str, ?Profiler $profiler): Rollable
    {
        if ('' === $str) {
            $cup = new Cup();
            $cup->setProfiler($profiler);

            return $cup;
        }

        if (1 !== preg_match(self::POOL_PATTERN, $str, $matches)) {
            throw new UnknownExpression(sprintf('the submitted dice format `%s` is invalid or not supported', $str));
        }

        $pool = self::getPool($matches, $profiler);

        if (1 === preg_match(self::MODIFIER_PATTERN, $matches['modifier'], $modifier_matches)) {
            return self::addArithmetic(
                $modifier_matches,
                self::addComplexModifier($modifier_matches, $pool, $profiler),
                $profiler
            );
        }

        throw new UnknownAlgorithm(sprintf('the submitted modifier `%s` is invalid or not supported', $matches['modifier']));
    }

    /**
     * Generates the Cup from the expression matched pattern.
     * @param ?Profiler $profiler
     */
    private static function getPool(array $matches, ?Profiler $profiler): Cup
    {
        if ('' !== $matches['complex']) {
            return self::createComplexPool($matches, $profiler);
        }

        return self::createSimplePool($matches, $profiler);
    }

    /**
     * Creates a simple Uniformed Pool.
     * @param ?Profiler $profiler
     */
    private static function createSimplePool(array $matches, ?Profiler $profiler): Cup
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

        return Cup::createFromRollable($quantity, self::parseDefinition($definition), $profiler);
    }

    /**
     * Parse Rollable definition.
     *
     * @throws Exception If the defintion is not parsable
     */
    private static function parseDefinition(string $definition): Rollable
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
        $sides = filter_var($sides, FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

        return new CustomDice(...$sides);
    }

    /**
     * Creates a complex mixed Pool.
     * @param ?Profiler $profiler
     */
    private static function createComplexPool(array $matches, ?Profiler $profiler): Cup
    {
        $dices = [];
        foreach (self::explode($matches['mixed']) as $part) {
            $dices[] = self::parsePool($part, $profiler);
        }

        $cup = new Cup(...$dices);
        $cup->setProfiler($profiler);

        return $cup;
    }

    /**
     * Decorates the Rollable object with up to 2 ArithmeticModifier.
     * @param ?Profiler $profiler
     */
    private static function addArithmetic(array $matches, Rollable $rollable, ?Profiler $profiler): Rollable
    {
        if (!isset($matches['math1'])) {
            return $rollable;
        }

        $rollable = new Arithmetic($rollable, $matches['operator1'], (int) $matches['value1'], $profiler);
        if (!isset($matches['math2'])) {
            return $rollable;
        }

        return new Arithmetic($rollable, $matches['operator2'], (int) $matches['value2'], $profiler);
    }

    /**
     * Decorates the Rollable object with the DropKeep or the Explode Modifier.
     * @param ?Profiler $profiler
     */
    private static function addComplexModifier(array $matches, Cup $rollable, ?Profiler $profiler): Rollable
    {
        if ('' === $matches['algo']) {
            return $rollable;
        }

        $type = strtolower($matches['type']);
        if (0 !== strpos($type, '!')) {
            return self::addDropKeep($type, $matches, $rollable, $profiler);
        }

        return self::addExplode(substr($type, 1), $matches, $rollable, $profiler);
    }

    /**
     * Decorates the Rollable object with the SortModifer modifier.
     * @param ?Profiler $profiler
     */
    private static function addDropKeep(string $algo, array $matches, Cup $rollable, ?Profiler $profiler): Rollable
    {
        $threshold = $matches['threshold'] ?? 1;

        return new DropKeep($rollable, $algo, (int) $threshold, $profiler);
    }

    /**
     * Decorates the Rollable object with the ExplodeModifier modifier.
     * @param ?Profiler $profiler
     */
    private static function addExplode(string $compare, array $matches, Cup $rollable, ?Profiler $profiler): Rollable
    {
        if ('' == $compare) {
            $compare = Explode::EQUALS;
            $threshold = isset($matches['threshold']) ? (int) $matches['threshold'] : null;

            return new Explode($rollable, $compare, $threshold, $profiler);
        }

        if (isset($matches['threshold'])) {
            return new Explode($rollable, $compare, (int) $matches['threshold'], $profiler);
        }

        throw new UnknownAlgorithm(sprintf('the submitted exploding modifier `%s` is invalid or not supported', $matches['algo']));
    }
}
