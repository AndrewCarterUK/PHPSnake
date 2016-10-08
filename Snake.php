<?php

class Snake
{
  const UP = 'up';
  const DOWN = 'down';
  const LEFT = 'left';
  const RIGHT = 'right';

  const COLOUR_TEXT = "\033[31m";
  const COLOUR_FOOD = "\033[32m";
  const COLOUR_SNAKE_1 = "\033[33m";
  const COLOUR_SNAKE_2 = "\033[34m";

  const DURATION = 100;

  private $running;
  private $width;
  private $height;
  private $players;
  private $keepAlive;
  private $secondsRemaining;
  private $snakes;
  private $directions;
  private $buffer;
  private $food;
  private $loser;

  public function __construct($players = 1, $keepAlive = false)
  {
    $this->width  = (int) exec('tput cols');
    $this->height = (int) exec('tput lines') - 1;
    $this->players = $players;
    $this->keepAlive = $keepAlive;
  }

  public function run()
  {
    system('stty -icanon -echo');

    $multi = $this->players > 1;

    while (1) {
      $this->game();

      system('clear');

      echo self::COLOUR_TEXT;

      if ($multi) {
        if ($this->loser !== NULL) {
          echo 'Player ' . ($this->loser + 1) . ' crashed!' . PHP_EOL . PHP_EOL;
        }

        echo 'Scores:' . PHP_EOL;

        foreach ($this->snakes as $key => $snake) {
          echo "\t" . 'Player ' . ($key + 1) . ': ' . count($snake);
        }
      } else {
        echo 'You crashed!' . PHP_EOL . PHP_EOL;
        echo 'Score: ' . count($this->snakes[0]) . PHP_EOL;
      }

      sleep(4);
    }
  }

  public function game()
  {
    $this->init();

    $loop = React\EventLoop\Factory::create();

    $stdin = fopen('php://stdin', 'r');
    stream_set_blocking($stdin, 0);

    while (fgetc($stdin));

    $loop->addReadStream($stdin, function ($stdin) {
      $key = ord(fgetc($stdin));

      if (27 === $key) {
        fgetc($stdin);
        $key = ord(fgetc($stdin));
      }

      switch ($key) {
        case 65: case ord('8'): $this->setDirection(Snake::UP);    break;
        case 66: case ord('2'): $this->setDirection(Snake::DOWN);  break;
        case 68: case ord('4'): $this->setDirection(Snake::LEFT);  break;
        case 67: case ord('6'): $this->setDirection(Snake::RIGHT); break;
        case ord('w'): $this->setDirection(Snake::UP, 1);    break;
        case ord('s'): $this->setDirection(Snake::DOWN, 1);  break;
        case ord('a'): $this->setDirection(Snake::LEFT, 1);  break;
        case ord('d'): $this->setDirection(Snake::RIGHT, 1); break;
      }
    });

    $loop->addPeriodicTimer(0.1, function () use ($loop) {
      $active = $this->step();
      $this->render();
      $this->print();

      if (!$active) {
        $loop->stop();
      }
    });

    if ($this->keepAlive && $this->players > 1) {
      $loop->addPeriodicTimer(1, function () use ($loop) {
        if ($this->running) {
          $this->secondsRemaining--;

          if ($this->secondsRemaining < 0) {
            $loop->stop();
          }
        }
      });
    }

    $loop->run();
  }

  public function init()
  {
    $this->loser = NULL;
    $this->running = false;

    $this->secondsRemaining = self::DURATION;

    $this->directions = $this->snakes = [];

    for ($i = 0; $i < $this->players; $i++) {
      $this->directions[] = NULL;

      $this->snakes[] = [[
        (int) (($i + 1) * ($this->width  / ($this->players + 1))),
        (int) ($this->height / 2),
      ]];
    }

    $this->newFood();
  }

  public function setDirection($direction, $snake = 0)
  {
    $this->running = true;

    if (count($this->snakes[$snake]) > 1) {
      switch ($this->directions[$snake]) {
        case self::UP:    if ($direction == self::DOWN)  return; break;
        case self::DOWN:  if ($direction == self::UP)    return; break;
        case self::LEFT:  if ($direction == self::RIGHT) return; break;
        case self::RIGHT; if ($direction == self::LEFT)  return; break;
      }
    }

    $this->directions[$snake] = $direction;
  }

