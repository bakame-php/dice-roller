<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
namespace Ethtezahl\DiceRoller;

final class Cup
{
    /**
     *  Will contain all groups of die put in the cup
     * @var array
     */
    private $group = [];

    public function addGroup(Group $pGroup)
    {
        $this->group[] = $pGroup;
    }

    /**
     * @return int
     */
    public function roll() : int
    {
        $sum = 0;

        foreach ($this->group as $group) {
            $sum += $group->roll();
        }

        return $sum;
    }
}
