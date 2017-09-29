<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller;

use Ethtezahl\DiceRoller\Modifier;

final class Factory
{
    const POOL_PATTERN = ',^
        (?<dice>
            (?<simple>(?<quantity>\d*)d(?<size>\d+|F)?) # simple dice pattern
            |
            (?<complex>\((?<mixed>.+)\))                # complex dice pattern
        )
        (?<modifier>.*)?                                # modifier pattern
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
     * Returns a new Cup Instance from a string pattern
     *
     * @param string $pAsked
     *
     * @return Rollable
     */
    public function newInstance(string $pAsked = ''): Rollable
    {
        $parts = $this->explode($pAsked);
        if (1 == count($parts)) {
            return $this->parsePool(array_shift($parts));
        }

        return new Cup(array_map([$this, 'parsePool'], $parts));
    }

    /**
     * Explode the given string into separate parts
     *
     * @param string $pStr
     *
     * @return string[]
     */
    private function explode(string $pStr): array
    {
        $parts = explode('+', $pStr);
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
     * Returns a collection of equals dice
     *
     * @param string $pStr dice configuration string
     *
     * @throws Exception if the configuration string is not supported
     *
     * @return Rollable
     */
    private function parsePool(string $pStr): Rollable
    {
        if ('' == $pStr) {
            return new Cup();
        }

        if (!preg_match(self::POOL_PATTERN, $pStr, $matches)) {
            throw new Exception(sprintf('the submitted dice format `%s` is invalid or not supported', $pStr));
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
     * Create a simple Uniformed Pool
     *
     * @param array $pMatches
     *
     * @return Cup
     */
    private function createSimplePool(array $pMatches): Cup
    {
        $quantity = (int) ($pMatches['quantity'] ?? 1);
        if (0 == $quantity) {
            $quantity = 1;
        }

        $size = $pMatches['size'] ?? '6';
        $size = strtolower($size);
        if ('' == $size) {
            $size = '6';
        }

        return Cup::createFromDice($quantity, $size);
    }

    /**
     * Create a complex mixed Pool
     *
     * @param array $pMatches
     *
     * @return Cup
     */
    private function createComplexPool(array $pMatches): Cup
    {
        return new Cup(array_map([$this, 'parsePool'], $this->explode($pMatches['mixed'])));
    }

    /**
     * Decorate the Rollable object with the DropKeep or the Explode Modifier
     *
     * @param array $pMatches
     * @param Cup   $pRollable
     *
     * @return Rollable
     */
    private function addComplexModifier(array $pMatches, Cup $pRollable): Rollable
    {
        if ('' == $pMatches['algo']) {
            return $pRollable;
        }

        $type = strtolower($pMatches['type']);
        if (0 !== strpos($type, '!')) {
            return $this->addDropKeep($type, $pMatches, $pRollable);
        }

        return $this->addExplode(substr($type, 1), $pMatches, $pRollable);
    }

    /**
     * Decorate the Rollable object with the SortModifer modifier
     *
     * @param string $algo
     * @param array  $pMatches
     *
     * @param Cup $pRollable
     */
    private function addDropKeep(string $algo, array $pMatches, Cup $pRollable): Rollable
    {
        $threshold = $pMatches['threshold'] ?? 1;

        return new Modifier\DropKeep($pRollable, $algo, (int) $threshold);
    }

    /**
     * Decorate the Rollable object with the ExplodeModifier modifier
     *
     * @param string $algo
     * @param array  $pMatches
     *
     * @param Cup $pRollable
     */
    private function addExplode(string $compare, array $pMatches, Cup $pRollable): Rollable
    {
        if ('' == $compare) {
            $compare = Modifier\Explode::EQUALS;
            $threshold = $pMatches['threshold'] ?? -1;

            return new Modifier\Explode($pRollable, $compare, (int) $threshold);
        }

        if (isset($pMatches['threshold'])) {
            return new Modifier\Explode($pRollable, $compare, (int) $pMatches['threshold']);

        }

        throw new Exception(sprintf('the submitted exploding modifier `%s` is invalid or not supported', $pMatches['algo']));
    }

    /**
     * Decorate the Rollable object with up to 2 ArithmeticModifier
     *
     * @param array    $pMatches
     * @param Rollable $pRollable
     *
     * @return Rollable
     */
    private function addArithmetic(array $pMatches, Rollable $pRollable): Rollable
    {
        if (!isset($pMatches['math1'])) {
            return $pRollable;
        }

        $rollable = new Modifier\Arithmetic($pRollable, $pMatches['operator1'], (int) $pMatches['value1']);
        if (!isset($pMatches['math2'])) {
            return $rollable;
        }

        return new Modifier\Arithmetic($rollable, $pMatches['operator2'], (int) $pMatches['value2']);
    }
}