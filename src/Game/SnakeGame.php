<?php

declare(strict_types=1);

namespace Katalama\ConsolePlayer\Game;

use Katalama\ConsolePlayer\GameInterface;

class SnakeGame implements GameInterface
{
	private array $direction = [0, 0];
	private array $nextDirection = [0, 0];
	private readonly int $width;
	private readonly int $height;
	private int $speed = 10;
	private bool $isPaused = false;
	private bool $helpScreenWasShown = false;
	private float $previousSceneTime = 0;
	private int $headX = 0;
	private int $headY = 0;
	private array $field;
    private int $snakePotentialLength = 1;
    private int $snakeLength = 1;
	private bool $gameIsOver = false;
	private array $log = [];
	private bool $dbg = false;
    private int $score = 0;
    private ?float $startTime = null;
    private float $pausedTime = 0;
    private int $apples = 0;
    private array $records;

    public function getTitle(): ?string
    {
        return "Snake";
    }

    public function getDescription(): ?string
    {
        return "Classic 'snake' game. Terminal font should be square-sized.";
    }

	public function init(
		int $width,
		int $height,
		int $fps
	): void {
		$this->width = $width;
		$this->height = $height - 2;
		$this->previousSceneTime = microtime(true) - 1/$this->speed;
		
		$this->field = array_fill(0, $this->height, array_fill(0, $this->width, 0));
		$this->field[$this->headX][$this->headY] = $this->snakePotentialLength;

		$this->addApple();
		$this->addApple();
		$this->addApple();
		$this->addApple();

        $this->loadRecords();
	}

    private function loadRecords(): void
    {
        try {
            $this->records = unserialize(file_get_contents("./records.txt"));
        } catch (\Throwable $e) {
            $this->records = [];
        }
    }

    private function saveRecords(): void
    {
        file_put_contents("./records.txt", serialize($this->records));
    }

	private function addApple(): void
    {
		$nextApplePos = random_int(0, $this->height * $this->width - $this->snakeLength - $this->apples);

        for ($i = 0; $i < $this->height; $i++) {
            for ($j = 0; $j < $this->width; $j++) {
                if ($this->field[$i][$j] === 0) {
                    $nextApplePos--;
                }

                if ($nextApplePos === 0) {
                    $this->field[$i][$j] = -1;
                    $this->apples += 1;
                    return;
                }
            }
        }
	}
	
	private function recalc(): void
	{
        $this->direction = $this->nextDirection;

		$nextX = $this->headX + $this->direction[0] + $this->height;
		$nextX %= $this->height;
		
		$nextY = $this->headY + $this->direction[1] + $this->width;
		$nextY %= $this->width;

        $snakeLengthDidntGrow = false;
		foreach ($this->field as $x => $row) {
			foreach ($row as $y => $val) {
				if ($val > 0) {
                    // if tail will be shorter, then snake length still the same
					if ($val === 1) {
                        $snakeLengthDidntGrow = true;
                    }

					$this->field[$x][$y] = $val - 1;
				}
			}
		}

        if (!$snakeLengthDidntGrow) {
            $this->snakeLength++;
        }

		if ($this->field[$nextX][$nextY] > 0) {
			$this->gameOver();
		}

		if ($this->field[$nextX][$nextY] === -1) {
            ++$this->snakePotentialLength;
            ++$this->score;
            $this->addRecord();
            $this->saveRecords();
			$this->addApple();
		}
		
		$this->headX = $nextX;
		$this->headY = $nextY;
		$this->field[$this->headX][$this->headY] = $this->snakePotentialLength;
	}
	
	private function gameOver(): void
    {
		$this->gameIsOver = true;
	}

    private function getGameTime(): float
    {
        if ($this->startTime === null) {
            return 0.01;
        }

        $finishTime = microtime(true);
        if ($this->isPaused) {
            $finishTime = $this->pausedTime;
        }

        return round($finishTime - $this->startTime, 6) + 0.01;
    }

    private function getFormattedTime(float $time): string
    {
        $time = round($time, 6);
        $afterPoint = floor($time * 10) % 10;
        $time = \DateTime::createFromFormat('U.u', (string)$time);
        return match (true) {
            $time < 60 => $time->format('s') . '.' . $afterPoint,
            $time < 60*60 => $time->format('i:s') . '.' . $afterPoint,
            true => $time->format('H:i:s') . '.' . $afterPoint,
        };
    }

