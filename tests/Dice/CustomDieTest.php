<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Test\Dice;

use Bakame\DiceRoller\Contract\CanNotBeRolled;
use Bakame\DiceRoller\Dice\CustomDie;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Bakame\DiceRoller\Dice\CustomDie
 */
final class CustomDieTest extends TestCase
{
    public function testDice(): void
    {
        $dice = new CustomDie(1, 2, 2, 4, 4);
        self::assertSame(5, $dice->size());
        self::assertSame(4, $dice->maximum());
        self::assertSame(1, $dice->minimum());
        self::assertSame('D[1,2,2,4,4]', $dice->notation());
        self::assertSame(json_encode('D[1,2,2,4,4]'), json_encode($dice));
        self::assertEquals($dice, CustomDie::fromNotation($dice->notation()));
        for ($i = 0; $i < 10; $i++) {
            $test = $dice->roll()->value();
            self::assertGreaterThanOrEqual($dice->minimum(), $test);
            self::assertLessThanOrEqual($dice->maximum(), $test);
        }
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorWithWrongValue(): void
    {
        self::expectException(CanNotBeRolled::class);
        new CustomDie(1);
    }

    /**
     * @dataProvider invalidExpression
     * @covers ::fromNotation
     */
    public function testfromStringWithWrongValue(string $expression): void
    {
        self::expectException(CanNotBeRolled::class);
        CustomDie::fromNotation($expression);
    }

    public function invalidExpression(): iterable
    {
        return [
            'invalid format' => ['1'],
            'contains non numeric' => ['d[1,0,foobar]'],
            'contains empty side' => ['d[1,,1]'],
        ];
    }
}
