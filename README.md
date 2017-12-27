# Dice Roller

## Concept.

A simple Dice Roller implemented in PHP

## System Requirements

You need **PHP >= 7.0.0** but the latest stable version of PHP is recommended.

## Installation

```bash
$ composer require bakame-php/dice-roller
```

## Basic usage

The code above will simulate the roll of two six-sided die

```php
<?php

// First: import needed namespace
use Bakame\DiceRoller;

// We create the cup that will contain the two die:
$cup = DiceRoller\create('2D6');

// Display the result:
echo $cup->roll();
```

## Advanced use: with multiple types of dices and modifiers

The following expression is supported by the library:

```php
$cup = DiceRoller\create('3D20+4+D4!>3/4^3');
echo $cup->roll();     // returns 48
echo $cup->getTrace(); // returns ((20 + 8 + 16) + 4) + 3 / 4 ^ 3
```

## Documentation

### Rollable

Any object that can be rolled MUST implements the `Rollable` interface. Typically, dices, collection and modifiers all implement this interface.

```php
<?php

namespace Bakame\DiceRoller;

interface Rollable
{
    public function getTrace(): string;
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
- `Rollable::getTrace` returns the execution trace of how the last roll was executed.

The package comes bundles with the following rollable objects

| Rollable Type | Class Name |
| ------------- | ---------- |
| Dice          | `Bakame\DiceRoller\Dice` |
| Dice          | `Bakame\DiceRoller\FudgeDice` |
| Dice          | `Bakame\DiceRoller\PercentileDice` |
| Dice          | `Bakame\DiceRoller\CustomDice` |
| Collection    | `Bakame\DiceRoller\Cup` |
| Modifier      | `Bakame\DiceRoller\Modifier\Arithmetic` |
| Modifier      | `Bakame\DiceRoller\Modifier\Explode` |
| Modifier      | `Bakame\DiceRoller\Modifier\DropKeep` |

### Dices

In addition to the `Rollable` interface, the Dice type implement the `Countable` interface. The `count` method returns the dice sides count.

 A `Dice` type object must have at least 2 sides otherwise a `Bakame\DiceRoller\Exception` exception is thrown.

- The `Dice` constructor unique argument is the dice sides count.
- The `CustomDice` constructor takes a variadic argument which represents the dice side values.
- The `FludgeDice` constructor takes no argument as fudge dices are always 3 sides dices with values being `-1`, `0` or `1`.
- The `PercentileDice` constructor takes no argument as percentile dices are always 100 sides dices with values between `1` and `100`.

```php
<?php

use Bakame\DiceRoller\CustomDice;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\FudgeDice;
use Bakame\DiceRoller\PercentileDice;

$basic = new Dice(3);
echo $basic;    // 'D3';
$basic->roll(); // may return 1, 2 or 3
count($basic);  // returns 3

$custom = new CustomDice(3, 2, 1, 1);
echo $custom;    // 'D[3,2,1,1]';
$custom->roll(); // may return 1, 2 or 3
count($custom);  // returns 4

$fugde = new FudgeDice();
echo $fudge;    // displays 'DF'
$fudge->roll(); // may return -1, 0, or 1
count($fudge);  // returns 3

$percentile = new PercentileDice();
echo $percentile;    // displays 'D%'
$percentile->roll(); // returns a value between 1 and 100
count($fudge);       // returns 100
```

### Dices Collection

A `Cup` is a collection of `Rollable` objects. This means that a `Cup` can contains multiple dices but others `Cup` objects as well.

```php
<?php

namespace Bakame\DiceRoller;

final class Cup implements Countable, IteratorAggregate, Rollable
{
    public static function createFromRollable(int $pQuantity, Rollable $rollable): self;
    public function __construct(Rollable ...$rollables);
    public function withRollable(Rollable $rollable);
}
```

The `Cup::createFromRollable` named constructor enables creating uniformed `Cup` object which contains only 1 type of rollable objects.

```php
<?php

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\PercentileDice;
use Bakame\DiceRoller\Cup;

echo Cup::createFromRollable(3, new Dice(5));                // displays 3D5
echo Cup::createFromRollable(4, new PercentileDice());       // displays 4D%
echo Cup::createFromRollable(2, new CustomDice(1, 2, 2, 4)); // displays 2D[1,2,2,4]
echo Cup::createFromRollable(1, new FudgeDice());            // displays DF
```

A Cup created using `createFromRollable` must contain at least 1 `Rollable` object otherwise a `Bakame\DiceRoller\Exception` is thrown.

When iterating over a `Cup` object you will get access to all its inner `Rollable` objects.

```php
<?php

use Bakame\DiceRoller\Cup;

foreach (Cup::createFromRollable(3, new Dice(5)) as $rollable) {
    echo $rollable; // will always return D5
}
```

Once a `Cup` is instantiated there are no method to alter its properties. However the `Cup::withRollable` method enables you to build complex `Cup` object using the builder. The method will always returns a new `Cup` object but with the added `Rollable` object while maintaining the state of the current `Cup` object.


```php
<?php

use Bakame\DiceRoller\Cup;

$cup = Cup::createFromRollable(3, new Dice(5));
count($cup); //returns 3 the number of dices
echo $cup;   //returns 3D5

