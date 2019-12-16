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

use Bakame\DiceRoller\Contract\Context;
use Bakame\DiceRoller\Contract\Rollable;

final class TossContext implements Context
{
    /**
     * @var string
     */
    private $notation;

    /**
     * @var string
     */
    private $source;

    /**
     * @var array
     */
    private $extensions;

    public function __construct(Rollable $rollable, string $source, array $extensions = [])
    {
        unset($extensions['source'], $extensions['notation'], $extensions['value'], $extensions['operation']);

        $this->notation = $rollable->notation();
        $this->source = $source;
        $this->extensions = $extensions;
    }

    /**
     * {@inheritDoc}
     */
    public function notation(): string
    {
        return $this->notation;
    }

    /**
     * {@inheritDoc}
     */
    public function source(): string
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     */
    public function extensions(): array
    {
        return $this->extensions;
    }

    /**
     * {@inheritDoc}
     */
    public function asArray(): array
    {
        return ['source' => $this->source, 'notation' => $this->notation] + $this->extensions;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->asArray();
    }
}
