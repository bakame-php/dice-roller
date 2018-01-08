<?php

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\CustomDice;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\Exception;
use Bakame\DiceRoller\FudgeDice;
use Bakame\DiceRoller\PercentileDice;
use Bakame\DiceRoller\Rollable;
use PHPUnit\Framework\TestCase;
use TypeError;
use function Bakame\DiceRoller\create;

/**
 * @coversDefaultClass Bakame\DiceRoller\Cup
 */
final class CupTest extends TestCase
{
    public function testConstructorThrowsTypeError()
    {
        $this->expectException(TypeError::class);
        new Cup(new Dice(3), 'foo');
    }

    /**
     * @covers ::__construct
     * @covers ::withAddedRollable
     */
    public function testWithRollable()
    {
        $cup = new Cup(new FudgeDice());
        $altCup = $cup->withAddedRollable(new CustomDice(-1, 1, -1));
        $this->assertNotEquals($cup, $altCup);
    }

    /**
     * @covers ::__construct
     * @covers ::withAddedRollable
     */
    public function testWithRollableReturnsSameInstance()
    {
        $cup = new Cup(new FudgeDice());
        $altCup = $cup->withAddedRollable(new Cup());
        $this->assertSame($cup, $altCup);
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers \Bakame\DiceRoller\Result
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::count
     * @covers ::getIterator
     */
    public function testRoll()
    {
        $cup = new Cup(create('4D10'), create('2d4'));
        $this->assertSame(6, $cup->getMinimum());
        $this->assertSame(48, $cup->getMaximum());
        $this->assertCount(2, $cup);
        for ($i = 0; $i < 5; $i++) {
            $test = $cup->roll();
            $this->assertGreaterThanOrEqual($cup->getMinimum(), $test->getResult());
            $this->assertLessThanOrEqual($cup->getMaximum(), $test->getResult());
            $this->assertSame($test->getAnnotation(), (string) $cup);
        }
    }

    /**
     * @covers ::__construct
     * @covers ::createFromRollable
     * @dataProvider validNamedConstructor
     *
     * @param int      $quantity
     * @param Rollable $template
     */
    public function testCreateFromRollable(int $quantity, Rollable $template)
    {
        $cup = Cup::createFromRollable($quantity, $template);
        $this->assertCount($quantity, $cup);
        $this->assertContainsOnlyInstancesOf(get_class($template), $cup);
    }

    public function validNamedConstructor()
    {
        return [
            'basic dice' => [
                'quantity' => 2,
                'template' => new Dice(6),
            ],
            'fudge dice' => [
                'quantity' => 3,
                'template' => new FudgeDice(),
            ],
            'percentile dice' => [
                'quantity' => 4,
                'template' => new PercentileDice(),

            ],
            'custom dice' => [
                'quantity' => 5,
                'template' => new CustomDice(1, 2, 2, 3, 5),
            ],
        ];
    }

    public function testCreateFromRollableThrowsException()
    {
        $this->expectException(Exception::class);
        Cup::createFromRollable(0, new FudgeDice());
    }

    /**
     * @covers ::__construct
     * @covers ::createFromRollable
     * @covers ::withAddedRollable
     * @covers ::isValid
     */
    public function testCreateFromRollableReturnsEmptyCollection()
    {
        $cup = Cup::createFromRollable(12, new Cup());
        $alt_cup = $cup->withAddedRollable(new Cup());
        $this->assertCount(0, $cup);
        $this->assertSame($cup, $alt_cup);
    }
}
