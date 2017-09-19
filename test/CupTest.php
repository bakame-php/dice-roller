<?php
namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\Exception;
use Ethtezahl\DiceRoller\Factory;
use Ethtezahl\DiceRoller\FudgeDice;
use Ethtezahl\DiceRoller\Rollable;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Ethtezahl\DiceRoller\Cup
 */
final class CupTest extends TestCase
{
    private $factory;

    public function setUp()
    {
        $this->factory = new Factory();
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers ::sum
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::count
     * @covers ::getIterator
     */
    public function testRoll()
    {
        $cup = new Cup(
            $this->factory->newInstance('4D10'),
            $this->factory->newInstance('2d4')
        );
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
     * @covers ::createFromDice
     * @covers ::filterSize
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @dataProvider validNamedConstructor
     */
    public function testCreateFromDice($quantity, $sides, $className, $min, $max)
    {
        $cup = Cup::createFromDice($quantity, $sides);
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
        ];
    }

    /**
     * @covers ::createFromDice
     * @covers ::filterSize
     * @dataProvider invalidNamedConstructor
     */
    public function testCreateFromDiceThrowsException($quantity, $sides)
    {
        $this->expectException(Exception::class);
        Cup::createFromDice($quantity, $sides);
    }

    public function invalidNamedConstructor()
    {
        return [
            'invalid quantity' => [
                'quantity' => -1,
                'sides' => 3,
            ],
            'invalid sides' => [
                'quantity' => 2,
                'sides' => 1,
            ],
            'invalid sides with wrong string' => [
                'quantity' => 3,
                'sides' => 'foobar',
            ]
        ];
    }
}