	private function asText(): string
	{
        $time = $this->getFormattedTime($this->getGameTime());
        $timeToBeat = $this->getTimeToBeat();
		$lines[] = "SCORE: "  . $this->score . " x \e[0;31m" . "\u{c4}" . "\e[0m"
            // . "    SNAKE LENGTH: "  . $this->snakeLength . " x \e[0;31m" . "\u{c4}"
            . "    SPEED: "  . $this->speed
            . "    TIME: "  . $time
            . "    beat: "  . (!is_null($timeToBeat) ? ($timeToBeat < 0 ? "\e[0;31m" : "\e[0;32m") . $this->getFormattedTime(abs($timeToBeat)) . "\e[0m" : '*');

		$lines[] = "\e[0;31m" . str_repeat("\u{c5}", $this->width) . "\e[0m";

		foreach ($this->field as $row) {
			$s = '';
			foreach ($row as $col => $val) {
				if ($val > 0) {
					// snake body
					$symbol = "\u{c5}";//'●';
					if ($val === $this->snakePotentialLength) {
						$symbol = match ($this->direction) {
							[ 0,  1] => "\u{c2}",//"\u{25d0}",
							[ 0, -1] => "\u{c0}",//"\u{25d1}",
							[ 1,  0] => "\u{c1}",//"\u{25d3}",
							[-1,  0], [ 0,  0] => "\u{c3}",//"\u{25d2}",
						};
					}
					
					$s .= "\e[0;32m" . $symbol . "\e[0m";
                } else if ($val === -1) {
                    // apple
                    $s .= "\e[0;31m" . "\u{c4}" . "\e[0m";
				} else {
					// nothing
					$s .= ' ';
				}
			}
			$lines[] = $s;
		}
		
		return implode(PHP_EOL, $lines);
	}
	
	public function nextScene(): ?string
	{
		if ($this->isPaused) {
			if (!$this->helpScreenWasShown) {
				$this->helpScreenWasShown = true;
				return $this->getHelpScreen();
			} else {
				return null;
			}
		}
		
		$time = microtime(true);
		
		if ($time - $this->previousSceneTime < 1/$this->speed) {
			return null; 
		}
		
		while ($time - $this->previousSceneTime >= 1/$this->speed) {
			$this->recalc();
			$this->previousSceneTime += 1/$this->speed;
		}
		
		return $this->asText();
	}
	
	public function handleKey(string $key): void
	{
		$this->log[] = "Pressed key: [$key]";
		[$handler, $_] = $this->getKeyHandlers()[$key] ?? null;
		
		if (null !== $handler) {
			$handler();
		}
	}
	
	private function getKeyHandlers(): array
	{
		return [
			chr(27) . chr(91) . chr(65) => [
				function () {
					if ($this->direction[0] === 0) {
						$this->nextDirection = [-1, 0];
					}

                    if ($this->startTime === null) {
                        $this->startTime = microtime(true);
                    }
				},
				'[arrow up] up',
			],
            chr(27) . chr(91) . chr(68) => [
				function () {
					if ($this->direction[1] === 0) {
						$this->nextDirection = [0, -1];
					}

                    if ($this->startTime === null) {
                        $this->startTime = microtime(true);
                    }
				},
				'[left arrow] left',
			],
            chr(27) . chr(91) . chr(66) => [
                function () {
					if ($this->direction[0] === 0) {
						$this->nextDirection = [1, 0];
					}

                    if ($this->startTime === null) {
                        $this->startTime = microtime(true);
                    }
				},
				'[down arrow] down',
			],
            chr(27) . chr(91) . chr(67) => [
				function () {
					if ($this->direction[1] === 0) {
						$this->nextDirection = [0, 1];
					}

                    if ($this->startTime === null) {
                        $this->startTime = microtime(true);
                    }
				},
				'[right arrow] right',
			],
			'w' => [
				function () {
					$this->speed++;
				},
				'[w] speed up',
			],
			's' => [
				function () {
					$this->speed = max($this->speed - 1, 1);
				},
				'[s] speed down',
			],
			chr(27) => [
				function () {
					$this->isPaused ? $this->resume() : $this->pause();
				},
				'[ESC] pause',
			],
			'q' => [
				function () {
					$this->gameOver();
				},
				'[q] quit',
			],
		];
	}
	
	private function pause()
	{
		$this->isPaused = true;
		$this->helpScreenWasShown = false;
        $this->pausedTime = microtime(true);
	}
	
	private function resume()
	{
		$this->isPaused = false;
		$this->previousSceneTime = microtime(true) - 1 / $this->speed;
        $this->startTime += microtime(true) - $this->pausedTime;
	}
	
	private function getHelpScreen() {
		$lines = ['','','','','','','',''];
		$tab = str_repeat(' ', 8);
		foreach ($this->getKeyHandlers() as $key => [$handler, $help]) {
            $lines[] = $tab . $help;
        }
        $lines[] = $tab . str_repeat('=', $this->width - 8*2);
        $lines[] = $tab . "Score: " . $this->score . " (" . $this->snakeLength . ")";
        $lines[] = $tab . "Snake future/real length: " . $this->snakePotentialLength . " / " . $this->snakeLength;
        $lines[] = $tab . "Time spent: " . $this->getFormattedTime($this->getGameTime());

		if ($this->dbg) {
			$lines[] = "headX: " . $this->headX;
			$lines[] = "headY: " . $this->headY;
			$lines[] = "snakePotentialLength: " . $this->snakePotentialLength;
			$lines[] = "snakeLength: " . $this->snakeLength;
			$lines[] = "lastKeyPressed: " . $this->snakePotentialLength;
			$lines = array_merge($lines, $this->log);
		}
		
		return implode(PHP_EOL, $lines);
	}
	
	public function isStopped(): bool
	{
		return $this->gameIsOver;
	}

    private function addRecord(): void
    {
        $this->records[$this->score] = min($this->records[$this->score] ?? 1e100, $this->getGameTime());
    }

    private function getTimeToBeat(): ?float
    {
        if (!isset($this->records[$this->score + 1])) return null;

        return $this->records[$this->score + 1] - $this->getGameTime();
    }
}
