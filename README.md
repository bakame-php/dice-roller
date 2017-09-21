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

Any object that can be rolled MUST implements the `Rollable` interface. Typically, dices, collection and modifiers all implement this interface.

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

The package comes bundles with the following rollable objects 

| Rollable Type | Class Name |
| ------------- | ---------- |
| Dice          | `Ethtezahl\DiceRoller\Dice` |
| Dice          | `Ethtezahl\DiceRoller\FudgeDice` |
| Collection    | `Ethtezahl\DiceRoller\Cup` |
| Modifier      | `Ethtezahl\DiceRoller\Modifier\Arithmetic` |
| Modifier      | `Ethtezahl\DiceRoller\Modifier\Explode` |
| Modifier      | `Ethtezahl\DiceRoller\Modifier\DropKeep` |


### Dices

In addition to the `Rollable` interface, the Dice type implement the `Countable` interface. The `count` method returns the dice sides count.

- The `Dice` constructor unique argument is the dice sides count. A `Dice` object must have at least 2 sides otherwise a `Ethtezahl\DiceRoller\Exception` is thrown.
- The `FludgeDice` constructor takes no argument as fudge dices are always 3 sides dices with values being `-1`, `0` or `1`.

```php
<?php

use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\FudgeDice;

$basic = new Dice(3);
echo $basic;    // 'D3';
$basic->roll(); // may return 1,2 or 3
count($basic);  // returns 3

$fugde = new FudgeDice();
echo $fudge;    // displays 'DF'
$fudge->roll(); // may return -1, 0, or 1
count($fudge);  // returns 3
```

### Dices Collection

A `Cup` is a collection of `Rollable` objects. This means that a `Cup` can contains multiple dices but others `Cup` objects as well. Once a `Cup` is instantiated there are no method to alter its properties.

```php
<?php

namespace Ethtezahl\DiceRoller;

final class Cup implements Countable, IteratorAggregate, Rollable
{
    public static function createFromDice(int $pQuantity, int|string $pSize): self;
    public function __construct(Rollable ...$pItems);
}
```

The `Cup::createFromDice` named constructor enables creating uniformed `Cup` object which contains only 1 type of simple rollable objects (ie: `Dice` or `FludgeDice`).

```php
<?php

use Ethtezahl\DiceRoller\Cup;

echo Cup::createFromDice(3, 5);   // displays 3D5
echo Cup::createFromDice(4, 'F'); // displays 4DF
echo Cup::createFromDice(2, 'f'); // displays 2DF
```

A Cup created using `createFromDice` must contain at least 1 `Rollable` object otherwise a `Ethtezahl\DiceRoller\Exception` is thrown.

Where iterating over a `Cup` object you will get access to all its inner `Rollable` objects.

```php
<?php

use Ethtezahl\DiceRoller\Cup;

foreach (Cup::createFromDice(3, 5) as $rollable) {
    echo $rollable; // will always return D5
}
```

### Roll Modifiers

Sometimes you may want to modify the outcome of a roll. The library comes bundle with 3 modifiers, each implementing the `Rollable` interface.

#### The Arithmetic modifier

```php
<?php

namespace Ethtezahl\DiceRoller\Modifier;

final class Arithmetic implements Rollable
{
    public function __construct(Rollable $rollable, string $operator, int $value);
}
```

This modifier decorates a `Rollable` object by applying an arithmetic operation on the submitted `Rollable` object.

The modifier supports the following operators:

- `+` addition;
- `-` substraction;
- `*` multiplication;
- `/` division;
- `^` exponentiation;

The value given must be a positive integer or 0. If the value or the operator are not valid a `Ethtezahl\DiceRoller\Exception` will be thrown.

```php
<?php

use Ethtezahl\DiceRoller\Modifier\Arithmetic;
use Ethtezahl\DiceRoller\Dice;

$modifier = new Arithmetic(new Dice(6), '*', 3);
echo $modifier; // displays D6*3;
$modifier->roll(); //may return 4*3 = 12
```

