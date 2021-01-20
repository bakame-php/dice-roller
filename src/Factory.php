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

use function array_reduce;
use function count;
use function explode;
use function iterator_to_array;
use function preg_match;
use function strtolower;
use function strtoupper;
use function substr;

final class Factory
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

    public function __construct(private RandomIntGenerator $randomIntGenerator)
    {
    }

    public static function fromSystem(): self
    {
        return new self(new SystemRandomInt());
    }

    /**
     * @throws SyntaxError if the object can not be created due to some syntax error
     */
    public function newInstance(string $notation, Tracer $tracer = null): Rollable
    {
        $rollable = $this->create($this->parse($notation));
        if (null === $tracer) {
            return $rollable;
        }

        if ($rollable instanceof EnablesDeepTracing) {
            $rollable->setTracerRecursively($tracer);
        } elseif ($rollable instanceof SupportsTracing) {
            $rollable->setTracer($tracer);
        }

        return $rollable;
    }

    private function parse(string $notation): array
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
            if (!str_contains(strtolower($value), 'd')) {
                $res[$previous_offset] .= '+'.$value;
                continue;
            }

            if (str_contains($value, ')') && str_contains($res[$previous_offset], '(')) {
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
     *
     * @return array<array{definition:array, modifiers:array}>
     */
    private function parsePool(array $retval, string $notation): array
    {
        if ('' === $notation) {
            return $retval;
        }

        if (1 !== preg_match(self::POOL_PATTERN, $notation, $poolDefinition)) {
            throw SyntaxError::dueToInvalidNotation($notation);
        }

        if (1 !== preg_match(self::MODIFIER_PATTERN, $poolDefinition['modifier'], $modifierDefinition)) {
            throw SyntaxError::dueToInvalidModifier($poolDefinition['modifier']);
        }

        $retval[] = [
            'definition' => $this->getPoolDefinition($poolDefinition),
            'modifiers' => $this->getPoolModifiersDefinition($modifierDefinition),
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
        if ('!' !== $algo[0]) {
            return ['modifier' => 'dropkeep', 'operator' => $operator, 'value' => $value];
        }

        $operator = substr($operator, 1);
        if ('' !== $operator) {
            return ['modifier' => 'explode', 'operator' => $operator, 'value' => $value];
        }

        return ['modifier' => 'explode', 'operator' => '=', 'value' => $value];
    }

    /**
     * Returns a new object that can be rolled from a parsed dice notation.
     */
    private function create(array $parsed): Rollable
    {
        $rollable = new Cup();
        foreach ($parsed as $parts) {
            $rollable = $this->addRollable($rollable, $parts);
        }

        return $this->flattenRollable($rollable);
    }

    /**
     * Adds a Rollable item to a pool.
     */
    private function addRollable(Cup $pool, array $parts): Cup
    {
        $rollable = $this->createRollable($parts['definition']);
        foreach ($parts['modifiers'] as $matches) {
            $rollable = $this->decorate($rollable, $matches);
        }

        return $pool->withAddedRollable($this->flattenRollable($rollable));
    }

    /**
     * Generates the Pool from the dice notation matched pattern.
     *
     * @throws SyntaxError
     */
    private function createRollable(array $parts): Rollable
    {
        if (isset($parts['composite'])) {
            return $this->create($parts['composite']);
        }

        return Cup::of(
            (int) $parts['simple']['quantity'],
            $this->createDice($parts['simple']['type'])
        );
    }

    /**
     * Parse Rollable definition.
     *
     * @throws SyntaxError
     */
    private function createDice(string $notation): Dice
    {
        if ('DF' === $notation) {
            return new FudgeDie($this->randomIntGenerator);
        }

        if ('D%' === $notation) {
            return new PercentileDie($this->randomIntGenerator);
        }

        if (str_contains($notation, '[')) {
            return CustomDie::fromNotation($notation, $this->randomIntGenerator);
        }

        return SidedDie::fromNotation($notation, $this->randomIntGenerator);
    }

    /**
     * Decorates the Rollable object with modifiers objects.
     *
     * @throws SyntaxError
     */
    private function decorate(Rollable $rollable, array $matches): Rollable
    {
        if ('arithmetic' === $matches['modifier']) {
            return Arithmetic::fromOperation($rollable, $matches['operator'], $matches['value']);
        }

        if ('dropkeep' === $matches['modifier']) {
            return DropKeep::fromAlgorithm($rollable, $matches['operator'], $matches['value']);
        }

        return Explode::fromAlgorithm($rollable, $matches['operator'], $matches['value']);
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

        return iterator_to_array($rollable, false)[0];
    }
}
