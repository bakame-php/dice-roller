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

namespace Bakame\DiceRoller\Trace;

use Bakame\DiceRoller\Contract\TraceContext;
use JsonSerializable;

final class Context implements TraceContext, JsonSerializable
{
    /**
     * @var string
     */
    private $source;

    /**
     * @var array
     */
    private $extensions;

    public function __construct(string $source, array $extensions = [])
    {
        unset($extensions['source'], $extensions['operation'], $extensions['expression'], $extensions['result']);

        $this->source = $source;
        $this->extensions = $extensions;
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
    public function toArray(): array
    {
        return ['source' => $this->source] + $this->extensions;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