  public function newFood()
  {
    do {
      $this->food = [
        mt_rand(0, $this->width - 1),
        mt_rand(0, $this->height - 1),
      ];
    } while (!$this->noFoodCollision());
  }

  public function noFoodCollision()
  {
    foreach ($this->snakes as $snake) {
      foreach ($snake as $point) {
        if ($this->pointIsSame($point, $this->food)) {
          return false;
        }
      }
    }

    return true;
  }

  public function pointIsSame($x, $y)
  {
    return $x[0] === $y[0] && $x[1] === $y[1];
  }

  public function step()
  {
    foreach ($this->snakes as $key => $snake) {
      if (!$this->snakeStep($key)) {
        return false;
      }
    }

    return true;
  }

  public function snakeStep($key)
  {
    if ($this->directions[$key] === NULL) {
      return true;
    }

    $newPoint = $this->snakes[$key][count($this->snakes[$key]) - 1];

    switch ($this->directions[$key]) {
      case self::UP:    $newPoint[1]--; break;
      case self::DOWN:  $newPoint[1]++; break;
      case self::LEFT:  $newPoint[0]--; break;
      case self::RIGHT: $newPoint[0]++; break;
    }

    foreach ($this->snakes as $snake) {
      foreach ($snake as $point) {
        if ($this->pointIsSame($point, $newPoint)) {
          if ($this->keepAlive) {
            $this->snakes[$key] = [$this->snakes[$key][0]];
            break;
          } else {
            $this->loser = $key;
            return false;
          }
        }
      }
    }

    $bounce = false;

    if (
      $newPoint[0] < 0 || $newPoint[0] >= $this->width  ||
      $newPoint[1] < 0 || $newPoint[1] >= $this->height
    ) {
      if ($this->keepAlive) {
        if ($newPoint[0] < 0) $newPoint[0] = $this->width  - 1;
        if ($newPoint[1] < 0) $newPoint[1] = $this->height - 1;
        if ($newPoint[0] >= $this->width)  $newPoint[0] = 0;
        if ($newPoint[1] >= $this->height) $newPoint[1] = 0;
      } else {
        $this->loser = $key;
        return false;
      }
    }

    $this->snakes[$key][] = $newPoint;

    if ($this->pointIsSame($newPoint, $this->food)) {
      $this->newFood();
    } else {
      array_shift($this->snakes[$key]);
    }

    return true;
  }

  public function render()
  {
    $this->renderBackground();
    $this->renderSnake();
    $this->renderFood();
  }

  public function renderBackground()
  {
    $this->buffer = [];

    for ($j = 0; $j < $this->height; $j++) {
      $this->buffer[$j] = [];

      for ($i = 0; $i < $this->width; $i++) {
        $this->buffer[$j][$i] = ' ';
      }
    }
  }

  public function renderSnake()
  {
    foreach ($this->snakes as $player => $snake) {
      $colour = $player ? self::COLOUR_SNAKE_1 : self::COLOUR_SNAKE_2;

      foreach ($snake as $key => $point) {
        if ($key === count($snake) - 1) {
          $char = $colour . "\xF0\x9F\x98\x88";
        } /* elseif ($key == 0) { // TAIL
          $char = "\xE2\x9D\x9A";
        } */ else {
          $char = $colour . "\xE2\x96\x88";
        }

        $this->buffer[$point[1]][$point[0]] = $char;
      }
    }
  }

  public function renderFood()
  {
    $this->buffer[$this->food[1]][$this->food[0]] = self::COLOUR_FOOD . "\xF0\x9F\x8D\xB2";
  }

  public function print()
  {
    echo "\033[J";

    for ($y = 0; $y < $this->height; $y++) {
      $line = implode('', $this->buffer[$y]);
      echo PHP_EOL . $line;
    }

    echo self::COLOUR_TEXT;

    foreach ($this->snakes as $key => $snake) {
      echo 'Player ' . ($key + 1) . ': ' . count($snake) . "\t";
    }

    if ($this->secondsRemaining < self::DURATION) {
      echo "\t" . $this->secondsRemaining;
    }

    echo "\e[?25l";
  }
}