#### The DropKeep Modifier

```php
<?php

namespace Ethtezahl\DiceRoller\Modifier;

final class DropKeep implements Rollable
{
    const DROP_HIGHEST = 'dh';
    const DROP_LOWEST = 'dl';
    const KEEP_HIGHEST = 'kh';
    const KEEP_LOWEST = 'kl';
    public function __construct(Cup $pRollable, string $pAlgo, int $pThreshold);
}
```

This modifier decorates a `Rollable` object by applying the one of the dropkeep algorithm on a collection of `Rollable` objects. The constructor expects:

- a `Cup` object;
- a algorithm name;
- a threshold to trigger the alogrithm;

The supported algorithms are:

- `dh` or `DropKeep::DROP_HIGHEST` to drop the `$pThreshold` highest results of a given `Cup` object;
- `dl` or `DropKeep::DROP_LOWEST` to drop the `$pThreshold` lowest results of a given `Cup` object;
- `kh` or `DropKeep::KEEP_HIGHEST` to keep the `$pThreshold` highest results of a given `Cup` object;
- `kl` or `DropKeep::KEEP_LOWEST` to keep the `$pThreshold` lowest results of a given `Cup` object;

The `$pThreshold` MUST be lower or equals to the total numbers of rollable items in the `Cup` object.

If the algorithm or the threshold are not valid a `Ethtezahl\DiceRoller\Exception` will be thrown.

```php
<?php

use Ethtezahl\DiceRoller\Modifier\DropKeep;
use Ethtezahl\DiceRoller\Cup;

$modifier = new DropKeep(Cup::createFromDice(4, 6), DropKeep::DROP_HIGHEST, 3);
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
    public function __construct(Cup $pRollable, string $pCompare, int $pThreshold);
}
```

This modifier decorates a `Rollable` object by applying the one of the explode algorithm on a collection of `Rollable` objects. The constructor expects:

- a `Cup` object;
- a comparison operator string;
- a threshold to trigger the alogrithm;

The supported comparison operator are:

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
$modifier = new Explode($cup, Explode::EQUALS, 3);
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

The package comes bundles with a `Factory` class to ease `Rollable` instance creation. The factory supports basic roll annotation rules in a case insentitive way:


| Annotation | Examples  | Description |
| ---------- | -------- | -------- |
|  `NDX`     |  `3D4` |  create a dice pool where `N` represents the number of dices and `X` the number of sides. If you want a fudgeDice `X` must be equal to `F` otherwise `X` must be an integer equal or greater than 2. If `X` is omitted this means you are requesting a 6 sides basic dice. If `N` is omitted this means that you are requestion a single dice. |
| `oc`       | `^3`| where `o` represents the supported operators (`+`, `-`, `*`, `/`, `^`) and `c` a positive integer |
|  `!oc`     | `!>3` | an explode modifier where `o` represents one of the supported comparision operator (`>`, `<`, `=`)  and `c` a positive integer |
|  `[dh,dl,kh,kl]z` | `dh4` | keeping or dropping the lowest or highest z dice |



- **Only 2 arithmetic modifiers can be appended to a given dice pool.**  
- *The `=` comparison sign when using the explode modifier can be omitted*

By applying these rules the `Factory` can construct the following `Rollable` object:

```php
<?php

use Ethtezahl\DiceRoller\Factory;

$cup = (new Factory())->newInstance('3D20+4+D4!>3/4^3');
echo $cup->roll();
```

If the `Factory` is enable to parse the submitted dice annotation a `Ethtezahl\DiceRoller\Exception` will be thrown.  
Last but not least, if you prefer using function you can simply call the `roll_create` function defined in the `Ethtezahl\DiceRoller` namespace as follow:


```php
<?php

use Ethtezahl\DiceRoller;

$cup = DiceRoller\roll_create('3D20+4+D4!>3/4^3');
echo $cup->roll();
```


**Happy Coding!**