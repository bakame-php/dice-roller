<?php
namespace Ethtezahl\DiceRoller\Test\Modifier;

use Ethtezahl\DiceRoller;
use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\Exception;
use Ethtezahl\DiceRoller\Modifier\DropKeep;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Ethtezahl\DiceRoller\Modifier\DropKeep
 */
final class DropKeepTest extends TestCase
{
    private $cup;

    public function setUp()
    {
        $this->cup = DiceRoller\roll_create('4d6');
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorThrows1()
    {
        $this->expectException(Exception::class);
        new DropKeep($this->cup, DropKeep::DROP_LOWEST, 6);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorThrows2()
    {
        $this->expectException(Exception::class);
        new DropKeep($this->cup, 'foobar', 3);
    }

    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $cup = new DropKeep(new Cup(
            new Dice(3),
            new Dice(3),
            new Dice(4)
        ), DropKeep::DROP_LOWEST, 2);
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
     * @dataProvider validParametersProvider
     */
    public function testModifier(string $algo, int $threshold, int $min, int $max)
    {
        $cup = new DropKeep($this->cup, $algo, $threshold);
        $res = $cup->roll();
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
            ]
        ];
    }
}
