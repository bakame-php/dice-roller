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
use Bakame\DiceRoller\Exception\SyntaxError;
use function array_reduce;
use function count;
use function explode;
use function preg_match;
use function stripos;
use function strpos;
use function strtoupper;
use function substr;

final class NotationParser implements Parser
{
    private const DEFAULT_SIMPLE_POOL = ['type' => '6', 'quantity' => '1'];

    private const POOL_PATTERN = ',^
        (?<dice>
            (?<simple>(?<quantity>\d*)d(?<type>\d+|f|\%|\[.*?\])?) # simple dice pattern
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

    public function parse(string $notation): array
    {
        return array_reduce($this->extractPool($notation), [$this, 'parsePool'], []);
    }

    /**
     * Extract pool notation from a generic dice notation.
     *
     * @return string[]
     */
    private function extractPool(string $notation): array
    {
        $parts = explode('+', $notation);
        $res = [];
        foreach ($parts as $offset => $value) {
            if (0 === $offset) {
                $res[] = $value;
                continue;
            }

            $previous_offset = count($res) - 1;
            if (false === stripos($value, 'd')) {
                $res[$previous_offset] .= '+'.$value;
                continue;
            }

            if (false !== strpos($value, ')') && false !== strpos($res[$previous_offset], '(')) {
                $res[$previous_offset] .= '+'.$value;
                continue;
            }

            $res[] = $value;
        }

        return $res;
    }

    /**
     * Returns an array representation of a Pool.
     *
     *  - If the string is the empty string a empty array is returned
     *  - Otherwise an array containing:
     *         - the pool definition
     *         - the pool modifiers
     *
     * @throws SyntaxError
     */
    private function parsePool(array $retval, string $notation): array
    {
        if ('' === $notation) {
            return $retval;
        }

        if (1 !== preg_match(self::POOL_PATTERN, $notation, $matches)) {
            throw SyntaxError::dueToInvalidNotation($notation);
        }

        if (1 !== preg_match(self::MODIFIER_PATTERN, $matches['modifier'], $modifier_matches)) {
            throw SyntaxError::dueToInvalidModifier($matches['modifier']);
        }

        $retval[] = [
            'definition' => $this->getPoolDefinition($matches),
            'modifiers' => $this->getPoolModifiersDefinition($modifier_matches),
        ];

        return $retval;
    }

    /**
     * Returns the pool definition as an array.
     */
    private function getPoolDefinition(array $matches): array
    {
        $notation = $matches['mixed'] ?? '';
        if ('' !== $notation) {
            return ['composite' => $this->parse($notation)];
        }

        $pool = self::DEFAULT_SIMPLE_POOL;
        if ('' !== $matches['type']) {
            $pool['type'] = $matches['type'];
        }

        if ('' !== $matches['quantity']) {
            $pool['quantity'] = $matches['quantity'];
        }

        $pool['type'] = strtoupper('D'.$pool['type']);

        return ['simple' => $pool];
    }

    /**
     * Returns the modifiers definition associated to a specific pool.
     */
    private function getPoolModifiersDefinition(array $matches): array
    {
        $modifiers = [];
        if ('' !== $matches['algo']) {
            $modifiers[] = $this->getAlgorithmDefinition($matches['algo'], $matches['type'], $matches['threshold'] ?? null);
        }

        if (isset($matches['math1'])) {
            $modifiers[] = [
                'modifier' => 'arithmetic',
                'operator' => $matches['operator1'],
                'value' => (int) $matches['value1'],
            ];
        }

        if (isset($matches['math2'])) {
            $modifiers[] = [
                'modifier' => 'arithmetic',
                'operator' => $matches['operator2'],
                'value' => (int) $matches['value2'],
            ];
        }

        return $modifiers;
    }

    /**
     * Returns the DropKeep or Explode definition.
     *
     * @param ?string $value
     */
    private function getAlgorithmDefinition(string $algo, string $operator, ?string $value): array
    {
        $operator = strtoupper($operator);
        $value = $value ?? 1;
        $value = (int) $value;
        if (0 !== strpos($algo, '!')) {
            return ['modifier' => 'dropkeep', 'operator' => $operator, 'value' => $value];
        }

        $operator = substr($operator, 1);
        if ('' !== $operator) {
            return ['modifier' => 'explode', 'operator' => $operator, 'value' => $value];
        }

        return ['modifier' => 'explode', 'operator' => '=', 'value' => $value];
    }
}
