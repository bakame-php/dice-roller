<?php
/**
* This file is part of the League.csv library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/bakame-php/dice-roller/
* @version 1.0.0
* @package bakame-php/dice-roller
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
declare(strict_types=1);

namespace Bakame\DiceRoller;

use Bakame\DiceRoller\Modifier\Arithmetic;
use Bakame\DiceRoller\Modifier\DropKeep;
use Bakame\DiceRoller\Modifier\Explode;

final class Parser
{
    const POOL_PATTERN = ',^
        (?<dice>
            (?<simple>(?<quantity>\d*)d(?<size>\d+|f|\%|\[.*?\])?) # simple dice pattern
            |
            (?<complex>\((?<mixed>.+)\))                           # complex dice pattern
        )
        (?<modifier>.*)?                                           # modifier pattern
    $,xi';

    const MODIFIER_PATTERN = ',^
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
     *
     * @param string $expression
     *
     * @return Rollable
     */
    public function parse(string $expression): Rollable
    {
        $parts = $this->explode($expression);
        if (1 == count($parts)) {
            return $this->parsePool(array_shift($parts));
        }

        return new Cup(...array_map([$this, 'parsePool'], $parts));
    }

    /**
     * Returns a new Cup Instance from a string pattern.
     *
     * @see Parser::parse
     *
     * @param string $expression
     *
     * @return Rollable
     */
    public function __invoke(string $expression): Rollable
    {
        return $this->parse($expression);
    }

    /**
     * Explodes the given string into separate parts.
     *
     * @param string $str
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
     * @throws Exception if the configuration string is not supported
     *
     * @return Rollable
     */
    private function parsePool(string $str): Rollable
    {
        if ('' === $str) {
            return new Cup();
        }

        if (!preg_match(self::POOL_PATTERN, $str, $matches)) {
            throw new Exception(sprintf('the submitted dice format `%s` is invalid or not supported', $str));
        }

        $method = 'createSimplePool';
        if ('' !== $matches['complex']) {
            $method = 'createComplexPool';
        }

        $pool = $this->$method($matches);
        if (preg_match(self::MODIFIER_PATTERN, $matches['modifier'], $modifier_matches)) {
            return $this->addArithmetic($modifier_matches, $this->addComplexModifier($modifier_matches, $pool));
        }

        throw new Exception(sprintf('the submitted modifier `%s` is invalid or not supported', $matches['modifier']));
    }

    /**
     * Creates a simple Uniformed Pool.
     *
     * @param array $matches
     *
     * @return Cup
     */
    private function createSimplePool(array $matches): Cup
    {
        $quantity = (int) ($matches['quantity'] ?? 1);
        if (0 == $quantity) {
            $quantity = 1;
        }

        $size = $matches['size'] ?? '6';
        $size = strtolower($size);
        if ('' === $size) {
            $size = '6';
        }

        return Cup::createFromDiceDefinition($quantity, $size);
    }

    /**
     * Creates a complex mixed Pool.
     *
     * @param array $matches
     *
     * @return Cup
     */
    private function createComplexPool(array $matches): Cup
    {
        return new Cup(...array_map([$this, 'parsePool'], $this->explode($matches['mixed'])));
    }

    /**
     * Decorates the Rollable object with up to 2 ArithmeticModifier.
     *
     * @param array    $matches
     * @param Rollable $rollable
     *
     * @return Rollable
     */
    private function addArithmetic(array $matches, Rollable $rollable): Rollable
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
     *
     * @param array $matches
     * @param Cup   $rollable
     *
     * @return Rollable
     */
    private function addComplexModifier(array $matches, Cup $rollable): Rollable
    {
        if ('' == $matches['algo']) {
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
     *
     * @param string $algo
     * @param array  $matches
     * @param Cup    $rollable
     */
    private function addDropKeep(string $algo, array $matches, Cup $rollable): Rollable
    {
        $threshold = $matches['threshold'] ?? 1;

        return new DropKeep($rollable, $algo, (int) $threshold);
    }

    /**
     * Decorates the Rollable object with the ExplodeModifier modifier.
     *
     * @param string $compare
     * @param array  $matches
     * @param Cup    $rollable
     */
    private function addExplode(string $compare, array $matches, Cup $rollable): Rollable
    {
        if ('' == $compare) {
            $compare = Explode::EQUALS;
            $threshold = isset($matches['threshold']) ? (int) $matches['threshold'] : null;

            return new Explode($rollable, $compare, $threshold);
        }

        if (isset($matches['threshold'])) {
            return new Explode($rollable, $compare, (int) $matches['threshold']);
        }

        throw new Exception(sprintf('the submitted exploding modifier `%s` is invalid or not supported', $matches['algo']));
    }
}
