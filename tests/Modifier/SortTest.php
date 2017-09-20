<?php
namespace Ethtezahl\DiceRoller\Test\Modifier;

use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\Exception;
use Ethtezahl\DiceRoller\Factory;
use Ethtezahl\DiceRoller\Modifier\Sort;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Ethtezahl\DiceRoller\Modifier\Sort
 */
final class SortTest extends TestCase
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
        $this->expectException(Exception::class);
        new Sort($this->cup, 6, Sort::DROP_LOWEST);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorThrows2()
    {
        $this->expectException(Exception::class);
        new Sort($this->cup, 3, 'foobar');
    }

    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $cup = new Sort(new Cup(
            new Dice(3),
            new Dice(3),
            new Dice(4)
        ), 2, Sort::DROP_LOWEST);
        $this->assertSame('(2D3+D4)DL2', (string) $cup);
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::calculate
     * @covers ::keepLowest
     * @covers ::keepHighest
     * @covers ::dropLowest
     * @covers ::dropHighest
     * @covers ::roll
     * @dataProvider validArithmeticProvider
     */
    public function testModifier(string $algo, int $threshold, int $min, int $max)
    {
        $cup = new Sort($this->cup, $threshold, $algo);
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
                'algo' => Sort::DROP_LOWEST,
                'threshold' => 3,
                'min' => 1,
                'max' => 6,
            ],
            'dh' => [
                'algo' => Sort::DROP_HIGHEST,
                'threshold' => 2,
                'min' => 2,
                'max' => 12,
            ],
            'kl' => [
                'algo' => Sort::KEEP_LOWEST,
                'threshold' => 2,
                'min' => 2,
                'max' => 12,
            ],
            'kh' => [
                'algo' => Sort::KEEP_HIGHEST,
                'threshold' => 3,
                'min' => 3,
                'max' => 18,
            ]
        ];
    }
}
