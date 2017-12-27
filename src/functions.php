<?php
/**
* This file is part of the League.csv library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/bakame-php/dice-roller/
* @version 1.0.0
* @package bakame-php/dice-roller
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
declare(strict_types=1);

namespace Bakame\DiceRoller;

/**
 * Returns a new Cup Instance from a string pattern.
 *
 * @param string $pAsked
 * @param string $pStr
 *
 * @throws Exception if an error occurs while parsing the dice notation string
 *
 * @return Rollable
 */
function create(string $pStr): Rollable
{
    static $parser;

    $parser = $parser ?? new Parser();

    return $parser($pStr);
}
