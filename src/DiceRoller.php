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

    /**
     * Returns a new Cup Instance from a string pattern.
     */
    public static function parse(string $expression): Rollable
    {
        $parts = self::explode($expression);
        if (1 !== count($parts)) {
            return new Cup(...array_map([DiceRoller::class, 'parsePool'], $parts));
        }

        $rollable = self::parsePool(array_shift($parts));
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
     * @param string $str dice configuration string
     *
     * @throws Exception if the configuration string is not supported
     */
    private static function parsePool(string $str): Rollable
    {
        if ('' === $str) {
            return new Cup();
        }

        if (1 !== preg_match(self::POOL_PATTERN, $str, $matches)) {
            throw new UnknownExpression(sprintf('the submitted dice format `%s` is invalid or not supported', $str));
        }

        $pool = self::getPool($matches);
        if (1 === preg_match(self::MODIFIER_PATTERN, $matches['modifier'], $modifier_matches)) {
            return self::addArithmetic($modifier_matches, self::addComplexModifier($modifier_matches, $pool));
        }

        throw new UnknownAlgorithm(sprintf('the submitted modifier `%s` is invalid or not supported', $matches['modifier']));
    }

    /**
     * Generates the Cup from the expression matched pattern.
     */
    private static function getPool(array $matches): Cup
    {
        if ('' !== $matches['complex']) {
            return self::createComplexPool($matches);
        }

        return self::createSimplePool($matches);
    }

    /**
     * Creates a simple Uniformed Pool.
     */
    private static function createSimplePool(array $matches): Cup
    {
        $quantity = (int) ($matches['quantity'] ?? 1);
        if (0 === $quantity) {
            $quantity = 1;
        }

        $definition = $matches['size'] ?? '6';
        $definition = strtolower($definition);
        if ('' === $definition) {
            $definition = '6';
        }

        return Cup::createFromRollable($quantity, self::parseDefinition($definition));
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
     */
    private static function createComplexPool(array $matches): Cup
    {
        return new Cup(...array_map([DiceRoller::class, 'parsePool'], self::explode($matches['mixed'])));
    }

    /**
     * Decorates the Rollable object with up to 2 ArithmeticModifier.
     */
    private static function addArithmetic(array $matches, Rollable $rollable): Rollable
    {
        if (!isset($matches['math1'])) {
            return $rollable;
        }

        $rollable = new Arithmetic($rollable, $matches['operator1'], (int) $matches['value1']);
        if (!isset($matches['math2'])) {
            return $rollable;
        }

        return new Arithmetic($rollable, $matches['operator2'], (int) $matches['value2']);
    }

    /**
     * Decorates the Rollable object with the DropKeep or the Explode Modifier.
     */
    private static function addComplexModifier(array $matches, Cup $rollable): Rollable
    {
        if ('' === $matches['algo']) {
            return $rollable;
        }

        $type = strtolower($matches['type']);
        if (0 !== strpos($type, '!')) {
            return self::addDropKeep($type, $matches, $rollable);
        }

        return self::addExplode(substr($type, 1), $matches, $rollable);
    }

    /**
     * Decorates the Rollable object with the SortModifer modifier.
     */
    private static function addDropKeep(string $algo, array $matches, Cup $rollable): Rollable
    {
        $threshold = $matches['threshold'] ?? 1;

        return new DropKeep($rollable, $algo, (int) $threshold);
    }

    /**
     * Decorates the Rollable object with the ExplodeModifier modifier.
     */
    private static function addExplode(string $compare, array $matches, Cup $rollable): Rollable
    {
        if ('' == $compare) {
            $compare = Explode::EQUALS;
            $threshold = isset($matches['threshold']) ? (int) $matches['threshold'] : null;

            return new Explode($rollable, $compare, $threshold);
        }

        if (isset($matches['threshold'])) {
            return new Explode($rollable, $compare, (int) $matches['threshold']);
        }

        throw new UnknownAlgorithm(sprintf('the submitted exploding modifier `%s` is invalid or not supported', $matches['algo']));
    }
}
