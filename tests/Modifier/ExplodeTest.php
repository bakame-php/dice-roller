<?php

namespace Bakame\DiceRoller\Test\Modifier;

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\Exception;
use Bakame\DiceRoller\Modifier\Explode;
use Bakame\DiceRoller\Rollable;
use PHPUnit\Framework\TestCase;
use function Bakame\DiceRoller\create;

/**
 * @coversDefaultClass Bakame\DiceRoller\Modifier\Explode
 */
final class ExplodeTest extends TestCase
{
    private $cup;

    public function setUp()
    {
        $this->cup = create('4d6');
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorThrows()
    {
        $this->expectException(Exception::class);
        new Explode($this->cup, 'foobar', 2);
    }

    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $cup = new Explode(new Cup(
            new Dice(3),
            new Dice(3),
            new Dice(4)
        ), Explode::EQUALS, 3);

        $this->assertSame('(2D3+D4)!=3', (string) $cup);
    }

    public function testGetTrace()
    {
        $dice = $this->createMock(Rollable::class);
        $dice->method('roll')
            ->will($this->onConsecutiveCalls(2, 2, 3));

        $dice->method('getTrace')
            ->will($this->onConsecutiveCalls('2', '2', '3'))
        ;

        $cup = new Explode(new Cup($dice), Explode::EQUALS, 2);
        $this->assertSame('', $cup->getTrace());
        $this->assertSame(7, $cup->roll());
        $this->assertSame('2 + 2 + 3', $cup->getTrace());
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::calculate
     * @covers ::isValid
     * @covers ::roll
     * @dataProvider validParametersProvider
     * @param string $algo
     * @param int    $threshold
     * @param int    $min
     * @param int    $max
     */
    public function testModifier(string $algo, int $threshold, int $min, int $max)
    {
        $cup = new Explode($this->cup, $algo, $threshold);
        $res = $cup->roll();
        $this->assertSame($min, $cup->getMinimum());
        $this->assertSame($max, $cup->getMaximum());
        $this->assertGreaterThanOrEqual($min, $res);
        $this->assertLessThanOrEqual($max, $res);
    }

    public function validParametersProvider()
    {
        return [
            'equals' => [
                'algo' => Explode::EQUALS,
                'threshold' => 3,
                'min' => 4,
                'max' => PHP_INT_MAX,
            ],
            'greater than' => [
                'algo' => Explode::LESSER_THAN,
                'threshold' => 2,
                'min' => 4,
                'max' => PHP_INT_MAX,
            ],
            'lesser than' => [
                'algo' => Explode::GREATER_THAN,
                'threshold' => 2,
                'min' => 4,
                'max' => PHP_INT_MAX,
            ],
        ];
    }
}
