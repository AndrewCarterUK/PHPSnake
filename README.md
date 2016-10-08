# PHP Snake

By [AndrewCarterUK ![(Twitter)](http://i.imgur.com/wWzX9uB.png)](https://twitter.com/AndrewCarterUK)

This game was created as a rule breaking entry to the Bulgaria PHP Conference 2016 Hackathon. Joshua Thijssen assisted with the development of this entry, but also got interested enough to make his own (awesome) [PHP snake game](https://github.com/jaytaph/bgphpsnake).

![Screenshot](http://res.cloudinary.com/andrewcarteruk/image/upload/v1475955502/PHP%20Snake.png)

## Setup

Using [composer](https://getcomposer.org):

```
composer install
```

## Multi Player Mode

```sh
php play.php mk
```

In this mode the **walls are not boundaries**, if you crash into the other player or yourself you will lose the whole of your snake.

The arrow keys control one of the snakes and the 'A', 'S', 'D' and 'W' keys control the other.

You have 100 seconds to play the game, the winner is the player with the longest snake at the end.

## Single Player Mode

```sh
php play.php
```

In this mode the **walls are boundaries**, if you crash into the wall or your own snake the game is over.

## Other Modes

The remaining two modes are possible, but not really any fun.

### Single Player (Keep Alive) Mode

```sh
php play.php k
```

### Multi Player (Death) mode

```sh
php play.php m
```
