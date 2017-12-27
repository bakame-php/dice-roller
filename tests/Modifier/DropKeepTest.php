<?php

namespace Ethtezahl\DiceRoller\Test\Modifier;

use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\Exception;
use Ethtezahl\DiceRoller\Modifier\DropKeep;
use Ethtezahl\DiceRoller\Rollable;
use PHPUnit\Framework\TestCase;
use function Ethtezahl\DiceRoller\create;

/**
 * @coversDefaultClass Ethtezahl\DiceRoller\Modifier\DropKeep
 */
final class DropKeepTest extends TestCase
{
    private $cup;

    public function setUp()
    {
        $this->cup = create('4d6');
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
     * @covers ::roll
     * @covers ::getTrace
     * @covers \Ethtezahl\DiceRoller\Cup::getTrace
     */
    public function testGetTrace()
    {
        $dice1 = new class() implements Rollable {
            public function getMinimum(): int
            {
                return 1;
            }

            public function getMaximum(): int
            {
                return 1;
            }

            public function roll(): int
            {
                return 1;
            }

            public function __toString()
            {
                return '1';
            }

            public function getTrace(): string
            {
                return '1';
            }
        };

        $dice2 = new class() implements Rollable {
            public function getMinimum(): int
            {
                return 2;
            }

            public function getMaximum(): int
            {
                return 2;
            }

            public function roll(): int
            {
                return 2;
            }

            public function __toString()
            {
                return '2';
            }

            public function getTrace(): string
            {
                return '2';
            }
        };

        $rollables = new Cup($dice1, clone $dice1, $dice2, clone $dice2);
        $cup = new DropKeep($rollables, DropKeep::DROP_LOWEST, 1);
        $this->assertSame('', $rollables->getTrace());
        $this->assertSame('', $cup->getTrace());
        $this->assertSame(5, $cup->roll());
        $this->assertSame('(1 + 2 + 2)', $cup->getTrace());
        $this->assertSame('', $rollables->getTrace());
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::calculate
     * @covers ::keepLowest
     * @covers ::keepHighest
     * @covers ::drop
     * @covers ::dropLowest
     * @covers ::dropHighest
     * @covers ::roll
     * @dataProvider validParametersProvider
     * @param string $algo
     * @param int    $threshold
     * @param int    $min
     * @param int    $max
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
            ],
        ];
    }
}
