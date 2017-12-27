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
     * @covers ::createFromDiceDefinition
     * @covers ::parseDefinition
     * @covers ::createFromRollable
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::minimum
     * @covers ::maximum
     * @dataProvider validNamedConstructor
     * @param mixed $quantity
     * @param mixed $sides
     * @param mixed $className
     * @param mixed $min
     * @param mixed $max
     */
    public function testCreateFromDiceDefinition($quantity, $sides, $className, $min, $max)
    {
        $cup = Cup::createFromDiceDefinition($quantity, $sides);
        $this->assertCount($quantity, $cup);
        $this->assertContainsOnlyInstancesOf($className, $cup);
        $this->assertSame($min, $cup->getMinimum());
        $this->assertSame($max, $cup->getMaximum());
    }

    public function validNamedConstructor()
    {
        return [
            'basic dice' => [
                'quantity' => 2,
                'sides' => 6,
                'className' => Dice::class,
                'min' => 2,
                'max' => 12,
            ],
            'fudge dice' => [
                'quantity' => 2,
                'sides' => 'F',
                'className' => FudgeDice::class,
                'min' => -2,
                'max' => 2,
            ],
            'fudge dice case insensitive' => [
                'quantity' => 2,
                'sides' => 'f',
                'className' => FudgeDice::class,
                'min' => -2,
                'max' => 2,
            ],
            'multiple basic dice' => [
                'quantity' => 4,
                'sides' => 3,
                'className' => Dice::class,
                'min' => 4,
                'max' => 12,
            ],
            'multipe fudge dice' => [
                'quantity' => 4,
                'sides' => 'f',
                'className' => FudgeDice::class,
                'min' => -4,
                'max' => 4,
            ],
            'percentile dice' => [
                'quantity' => 2,
                'sides' => '%',
                'className' => PercentileDice::class,
                'min' => 2,
                'max' => 200,
            ],
            'custom dice' => [
                'quantity' => 2,
                'sides' => '[1,2,2,3,5]',
                'className' => CustomDice::class,
                'min' => 2,
                'max' => 10,
            ],
        ];
    }

    /**
     * @covers ::createFromDiceDefinition
     * @covers ::createFromRollable
     * @covers ::parseDefinition
     * @dataProvider invalidNamedConstructor
     * @param mixed $quantity
     * @param mixed $definition
     */
    public function testCreateFromDiceDefinitionThrowsException($quantity, $definition)
    {
        $this->expectException(Exception::class);
        Cup::createFromDiceDefinition($quantity, $definition);
    }

    public function invalidNamedConstructor()
    {
        return [
            'invalid quantity' => [
                'quantity' => -1,
                'definition' => '3',
            ],
            'invalid sides' => [
                'quantity' => 2,
                'definition' => '1',
            ],
            'invalid sides with wrong string' => [
                'quantity' => 3,
                'definition' => 'foobar',
            ],
            'invalid fudge definition' => [
                'quantity' => 3,
                'definition' => 'ff',
            ],
            'invalid percentile definition' => [
                'quantity' => 3,
                'definition' => '%f',
            ],
            'invalid custome dice definition' => [
                'quantity' => 3,
                'definition' => '1,2,3,foo',
            ],
        ];
    }
}
