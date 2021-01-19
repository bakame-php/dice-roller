<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bakame\DiceRoller;

use function count;
use function iterator_to_array;

final class Factory
{
    public function __construct(
        private Parser $parser,
        private RandomIntGenerator $randomIntGenerator
    ) {
    }

    public static function fromSystem(): self
    {
        return new self(new NotationParser(), new SystemRandomInt());
    }

    /**
     * @throws SyntaxError if the object can not be created due to some syntax error
     */
    public function newInstance(string $notation, Tracer $tracer = null): Rollable
    {
        $rollable = $this->create($this->parser->parse($notation));
        if (null === $tracer) {
            return $rollable;
        }

        if ($rollable instanceof EnablesDeepTracing) {
            $rollable->setTracerRecursively($tracer);
        } elseif ($rollable instanceof SupportsTracing) {
            $rollable->setTracer($tracer);
        }

        return $rollable;
    }

    /**
     * Returns a new object that can be rolled from a parsed dice notation.
     */
    private function create(array $parsed): Rollable
    {
        $rollable = new Cup();
        foreach ($parsed as $parts) {
            $rollable = $this->addRollable($rollable, $parts);
        }

        return $this->flattenRollable($rollable);
    }

    /**
     * Adds a Rollable item to a pool.
     */
    private function addRollable(Cup $pool, array $parts): Cup
    {
        $rollable = $this->createRollable($parts['definition']);
        foreach ($parts['modifiers'] as $matches) {
            $rollable = $this->decorate($rollable, $matches);
        }

        return $pool->withAddedRollable($this->flattenRollable($rollable));
    }

    /**
     * Generates the Pool from the dice notation matched pattern.
     *
     * @throws SyntaxError
     */
    private function createRollable(array $parts): Rollable
    {
        if (isset($parts['composite'])) {
            return $this->create($parts['composite']);
        }

        return Cup::of(
            (int) $parts['simple']['quantity'],
            $this->createDice($parts['simple']['type'])
        );
    }

    /**
     * Parse Rollable definition.
     *
     * @throws SyntaxError
     */
    private function createDice(string $notation): Dice
    {
        if ('DF' === $notation) {
            return new FudgeDie($this->randomIntGenerator);
        }

        if ('D%' === $notation) {
            return new PercentileDie($this->randomIntGenerator);
        }

        if (str_contains($notation, '[')) {
            return CustomDie::fromNotation($notation, $this->randomIntGenerator);
        }

        return SidedDie::fromNotation($notation, $this->randomIntGenerator);
    }

    /**
     * Decorates the Rollable object with modifiers objects.
     *
     * @throws SyntaxError
     */
    private function decorate(Rollable $rollable, array $matches): Rollable
    {
        if ('arithmetic' === $matches['modifier']) {
            return Arithmetic::fromOperation($rollable, $matches['operator'], $matches['value']);
        }

        if ('dropkeep' === $matches['modifier']) {
            return DropKeep::fromAlgorithm($rollable, $matches['operator'], $matches['value']);
        }

        return Explode::fromAlgorithm($rollable, $matches['operator'], $matches['value']);
    }

    /**
     * Extracts the Rollable object from a Pool with only one item.
     */
    private function flattenRollable(Rollable $rollable): Rollable
    {
        if (!$rollable instanceof Pool) {
            return $rollable;
        }

        if (1 !== count($rollable)) {
            return $rollable;
        }

        return iterator_to_array($rollable, false)[0];
    }
}
