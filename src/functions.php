<?php
/**
* This file is part of the League.csv library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/thephpleague/csv/
* @version 9.1.0
* @package League.csv
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
declare(strict_types=1);

namespace Ethtezahl\DiceRoller;

/**
 * Returns a new Cup Instance from a string pattern
 *
 * @param string $pAsked
 *
 * @throws Exception if an error occurs while parsing the dice notation string
 *
 * @return Rollable
 */
function roll_create(string $pStr): Rollable
{
    static $parser;

    $parser = $parser ?? new Factory();

    return $parser->newInstance($pStr);
}