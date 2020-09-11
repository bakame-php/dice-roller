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

use PHPUnit\Framework\TestCase;

final class TossTest extends TestCase
{
    public function testItCanBeInstantiated(): void
    {
        $roll = new Toss(42, '22 + 20');
        $arrExpected = ['value' => 42, 'operation' => '22 + 20'];
        self::assertSame('22 + 20', $roll->operation());
        self::assertSame(42, $roll->value());
        self::assertSame($arrExpected, $roll->info());
        self::assertSame('42', $roll->asString());
        self::assertSame('42', json_encode($roll));
        self::assertNull($roll->context());
    }
}
