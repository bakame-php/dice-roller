<?php

namespace Bakame\DiceRoller\Test\Modifier;

use Bakame\DiceRoller;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\DropKeep;
use Bakame\DiceRoller\Exception;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Bakame\DiceRoller\DropKeep
 */
final class DropKeepTest extends TestCase
{
    private $cup;

    public function setUp()
    {
        $this->cup = DiceRoller\create('4d6');
    }

    /**
     * @covers ::__construct
     * @covers ::validate
     */
    public function testConstructorThrows1()
    {
        $this->expectException(Exception::class);
        new DropKeep($this->cup, DropKeep::DROP_LOWEST, 6);
    }

    /**
     * @covers ::__construct
     * @covers ::validate
     */
    public function testConstructorThrows2()
    {
        $this->expectException(Exception::class);
        new DropKeep($this->cup, 'foobar', 3);
    }

    /**
     * @covers ::__toString
     * @covers ::validate
     * @covers ::getOperator
     * @covers ::getThreshold
     * @covers ::getRollable
     */
    public function testGetter()
    {
        $cup = new Cup(new Dice(3), new Dice(3), new Dice(4));
        $obj = new DropKeep($cup, DropKeep::DROP_LOWEST, 2);

        $this->assertSame('(2D3+D4)DL2', (string) $obj);
        $this->assertSame(2, $obj->getThreshold());
        $this->assertSame($cup, $obj->getRollable());
        $this->assertSame(DropKeep::DROP_LOWEST, $obj->getOperator());
    }

    /**
     * @covers ::__construct
     * @covers ::validate
     * @covers ::withOperator
     * @covers ::withRollable
     * @covers ::withThreshold
     */
    public function testImmutability()
    {
        $cup = new Cup(new Dice(3), new Dice(3), new Dice(4));
        $obj = new DropKeep($cup, DropKeep::DROP_LOWEST, 2);

        $this->assertSame($obj->withRollable($cup), $obj);
        $this->assertSame($obj->withThreshold(2), $obj);
        $this->assertSame($obj->withOperator(DropKeep::DROP_LOWEST), $obj);
        $this->assertNotEquals($obj->withOperator(DropKeep::DROP_HIGHEST), $obj);
        $this->assertNotEquals($obj->withThreshold(3), $obj);
        $this->assertNotEquals($obj->withRollable(new Cup(new Dice(3), new Dice(4))), $obj);

        $cup2 = new DropKeep(new Dice(3), DropKeep::KEEP_HIGHEST, 1);
        $this->assertSame(1, $cup2->getThreshold());
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::calculate
     * @covers ::keepLowest
     * @covers ::keepHighest
     * @covers ::isValid
     * @covers ::drop
     * @covers ::dropLowest
     * @covers ::dropHighest
     * @covers ::roll
     * @covers \Bakame\DiceRoller\Result
     * @dataProvider validParametersProvider
     * @param string $algo
     * @param int    $threshold
     * @param int    $min
     * @param int    $max
     */
    public function testModifier(string $algo, int $threshold, int $min, int $max)
    {
        $cup = new DropKeep($this->cup, $algo, $threshold);
        $res = $cup->roll()->getResult();
        $this->assertSame($min, $cup->getMinimum());
        $this->assertSame($max, $cup->getMaximum());
        $this->assertGreaterThanOrEqual($min, $res);
        $this->assertLessThanOrEqual($max, $res);
    }

    public function validParametersProvider()
    {
        return [
            'dl' => [
                'algo' => DropKeep::DROP_LOWEST,
                'threshold' => 3,
                'min' => 1,
                'max' => 6,
            ],
            'dh' => [
                'algo' => DropKeep::DROP_HIGHEST,
                'threshold' => 2,
                'min' => 2,
                'max' => 12,
            ],
            'kl' => [
                'algo' => DropKeep::KEEP_LOWEST,
                'threshold' => 2,
                'min' => 2,
                'max' => 12,
            ],
            'kh' => [
                'algo' => DropKeep::KEEP_HIGHEST,
                'threshold' => 3,
                'min' => 3,
                'max' => 18,
            ],
        ];
    }
}
