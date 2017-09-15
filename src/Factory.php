<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller;

use InvalidArgumentException;

final class Factory
{
    const ARITHMETIC_MODIFIER_PATTERN = ',^(?<operator>\+|-|/|\*|^)(?<value>\d+)$,';
    const SORT_MODIFIER_PATTERN = ',^(?<algo>kh|kl|dl|dh)(?<value>\d+)$,i';
    const EXPLODE_MODIFIER_PATTERN = ',^\!(?<compare>>|<)?(?<value>\d+)?$,i';
    const DICE_PATTERN = ',^(?<quantity>\d*)d(?<size>\d+)?(?<modifier>.*)?$,i';
    const FUDGE_DICE_PATTERN = ',^(?<quantity>\d*)dF(?<modifier>.*)?$,i';

    /**
     * Returns a new Cup Instance from a string pattern
     *
     * @param string $pAsked
     *
     * @return Rollable
     */
    public function newInstance(string $pAsked = ''): Rollable
    {
        if ('' == $pAsked) {
            return new Cup();
        }

        $parts = $this->explode($pAsked);
        if (1 == count($parts)) {
            return $this->parseGroup($parts[0]);
        }

        return new Cup(...array_map([$this, 'parseGroup'], $parts));
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
            if (is_numeric($value) && $offset > 0) {
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
     * @throws InvalidArgumentException if the configuration string is not supported
     *
     * @return Rollable
     */
    private function parseGroup(string $pStr): Rollable
    {
        if (preg_match(self::FUDGE_DICE_PATTERN, $pStr, $matches)) {
            return $this->createFudgeDicePool($matches);
        }

        if (preg_match(self::DICE_PATTERN, $pStr, $matches)) {
            return $this->createDicePool($matches);
        }

        throw new InvalidArgumentException(sprintf('the following dice format `%s` is invalid or not supported', $pStr));
    }

    /**
     * Returns a Cup made of identical Dices
     *
     * @param array $pMatches
     *
     * @return Rollable
     */
    private function createDicePool(array $pMatches): Rollable
    {
        $quantity = (int) ($pMatches['quantity'] ?? 1);
        if (0 == $quantity) {
            $quantity = 1;
        }

        $size = (int) ($pMatches['size'] ?? 6);
        if (0 == $size) {
            $size = 6;
        }

        $modifier = $pMatches['modifier'] ?? '';

        return $this->addModifier($modifier, Cup::createFromDice($quantity, $size));
    }

    /**
     * Returns a Cup made of identical Dices
     *
     * @param array $pMatches
     *
     * @return Rollable
     */
    private function createFudgeDicePool(array $pMatches): Rollable
    {
        $quantity = (int) ($pMatches['quantity'] ?? 1);
        if (0 == $quantity) {
            $quantity = 1;
        }

        $modifier = $pMatches['modifier'] ?? '';

        return $this->addModifier($modifier, Cup::createFromFudgeDice($quantity));
    }

    /**
     * Add the correct modifier
     *
     * @param string $pModifier
     * @param Cup    $pRollable
     *
     * @throws InvalidArgumentException If the modifier string is unknown or not supported
     *
     * @return Rollable
     */
    private function addModifier(string $pModifier, Cup $pRollable): Rollable
    {
        if ('' == $pModifier) {
            return $pRollable;
        }

        if (preg_match(self::ARITHMETIC_MODIFIER_PATTERN, $pModifier, $matches)) {
            return new ArithmeticModifier($pRollable, (int) $matches['value'], $matches['operator']);
        }

        if (preg_match(self::SORT_MODIFIER_PATTERN, $pModifier, $matches)) {
            $matches['algo'] = strtolower($matches['algo']);

            return new SortModifier($pRollable, (int) $matches['value'], $matches['algo']);
        }

        if (preg_match(self::EXPLODE_MODIFIER_PATTERN, $pModifier, $matches)) {
            $matches['compare'] = $matches['compare'] ?? ExplodeModifier::EQUALS;
            $matches['value'] = $matches['value'] ?? '-1';

            return new ExplodeModifier($pRollable, (int) $matches['value'], $matches['compare']);
        }

        throw new InvalidArgumentException(sprintf('the following modifier `%s` is invalid or not supported', $pModifier));
    }
}