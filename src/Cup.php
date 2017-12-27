<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Bakame\DiceRoller;

use Countable;
use IteratorAggregate;

final class Cup implements Countable, IteratorAggregate, Rollable
{
    /**
     * @var Rollable[]
     */
    private $items = [];

    /**
     * @var string
     */
    private $trace;

    /**
     * Create a new Cup From Dice definition.
     *
     * The returned Cup object will contain only one type of Rollable object.
     *
     * @param int    $quantity   Dice count
     * @param string $definition Dice definition
     *
     * @return self
     */
    public static function createFromDiceDefinition(int $quantity, string $definition): self
    {
        return self::createFromRollable($quantity, self::parseDefinition($definition));
    }

    /**
     * Parse Rollable definition
     *
     * @param string $definition
     *
     * @throws Exception If the defintion can not be parsed
     *
     * @return Rollable
     */
    private static function parseDefinition(string $definition): Rollable
    {
        if (false !== ($size = filter_var($definition, FILTER_VALIDATE_INT))) {
            return new Dice($size);
        }

        $definition = strtolower($definition);
        if ('f' === $definition) {
            return new FudgeDice();
        }

        if ('%' === $definition) {
            return new PercentileDice();
        }

        if ('][' === substr($definition.$definition, strlen($definition) - 1, 2)) {
            $sides = explode(',', substr($definition, 1, -1));
            $sides = filter_var($sides, FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

            return new CustomDice(...$sides);
        }

        throw new Exception(sprintf('The dice definition `%s` is invalid or not supported', $definition));
    }

    /**
     * Create a new Cup containing only on type of Rollable object
     *
     * @param int      $quantity
     * @param Rollable $rollable
     *
     * @throws Exception if the quantity is lesser than 0
     *
     * @return self
     */
    public static function createFromRollable(int $quantity, Rollable $rollable): self
    {
        if ($quantity < 1) {
            throw new Exception(sprintf('The quantity of dice `%s` is not valid', $quantity));
        }

        $cup = new self();
        $cup->items[] = $rollable;
        for ($i = 0; $i < $quantity - 1; ++$i) {
            $cup->items[] = clone $rollable;
        }

        return $cup;
    }

    /**
     * New instance
     *
     * @param mixed $items a list of Rollable objects (iterable array or Traversable object)
     */
    public function __construct(Rollable ...$items)
    {
        $this->items = $items;
        $this->trace = '';
    }

    /**
     * Return an instance with the added Rollable object.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified Rollable object.
     *
     * @param Rollable $rollable
     *
     * @return self
     */
    public function withRollable(Rollable $rollable): self
    {
        $cup = clone $this;
        $cup->items[] = $rollable;

        return $cup;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $parts = array_map(function (Rollable $rollable) {
            return (string) $rollable;
        }, $this->items);

        $pool = array_count_values($parts);
        array_walk($pool, function (&$value, $offset) {
            $value = $value > 1 ? $value.$offset : $offset;
        });

        $this->trace = '';

        return implode('+', $pool);
    }

    /**
     * Returns the number of Rollable objects.
     */
    public function count()
    {
        $this->trace = '';

        return count($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->trace = '';

        foreach ($this->items as $rollable) {
            yield $rollable;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $this->trace = '';

        return array_reduce($this->items, [$this, 'minimum'], 0);
    }

    /**
     * Add the result of the Rollable::getMinimum method to the submitted sum.
     *
     * @param int      $sum      initial sum
     * @param Rollable $rollable
     *
     * @return int
     */
    private function minimum(int $sum, Rollable $rollable): int
    {
        return $sum + $rollable->getMinimum();
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $this->trace = '';

        return array_reduce($this->items, [$this, 'maximum'], 0);
    }

    /**
     * Add the result of the Rollable::getMaximum method to the submitted sum.
     *
     * @param int      $sum      initial sum
     * @param Rollable $rollable
     *
     * @return int
     */
    private function maximum(int $sum, Rollable $rollable): int
    {
        return $sum + $rollable->getMaximum();
    }

    /**
     * {@inheritdoc}
     */
    public function getTrace(): string
    {
        return $this->trace;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $res = array_reduce($this->items, [$this, 'calculate'], []);

        $roll = array_sum(array_column($res, 'roll'));

        $this->trace = implode(' + ', array_map(function (string $value) {
            if (false !== strpos($value, '+')) {
                return '('.$value.')';
            }

            return $value;
        }, array_column($res, 'trace')));

        return $roll;
    }

    /**
     * Add the result of the Rollable::roll method to the submitted sum.
     *
     * @param array    $res
     * @param Rollable $rollable
     *
     * @return array
     */
    private function calculate(array $res, Rollable $rollable): array
    {
        $roll = $rollable->roll();
        $trace = $rollable->getTrace();

        $res[] = [
            'roll' => $roll,
            'trace' => $trace,
        ];

        return $res;
    }
}
