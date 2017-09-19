<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller;

final class Factory
{
    const POOL_PATTERN = ',^(?<quantity>\d*)d(?<size>\d+|F)?(?<modifier>.*)?$,i';
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

        return new Cup(...array_map([$this, 'parsePool'], $parts));
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
            if (false === stripos($value, 'd') && $offset > 0) {
                $res[count($res) - 1] .= '+'.$value;
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

        if (preg_match(self::POOL_PATTERN, $pStr, $matches)) {
            return $this->createPool($matches);
        }

        throw new Exception(sprintf('the submitted dice format `%s` is invalid or not supported', $pStr));
    }

    /**
     * Returns a Cup made of identical Dices
     *
     * @param array $pMatches
     *
     * @return Rollable
     */
    private function createPool(array $pMatches): Rollable
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

        if (preg_match(self::MODIFIER_PATTERN, $pMatches['modifier'], $matches)) {
            return $this->addArithmeticModifier($matches, $this->addComplexModifier($matches, Cup::createFromDice($quantity, $size)));
        }

        throw new Exception(sprintf('the submitted modifier `%s` is invalid or not supported', $pMatches['modifier']));
    }

    /**
     * Decorate the Rollable object with The SortModifer Or the ExplodeModifier
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
            $threshold = $pMatches['threshold'] ?? 1;

            return new SortModifier($pRollable, (int) $threshold, $type);
        }

        $compare = substr($type, 1);
        if ('' == $compare) {
            $compare = ExplodeModifier::EQUALS;
        }

        $threshold = $pMatches['threshold'] ?? -1;

        return new ExplodeModifier($pRollable, (int) $threshold, $compare);
    }

    /**
     * Decorate the Rollable object with up to 2 ArithmeticModifier
     *
     * @param array    $pMatches
     * @param Rollable $pRollable
     *
     * @return Rollable
     */
    private function addArithmeticModifier(array $pMatches, Rollable $pRollable): Rollable
    {
        if (!isset($pMatches['math1'])) {
            return $pRollable;
        }

        $rollable = new ArithmeticModifier($pRollable, (int) $pMatches['value1'], $pMatches['operator1']);
        if (!isset($pMatches['math2'])) {
            return $rollable;
        }

        return new ArithmeticModifier($rollable, (int) $pMatches['value2'], $pMatches['operator2']);
    }
}