# RPG Dice Roller

## Concept.

I wanted to code a program which create stats for pre-rolled characters for rpg "Call of Cthulhu".
I needed a library that can simulate and give result of a roll of multiple dice (sometimes with a different number of sides).
So I created this library, to make these rolls.

## System Requirements

You need **PHP >= 7.0.0** but the latest stable version of PHP is recommended.

## Installation

composer require ethtezahl/dice-roller

## Basic usage

The code above will simulate the roll of two six-sided die

```php
<?php

// First: import needed class
use Ethtezahl\DiceRoller\Factory;

// Factory allow us to create dice cup.
$factory = new Factory();

// We create the cup that will contain the two die:
$cup = $factory->newInstance('2D6');

// Display the result:
echo $cup->roll();
```

## Advanced use: with multiple types of dices and modifiers

The following expression is supported by the library:

```php
$cup = $factory->newInstance('3D20+4+D4!>3/4^3');
echo $cup->roll();
```

## Documentation

### Rollable

Any object that can be rolled MUST implements the `Rollable` interface. Typically, dices, modifiers and cup all implement this interface to ease usage.

```php
<?php

namespace Ethtezahl\DiceRoller;

interface Rollable
{
    public function getMinimum(): int;
    public function getMaximum(): int;
    public function roll(): int;
    public function __toString();
}
```

- `Rollable::getMinimum` returns the minimum value the rollable object can return during a roll;
- `Rollable::getMaximum` returns the maximum value the rollable object can return during a roll;
- `Rollable::roll` returns a value from a roll.
- `Rollable::__toString` returns the string annotation of the Rollable object.

### Dices

There are 2 types of Dices, each type implements the `Rollable` interface and the `Countable` interface. The `count` method returns the dice sides count.

#### Basic Dices

```php
<?php

namespace Ethtezahl\DiceRoller;

final class Dice implements Rollable, Countable
{
    public function __construct(int $pSize);
}
```

The basic dice class constructor unique argument expects the dice sides count. A dice must have at least 2 sides otherwise a `Ethtezahl\DiceRoller\Exception` is thrown.

#### Fludge Dices

```php
<?php

namespace Ethtezahl\DiceRoller;

final class FludgeDice implements Rollable, Countable
{
}
```

Fludge dices do not need any constructor as their configurations is always 3 sides with values being `-1`, `0` or `1`.

### Cup

```php
<?php

namespace Ethtezahl\DiceRoller;

final class Cup implements Countable, IteratorAggrage, Rollable
{
    public static function createFromDice(int $pQuantity, int|string $pSize): self;
    public function __construct(Rollable ...$pItems);
}
```

A `Cup` is a collection of `Rollable` objects. This means that a `Cup` can contains multiple dices but other `Cup` objects as well. Once a Cup is instantiated there are no method to alter it.

#### createFromDice

The `Cup::createFromDice` named constructor enables creating uniformed `Cup` object which contains only `Dice` or `FludgeDice` objects.

```php
<?php

use Ethtezahl\DiceRoller\Cup;

echo Cup::createFromDice(3, 5);   // displays 3D5
echo Cup::createFromDice(4, 'F'); // displays 4DF
echo Cup::createFromDice(2, 'f'); // displays 2DF
```

A Cup must contain at least `1` dice otherwise a `Ethtezahl\DiceRoller\Exception` is thrown.

### Modifiers

Sometimes you may want to modify the outcome of a dice or a cup roll. The library comes bundle with 3 modifiers. Each modifiers implements the `Rollable` interface.

#### The Arithmetic modifier

```php
<?php

namespace Ethtezahl\DiceRoller\Modifier;

final class Arithmetic implements Rollable
{
    public function __construct(Rollable $rollable, int $value, string $operator);
}
```

This modifier is a decorator class which modify the outcome of a given rollable object using a given value and a given operator.

The modifier supports the following operators:

- `+` addition;
- `-` substraction;
- `*` multiplication;
- `/` division;
- `^` exponentiation;

The value given must be a positive integer or 0.

If the value or the operator are not valid a `Ethtezahl\DiceRoller\Exception` will be thrown.

```php
<?php

use Ethtezahl\DiceRoller\Modifier\Arithmetic;
use Ethtezahl\DiceRoller\Dice;

$modifier = new Arithmetic(new Dice(6), 3, '*');
echo $modifier; // displays D6*3;
```

#### The Sort Modifier

