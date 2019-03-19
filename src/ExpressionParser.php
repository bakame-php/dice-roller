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
use function count;
use function explode;
use function preg_match;
use function sprintf;
use function stripos;
use function strpos;

final class ExpressionParser implements Parser
{
    private const SIDE_COUNT = '6';

    private const DICE_COUNT = 1;

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
     * {@inheritdoc}
     */
    public function extractPool(string $expression): array
    {
        $parts = explode('+', $expression);
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

            if (false !== strpos($value, ')') && false !== strpos($res[$previous_offset], '(')) {
                $res[$previous_offset] .= '+'.$value;
                continue;
            }

            $res[] = $value;
        }

        return $res;
    }

    /**
     * {@inheritdoc}
     */
    public function parsePool(string $expression): array
    {
        if ('' === $expression) {
            return [];
        }

        if (1 !== preg_match(self::POOL_PATTERN, $expression, $matches)) {
            throw new UnknownExpression(sprintf('the submitted expression `%s` is invalid or not supported', $expression));
        }

        if (1 !== preg_match(self::MODIFIER_PATTERN, $matches['modifier'], $modifier_matches)) {
            throw new UnknownAlgorithm(sprintf('the submitted modifier `%s` is invalid or not supported', $matches['modifier']));
        }

        $pool = ['mixed' => $matches['mixed'] ?? ''];
        if ('' === $pool['mixed']) {
            $pool['quantity'] = ('' === $matches['quantity']) ? self::DICE_COUNT : $matches['quantity'];
            $pool['size'] = ('' === $matches['size']) ? self::SIDE_COUNT : $matches['size'];
            unset($pool['mixed']);
        }

        $modifiers = [];
        if ('' !== $modifier_matches['algo']) {
            $modifiers[] = [
                'type' => $modifier_matches['type'],
                'threshold' => $modifier_matches['threshold'] ?? 1,
            ];
        }

        if (isset($modifier_matches['math1'])) {
            $modifiers[] = [
                'type' => 'arithmetic',
                'operator' => $modifier_matches['operator1'],
                'value' => $modifier_matches['value1'],
            ];
        }

        if (isset($modifier_matches['math2'])) {
            $modifiers[] = [
                'type' => 'arithmetic',
                'operator' => $modifier_matches['operator2'],
                'value' => $modifier_matches['value2'],
            ];
        }

        return ['pool' => $pool, 'modifiers' => $modifiers];
    }
}
