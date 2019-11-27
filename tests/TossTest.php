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

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\Toss;
use PHPUnit\Framework\TestCase;

class TossTest extends TestCase
{
    public function testItCanBeInstantiated(): void
    {
        $roll = new Toss('foo', 'bar', 42);
        $arrExpected = ['expression' => 'foo', 'operation' => 'bar', 'value' => 42];
        self::assertSame('foo', $roll->expression());
        self::assertSame('bar', $roll->operation());
        self::assertSame(42, $roll->value());
        self::assertSame($arrExpected, $roll->toArray());
        self::assertSame('42', $roll->toString());
        self::assertSame('42', json_encode($roll));
    }
}
