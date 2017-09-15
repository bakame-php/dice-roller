<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use OutOfRangeException;

final class Cup implements Countable, IteratorAggregate, Rollable
{
    /**
     * @var Rollable[]
     */
    private $items = [];

    /**
     * New instance
     *
     * @param Rollable[] $pItems a list of Rollable object
     */
    public function __construct(Rollable ...$pItems)
    {
        $this->items = $pItems;
    }

    /**
     * Create a new Cup From Dice definition
     *
     * @param int $pQuantity Dice count
     * @param int $pSides    Dice sides count
     *
     * @throws OutOfRangeException if the quantity is lesser than 1
     *
     * @return self
     */
    public static function createFromDice(int $pQuantity, int $pSides): self
    {
        if ($pQuantity < 1) {
            throw new OutOfRangeException(sprintf('The quantity of dice `%s` is not valid', $pQuantity));
        }

        $dice = new self();
        for ($i = 0; $i < $pQuantity; ++$i) {
            $dice->items[] = new Dice($pSides);
        }

        return $dice;
    }

    /**
     * Create a new Cup From Dice definition
     *
     * @param int $pQuantity Dice count
     *
     * @throws OutOfRangeException if the quantity is lesser than 1
     *
     * @return self
     */
    public static function createFromFudgeDice(int $pQuantity): self
    {
        if ($pQuantity < 1) {
            throw new OutOfRangeException(sprintf('The quantity of dice `%s` is not valid', $pQuantity));
        }

        $dice = new self();
        for ($i = 0; $i < $pQuantity; ++$i) {
            $dice->items[] = new FudgeDice();
        }

        return $dice;
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @inheritdoc
     */
    public function getMinimum(): int
    {
        return array_reduce($this->items, [$this, 'minimum'], 0);
    }

    /**
     * Add the result of the Rollable::roll method
     * to the submitted sum
     *
     * @param  int      $pSum  initial sum
     * @param  Rollable $pRollable
     *
     * @return int
     */
    private function minimum(int $pSum, Rollable $pRollable): int
    {
        return $pSum + $pRollable->getMinimum();
    }

    /**
     * @inheritdoc
     */
    public function getMaximum(): int
    {
        return array_reduce($this->items, [$this, 'maximum'], 0);
    }

    /**
     * Add the result of the Rollable::roll method
     * to the submitted sum
     *
     * @param  int      $pSum  initial sum
     * @param  Rollable $pRollable
     *
     * @return int
     */
    private function maximum(int $pSum, Rollable $pRollable): int
    {
        return $pSum + $pRollable->getMaximum();
    }

    /**
     * @inheritdoc
     */
    public function roll(): int
    {
        return array_reduce($this->items, [$this, 'sum'], 0);
    }

    /**
     * Add the result of the Rollable::roll method
     * to the submitted sum
     *
     * @param  int      $sum  initial sum
     * @param  Rollable $item
     *
     * @return int
     */
    private function sum(int $pSum, Rollable $pRollable): int
    {
        return $pSum + $pRollable->roll();
    }
}