# RPG Dice Roller

## Concept.

I wanted to code a program which create stats for pre-rolled characters for rpg "Call of Cthulhu".
I needed a library that can simulate and give result of a roll of multiple dice (sometimes with a different number of sides).
So I created this library, to make these rolls.

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

## Advanced use: with multiple types of dices

The following expression is supported by the library:

```php
$cup = $factory->newInstance('3D20+4+D4!>3/4^3');
echo $cup->roll();
```