$alt_cup = $cup->withRollable(new FugdeDice());
count($alt_cup); //returns 4 the number of dices
echo $alt_cup;   //returns 3D5+DF
```

### Roll Modifiers

Sometimes you may want to modify the outcome of a roll. The library comes bundle with 3 modifiers, each implementing the `Rollable` interface.

#### The Arithmetic modifier

```php
<?php

namespace Bakame\DiceRoller\Modifier;

final class Arithmetic implements Rollable
{
    const ADDITION = '+';
    const SUBSTRACTION = '-';
    const MULTIPLICATION = '*';
    const DIVISION = '/';
    const EXPONENTIATION = '^';

    public function __construct(Rollable $rollable, string $operator, int $value);
}
```

This modifier decorates a `Rollable` object by applying an arithmetic operation on the submitted `Rollable` object.

The modifier supports the following operators:

- `+` or `Arithmetic::ADDITION`;
- `-` or `Arithmetic::SUBSTRACTION`;
- `*` or `Arithmetic::MULTIPLICATION`;
- `/` or `Arithmetic::DIVISION`;
- `^` or `Arithmetic::EXPONENTIATION`;

The value given must be a positive integer or 0. If the value or the operator are not valid a `Bakame\DiceRoller\Exception` will be thrown.

```php
<?php

use Bakame\DiceRoller\Modifier\Arithmetic;
use Bakame\DiceRoller\Dice;

$modifier = new Arithmetic(new Dice(6), Arithmetic::MULTIPLICATION, 3);
echo $modifier;        // displays D6*3;
$modifier->roll();     //may return 12
$modifier->getTrace(); //may return 4 * 3
```

#### The DropKeep Modifier

```php
<?php

namespace Bakame\DiceRoller\Modifier;

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

If the algorithm or the threshold are not valid a `Bakame\DiceRoller\Exception` will be thrown.

```php
<?php

use Bakame\DiceRoller\Modifier\DropKeep;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice;

$cup = Cup::createFromRollable(4, new Dice(6));
$modifier = new DropKeep($cup, DropKeep::DROP_HIGHEST, 3);
echo $modifier; // displays '4D6DH3'
```

#### The Explode Modifier

```php
<?php

namespace Bakame\DiceRoller\Modifier;

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

If the comparison operator is not recognized a `Bakame\DiceRoller\Exception` will be thrown.

```php
<?php

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\Modifier\Explode;
use Bakame\DiceRoller\FudgeDice;

$cup = new Cup(new Dice(6), new FudgeDice(), new Dice(6), new Dice(6));
$modifier = new Explode($cup, Explode::EQUALS, 3);
echo $modifier; // displays (3D6+DF)!=3
```

### Parser

```php
<?php

namespace Bakame\DiceRoller;

final class Parser
{
    public function __invoke(string $annotation): Rollable;
    public function parse(string $annotation): Rollable;
}
```

The package comes bundles with a `Parser` class to ease `Rollable` instance creation. The parser supports basic roll annotation rules in a case insentitive way:


| Annotation | Examples  | Description |
| ---------- | -------- | -------- |
|  `NDX`     |  `3D4` |  create a dice pool where `N` represents the number of dices and `X` the number of sides. If `X` is omitted this means you are requesting a 6 sides basic dice. If `N` is omitted this means that you are requestion a single dice. |
|  `NDF`     |  `3DF` |  create a dice pool where `N` represents the number of fudge dices. If `N` is omitted this means that you are requestion a single fugde dice. |
|  `ND%`     |  `3D%` |  create a dice pool where `N` represents the number of percentile dices. If `N` is omitted this means that you are requestion a single percentile dice. |
|  `ND[x,x,x,x,...]`     |  `2D[1,2,2,5]` |  create a dice pool where `N` represents the number of custom dices and `x` the value of a specific dice side. The number of `x` represents the side count. If `N` is omitted this means that you are requestion a single custome dice. a Custom dice must contain at least 2 sides. |
| `oc`       | `^3`| where `o` represents the supported operators (`+`, `-`, `*`, `/`, `^`) and `c` a positive integer |
|  `!oc`     | `!>3` | an explode modifier where `o` represents one of the supported comparision operator (`>`, `<`, `=`)  and `c` a positive integer |
|  `[dh,dl,kh,kl]z` | `dh4` | keeping or dropping the lowest or highest z dice |


- **Only 2 arithmetic modifiers can be appended to a given dice pool.**  
- *The `=` comparison sign when using the explode modifier can be omitted*

By applying these rules the `Parser` can construct the following `Rollable` object:

```php
<?php

use Bakame\DiceRoller\Parser;

$cup = (new Parser())->parse('3D20+4+D4!>3/4^3');
//or
$cup = (new Parser())('3D20+4+D4!>3/4^3'); //using the __invoke method

echo $cup->roll();
```

If the `Parser` is not able to parse the submitted dice annotation a `Bakame\DiceRoller\Exception` will be thrown.  
Last but not least, if you prefer using function you can simply call the `create` function defined in the `Bakame\DiceRoller` namespace as follow:


```php
<?php

use Bakame\DiceRoller;

$cup = DiceRoller\create('3D20+4+D4!>3/4^3');
echo $cup->roll();
```

**Happy Coding!**