```php
<?php

namespace Ethtezahl\DiceRoller\Modifier;

final class Sort implements Rollable
{
    const DROP_HIGHEST = 'dh';
    const DROP_LOWEST = 'dl';
    const KEEP_HIGHEST = 'kh';
    const KEEP_LOWEST = 'kl';
    public function __construct(Cup $pRollable, int $pThreshold, string $pAlgo);
}
```

This modifier is a decorator class which modify the outcome of a given `Cup` object using a given threshold and a sorting algorithm. The supported algorithm are:

- `dh` or `Sort::DROP_HIGHEST` to drop the `$pThreshold` highest results of a given `Cup` object;
- `dl` or `Sort::DROP_LOWEST` to drop the `$pThreshold` lowest results of a given `Cup` object;
- `kh` or `Sort::KEEP_HIGHEST` to keep the `$pThreshold` highest results of a given `Cup` object;
- `kl` or `Sort::KEEP_LOWEST` to keep the `$pThreshold` lowest results of a given `Cup` object;

The `$pThreshold` MUST be lower or equals to the total numbers of rollable items in the `Cup` object.

If the algorithm or the threshold are not valid a `Ethtezahl\DiceRoller\Exception` will be thrown.

```php
<?php

use Ethtezahl\DiceRoller\Modifier\Sort;
use Ethtezahl\DiceRoller\Cup;

$modifier = new Sort(Cup::createFromDice(4, 6), 3, Sort::DROP_HIGHEST);
echo $modifier; // displays '4D6DH3'
```

#### The Explode Modifier

```php
<?php

namespace Ethtezahl\DiceRoller\Modifier;

final class Explode implements Rollable
{
    const EQUALS = '=';
    const GREATER_THAN = '>';
    const LESSER_THAN = '<';
    public function __construct(Cup $pRollable, int $pThreshold, string $pCompare);
}
```

This modifier is a decorator class which modify the outcome of a given `Cup` object using a threshold and a comparison operator. The following operators are supported:

- `=` or `Explode::EQUALS` explodes if any inner rollable roll result is equal to the `$pThreshold`;
- `>` or `Explode::GREATER_THAN` explodes if any inner rollable roll result is greater than the `$pThreshold`;
- `<` or `Explode::LESSER_THAN` explodes if any inner rollable roll result is lesser than the `$pThreshold`;

If the comparison operator is not recognized a `Ethtezahl\DiceRoller\Exception` will be thrown.

```php
<?php

use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\Modifier\Explode;
use Ethtezahl\DiceRoller\FudgeDice;

$cup = new Cup(new Dice(6), new FudgeDice(), new Dice(6), new Dice(6));
$modifier = new Explode($cup, 3, Explode::EQUALS);
echo $modifier; // displays (3D6+DF)!=3
```

### Factory

```php
<?php

namespace Ethtezahl\DiceRoller;

final class Factory
{
    public function newInstance(string $pStr): Rollable;
}
```

Because combining dices, cups and modifiers can be difficult, the package comes bundles with a `Factory` class to ease `Rollable` instance creation. The factory supports basic roll annotation rules in a case insentitive way:

- `NDX` : create a dice pool where `N` represents the number of dices and `X` the number of sides. If you want a fudgeDice `X` must be equal to `F` otherwise `X` must be an integer equal or greater than 2. If `X` is omitted this means you are requesting a 6 sides basic dice. If `N` is omitted this means that you are requestion a single dice.

The modifiers are appended to the dice annotation with the following rules:

For the arithmetic modifier:

- `oX` : where `o` represents the supported operators (`+`, `-`, `*`, `/`, `^`) and `X` a positive integer

**Only 2 arithmetic modifiers can be appended to a given dice pool.**

For the sorting modifier:

- `DHX`: drops the highest where `X` is the threshold value;
- `DLX`: drops the lowest where `X` is the threshold value;
- `KHX`: keeps the highest where `X` is the threshold value;
- `KLX`: keeps the lowest where `X` is the threshold value;

*If the `X` value is omitted it will default to `1`*

For the explode modifier:

- `!=X`: explodes the roll if `X` the threshold value is return by a rollable object;
- `!>X`: explodes the roll if `X` the threshold value is lesser than a rollable roll;
- `!<X`: keeps the lowest where `X` the threshold value is greater than a rollable roll;

*The `=` comparison sign can be omitted*

By applying these rules the `Factory` can construct the following `Rollable` object:

```php
<?php

use Ethtezahl\DiceRoller\Factory;

$cup = (new Factory())->newInstance('3D20+4+D4!>3/4^3');
echo $cup->roll();
```

if the `Factory` can not parse the submitted string a `Ethtezahl\DiceRoller\Exception` will be thrown.

**Happy Coding!**