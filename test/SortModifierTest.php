<?php
namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\Factory;
use Ethtezahl\DiceRoller\SortModifier;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Ethtezahl\DiceRoller\SortModifier
 */
final class SortModifierTest extends TestCase
{
    private $cup;

    public function setUp()
    {
        $this->cup = (new Factory())->newInstance('4d6');
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorThrows1()
    {
        $this->expectException(OutOfRangeException::class);
        new SortModifier($this->cup, 6, SortModifier::DROP_LOWEST);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorThrows2()
    {
        $this->expectException(OutOfRangeException::class);
        new SortModifier($this->cup, 3, 'foobar');
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::sum
     * @covers ::keepLowest
     * @covers ::keepHighest
     * @covers ::dropLowest
     * @covers ::dropHighest
     * @covers ::roll
     * @dataProvider validArithmeticProvider
     */
    public function testModifier(string $algo, int $threshold, int $min, int $max)
    {
        $cup = new SortModifier($this->cup, $threshold, $algo);
        $res = $cup->roll();
        $this->assertSame($min, $cup->getMinimum());
        $this->assertSame($max, $cup->getMaximum());
        $this->assertGreaterThanOrEqual($min, $res);
        $this->assertLessThanOrEqual($max, $res);
    }

    public function validArithmeticProvider()
    {
        return [
            'dl' => [
                'algo' => SortModifier::DROP_LOWEST,
                'threshold' => 3,
                'min' => 1,
                'max' => 6,
            ],
            'dh' => [
                'algo' => SortModifier::DROP_HIGHEST,
                'threshold' => 2,
                'min' => 2,
                'max' => 12,
            ],
            'kl' => [
                'algo' => SortModifier::KEEP_LOWEST,
                'threshold' => 2,
                'min' => 2,
                'max' => 12,
            ],
            'kh' => [
                'algo' => SortModifier::KEEP_HIGHEST,
                'threshold' => 3,
                'min' => 3,
                'max' => 18,
            ]
        ];
    }
}
