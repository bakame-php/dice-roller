<?php
namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\Factory;
use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\FudgeDice;
use Ethtezahl\DiceRoller\Rollable;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Ethtezahl\DiceRoller\Cup
 */
final class CupTest extends TestCase
{
    private $Factory;

    public function setUp()
    {
        $this->Factory = new Factory();
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
            $this->Factory->newInstance('4D10'),
            $this->Factory->newInstance('2d4')
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
     * @covers ::getMinimum
     * @covers ::getMaximum
     */
    public function testCreateFromDice()
    {
        $cup = Cup::createFromDice(2, 3);
        $this->assertCount(2, $cup);
        $this->assertContainsOnlyInstancesOf(Dice::class, $cup);
        $this->assertSame(2, $cup->getMinimum());
        $this->assertSame(6, $cup->getMaximum());
    }

    /**
     * @covers ::createFromDice
     * @dataProvider invalidDiceProvider
     */
    public function testCreateFromDiceThrowsException($quantity, $sides)
    {
        $this->expectException(OutOfRangeException::class);
        Cup::createFromDice($quantity, $sides);
    }

    public function invalidDiceProvider()
    {
        return [
            'invalid quantity' => [
                'quantity' => -1,
                'sides' => 3,
            ],
            'invalid sides' => [
                'quantity' => 1,
                'sides' => 1,
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::createFromFudgeDice
     * @covers ::getMinimum
     * @covers ::getMaximum
     */
    public function testCreateFromFudgeDice()
    {
        $cup = Cup::createFromFudgeDice(3);
        $this->assertCount(3, $cup);
        $this->assertContainsOnlyInstancesOf(FudgeDice::class, $cup);
        $this->assertSame(-3, $cup->getMinimum());
        $this->assertSame(3, $cup->getMaximum());
    }

    /**
     * @covers ::createFromFudgeDice
     */
    public function testCreateFromFudgeDiceThrowsException()
    {
        $this->expectException(OutOfRangeException::class);
        Cup::createFromFudgeDice(0);
    }
}
