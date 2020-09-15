<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller;

use Bakame\DiceRoller\Exception\SyntaxError;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Bakame\DiceRoller\NotationParser
 */
final class NotationParserTest extends TestCase
{
    private NotationParser $parser;

    public function setUp(): void
    {
        $this->parser = new NotationParser();
    }

    /**
     * @dataProvider invalidStringProvider
     */
    public function testInvalidGroupDefinition(string $expected): void
    {
        self::expectException(SyntaxError::class);

        $this->parser->parse($expected);
    }

    public function invalidStringProvider(): iterable
    {
        return [
            'missing separator D' => ['ZZZ'],
            'missing group definition' => ['+'],
            'invalid group' => ['10+3dF'],
            'invalid modifier' => ['3dFZZZZ'],
            'invalid complex cup' => ['(3DF+2D6)*3+3F^2'],
            'invalid complex cup 2' => ['(3DFoobar+2D6)*3+3DF^2'],
            'invalid complex cup 3' => ['()*3'],
            'invalid custom dice' => ['3dss'],
        ];
    }

    /**
     * @dataProvider validStringProvider
     */
    public function testValidParser(string $expected, array $parsed): void
    {
        $result = $this->parser->parse($expected);

        self::assertSame($parsed, $result);
    }

    public function validStringProvider(): iterable
    {
        return [
            'empty cup' => [
                'notation' => '',
                'parsed' => [],
            ],
            'simple' => [
                'notation' => '2D3',
                'parsed' => [
                    ['definition' => ['simple' => ['type' => 'D3', 'quantity' => '2']], 'modifiers' => []],
                ],
            ],
            'empty nb dice' => [
                'notation' => 'd3',
                'parsed' => [
                    ['definition' => ['simple' => ['type' => 'D3', 'quantity' => '1']], 'modifiers' => []],
                ],
            ],
            'empty nb sides' => [
                'notation' => '3d',
                'parsed' => [
                    ['definition' => ['simple' => ['type' => 'D6', 'quantity' => '3']], 'modifiers' => []],
                ],
            ],
            'mixed group' => [
                'notation' => '2D3+1D4',
                'parsed' => [
                    ['definition' => ['simple' => ['type' => 'D3', 'quantity' => '2']], 'modifiers' => []],
                    ['definition' => ['simple' => ['type' => 'D4', 'quantity' => '1']], 'modifiers' => []],
                ],
            ],
            'case insensitive' => [
                'notation' => '2d3+1d4',
                'parsed' => [
                    ['definition' => ['simple' => ['type' => 'D3', 'quantity' => '2']], 'modifiers' => []],
                    ['definition' => ['simple' => ['type' => 'D4', 'quantity' => '1']], 'modifiers' => []],
                ],
            ],
            'default to one dice' => [
                'notation' => 'd3+d4+1d3+5d2',
                'parsed' => [
                    ['definition' => ['simple' => ['type' => 'D3', 'quantity' => '1']], 'modifiers' => []],
                    ['definition' => ['simple' => ['type' => 'D4', 'quantity' => '1']], 'modifiers' => []],
                    ['definition' => ['simple' => ['type' => 'D3', 'quantity' => '1']], 'modifiers' => []],
                    ['definition' => ['simple' => ['type' => 'D2', 'quantity' => '5']], 'modifiers' => []],
                ],
            ],
            'fudge dice' => [
                'notation' => '2dF',
                'parsed' => [
                    ['definition' => ['simple' => ['type' => 'DF', 'quantity' => '2']], 'modifiers' => []],
                ],
            ],
            'multiple fudge dice' => [
                'notation' => 'dF+3dF',
                'parsed' => [
                    ['definition' => ['simple' => ['type' => 'DF', 'quantity' => '1']], 'modifiers' => []],
                    ['definition' => ['simple' => ['type' => 'DF', 'quantity' => '3']], 'modifiers' => []],
                ],
            ],
            'mixed cup' => [
                'notation' => '2df+3d2',
                'parsed' => [
                    ['definition' => ['simple' => ['type' => 'DF', 'quantity' => '2']], 'modifiers' => []],
                    ['definition' => ['simple' => ['type' => 'D2', 'quantity' => '3']], 'modifiers' => []],
                ],
            ],
            'add modifier' => [
                'notation' => '2d3-4',
                'parsed' => [[
                    'definition' => ['simple' => ['type' => 'D3', 'quantity' => '2']],
                    'modifiers' => [['modifier' => 'arithmetic', 'operator' => '-', 'value' => 4]],
                ]],
            ],
            'add modifier to multiple group' => [
                'notation' => '2d3+4+3dF!>1/4^3',
                'parsed' => [
                    [
                        'definition' => ['simple' => ['type' => 'D3', 'quantity' => '2']],
                        'modifiers' => [
                            ['modifier' => 'arithmetic', 'operator' => '+', 'value' => 4],
                        ],
                    ],
                    [
                        'definition' => ['simple' => ['type' => 'DF', 'quantity' => '3']],
                        'modifiers' => [
                            ['modifier' => 'explode', 'operator' => '>', 'value' => 1],
                            ['modifier' => 'arithmetic', 'operator' => '/', 'value' => 4],
                            ['modifier' => 'arithmetic', 'operator' => '^', 'value' => 3],
                        ],
                    ],
                ],
            ],
            'add explode modifier' => [
                'notation' => '2d3!',
                'parsed' => [[
                    'definition' => ['simple' => ['type' => 'D3', 'quantity' => '2']],
                    'modifiers' => [['modifier' => 'explode', 'operator' => '=', 'value' => 1]],
                ]],
            ],
            'add keep lowest modifier' => [
                'notation' => '2d3kl1',
                'parsed' => [[
                    'definition' => ['simple' => ['type' => 'D3', 'quantity' => '2']],
                    'modifiers' => [['modifier' => 'dropkeep', 'operator' => 'KL', 'value' => 1]],
                ]],
            ],
            'add keep highest modifier' => [
                'notation' => '2d3kh2',
                'parsed' => [[
                    'definition' => ['simple' => ['type' => 'D3', 'quantity' => '2']],
                    'modifiers' => [['modifier' => 'dropkeep', 'operator' => 'KH', 'value' => 2]],
                ]],
            ],
            'add drop lowest modifier' => [
                'notation' => '4d6dl2',
                'parsed' => [[
                    'definition' => ['simple' => ['type' => 'D6', 'quantity' => '4']],
                    'modifiers' => [['modifier' => 'dropkeep', 'operator' => 'DL', 'value' => 2]],
                ]],
            ],
            'add drop highest modifier' => [
                'notation' => '4d6dh3',
                'parsed' => [[
                    'definition' => ['simple' => ['type' => 'D6', 'quantity' => '4']],
                    'modifiers' => [['modifier' => 'dropkeep', 'operator' => 'DH', 'value' => 3]],
                ]],
            ],
            'complex mixed cup' => [
                'notation' => '(3DF+2D6)*3+3DF^2',
                'parsed' => [
                    [
                        'definition' => [
                            'composite' => [
                               ['definition' => ['simple' => ['type' => 'DF', 'quantity' => '3']], 'modifiers' => []],
                               ['definition' => ['simple' => ['type' => 'D6', 'quantity' => '2']], 'modifiers' => []],
                            ],
                        ],
                        'modifiers' => [['modifier' => 'arithmetic', 'operator' => '*', 'value' => 3]],
                    ],
                    [
                        'definition' => ['simple' => ['type' => 'DF', 'quantity' => '3']],
                        'modifiers' => [['modifier' => 'arithmetic', 'operator' => '^', 'value' => 2]],
                    ],
                ],
            ],
            'percentile dice' => [
                'notation' => '3d%',
                'parsed' => [['definition' => ['simple' => ['type' => 'D%', 'quantity' => '3']], 'modifiers' => []]],
            ],
            'custom dice' => [
                'notation' => '2d[1,2,34]',
                'parsed' => [['definition' => ['simple' => ['type' => 'D[1,2,34]', 'quantity' => '2']], 'modifiers' => []]],
            ],
        ];
    }

