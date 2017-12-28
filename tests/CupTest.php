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
     * @covers ::withRollable
     */
    public function testWithRollable()
    {
        $cup = new Cup(new FudgeDice());
        $altCup = $cup->withRollable(new CustomDice(-1, 1, -1));
        $this->assertNotEquals($cup, $altCup);
    }

    /**
     * @covers ::__construct
     * @covers ::withRollable
     */
    public function testWithRollableReturnsSameInstance()
    {
        $cup = new Cup(new FudgeDice());
        $altCup = $cup->withRollable(new Cup());
        $this->assertSame($cup, $altCup);
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::calculate
     * @covers ::roll
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
        $this->assertContainsOnlyInstancesOf(Rollable::class, $cup);
        for ($i = 0; $i < 5; $i++) {
            $test = $cup->roll();
            $this->assertGreaterThanOrEqual($cup->getMinimum(), $test);
            $this->assertLessThanOrEqual($cup->getMaximum(), $test);
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
    public function testCreateFromDiceDefinition(int $quantity, Rollable $template)
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
}
