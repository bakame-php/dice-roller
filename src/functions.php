<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
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