    /**
     * @dataProvider permissiveParserProvider
     */
    public function testPermissiveParser(string $full, string $short): void
    {
        self::assertEquals($this->parser->parse($short), $this->parser->parse($full));
    }

    public function permissiveParserProvider(): iterable
    {
        return [
            'default dice size' => [
                'full' => '1d6',
                'short' => '1d',
            ],
            'default dice size 2' => [
                'full' => '1d6',
                'short' => 'd',
            ],
            'default fudge dice size' => [
                'full' => '1dF',
                'short' => 'df',
            ],
            'default percentile dice size' => [
                'full' => '1d%',
                'short' => 'd%',
            ],
            'default keep lowest modifier' => [
                'full' => '2d3kl1',
                'short' => '2d3KL',
            ],
            'default keep highest modifier' => [
                'full' => '2d3KH1',
                'short' => '2d3kh',
            ],
            'default drop highest modifier' => [
                'full' => '2d3dh1',
                'short' => '2d3DH',
            ],
            'default drop lowest modifier' => [
                'full' => '2d3dl1',
                'short' => '2D3Dl',
            ],
            'default explode modifier' => [
                'full' => '1d6!',
                'short' => 'D!',
            ],
            'default explode modifier with threshold' => [
                'full' => '1d6!=3',
                'short' => 'D!3',
            ],
        ];
    }
}
