<?php
namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\Exception;
use Ethtezahl\DiceRoller\ExplodeModifier;
use Ethtezahl\DiceRoller\Factory;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Ethtezahl\DiceRoller\ExplodeModifier
 */
final class ExplodeModifierTest extends TestCase
{
    private $cup;

    public function setUp()
    {
        $this->cup = (new Factory())->newInstance('4d6');
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorThrows()
    {
        $this->expectException(Exception::class);
        new ExplodeModifier($this->cup, 2, 'foobar');
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::sum
     * @covers ::isValid
     * @covers ::roll
     * @dataProvider validProvider
     */
    public function testModifier(string $algo, int $threshold, int $min, int $max)
    {
        $cup = new ExplodeModifier($this->cup, $threshold, $algo);
        $res = $cup->roll();
        $this->assertSame($min, $cup->getMinimum());
        $this->assertSame($max, $cup->getMaximum());
        $this->assertGreaterThanOrEqual($min, $res);
        $this->assertLessThanOrEqual($max, $res);
    }

    public function validProvider()
    {
        return [
            'equals' => [
                'algo' => ExplodeModifier::EQUALS,
                'threshold' => 3,
                'min' => 4,
                'max' => PHP_INT_MAX,
            ],
            'greater than' => [
                'algo' => ExplodeModifier::LESSER_THAN,
                'threshold' => 2,
                'min' => 4,
                'max' => PHP_INT_MAX,
            ],
            'lesser than' => [
                'algo' => ExplodeModifier::GREATER_THAN,
                'threshold' => 2,
                'min' => 4,
                'max' => PHP_INT_MAX,
            ],
        ];
    }
}
