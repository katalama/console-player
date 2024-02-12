<?php

declare(strict_types=1);

namespace Katalama\ConsolePlayer\Game;

use Katalama\ConsolePlayer\GameInterface;

class SnakeGame implements GameInterface
{
	private array $direction = [1, 0];
	private array $nextDirection = [1, 0];
	private readonly int $width;
	private readonly int $height;
	private int $speed = 10;
	private bool $isPaused = false;
	private bool $helpScreenWasShown = false;
	private float $previousSceneTime = 0;
	private int $headX = 0;
	private int $headY = 0;
	private array $field;
	private int $snakeLength = 1;
	private bool $gameIsOver = false;
	private array $log = [];
	private bool $dbg = false;
    private int $score = 0;

    public function getTitle(): ?string
    {
        return "Snake";
    }

    public function getDescription(): ?string
    {
        return "Classic 'snake' game. Terminal font should be square sized. ";
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
		$this->field[$this->headX][$this->headY] = $this->snakeLength;

		$this->addApple();
		$this->addApple();
		$this->addApple();
		$this->addApple();
	}
	
	private function addApple(): void
    {
		$appleX = random_int(0, $this->height);
		$appleY = random_int(0, $this->width);
		
		$variants = [0,0,1,0,-1,0];
		for ($i=0; $i<5; $i++) {
			$applePosX = $appleX + $variants[$i];
			$applePosY = $appleY + $variants[$i + 1];
			
			if ($this->field[$applePosX][$applePosY] === 0) {
				$this->field[$applePosX][$applePosY]  = -1;
				break;
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
		
		foreach ($this->field as $x => $row) {
			foreach ($row as $y => $val) {
				if ($val > 0) {
					// snake
					$this->field[$x][$y] = $val - 1;
				} elseif ($val < 0) {
					// apple
					// $this->field[$x][$y] = $val + 1;

					// if ($val === -1) {
					// 	$this->addApple();
					// }
				} else {
					// nothing
				}
			}
		}
		

		if ($this->field[$nextX][$nextY] > 0) {
			$this->gameOver();
		}

		if ($this->field[$nextX][$nextY] === -1) {
			++$this->snakeLength;
            ++$this->score;
			$this->addApple();
		}
		
		$this->headX = $nextX;
		$this->headY = $nextY;
		$this->field[$this->headX][$this->headY] = $this->snakeLength;
	}
	
	private function gameOver(): void
    {
		$this->gameIsOver = true;
	}
	
	private function asText(): string
	{
		$lines[] = "SCORE: "  . $this->score . " x \e[0;31m" . "\u{c4}" . "\e[0m";
		$lines[] = "\e[0;31m" . str_repeat("\u{c5}", $this->width) . "\e[0m";

		foreach ($this->field as $row) {
			$s = '';
			foreach ($row as $col => $val) {
				if ($val > 0) {
					// snake body
					$symbol = "\u{c5}";//'●';
					if ($val === $this->snakeLength) {
						$symbol = match ($this->direction) {
							[ 0,  1] => "\u{c2}",//"\u{25d0}",
							[ 0, -1] => "\u{c0}",//"\u{25d1}",
							[ 1,  0] => "\u{c1}",//"\u{25d3}",
							[-1,  0] => "\u{c3}",//"\u{25d2}",
						};
					}
					
					if ($val === 1) {
						// tail
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
				},
				'[arrow up] up',
			],
            chr(27) . chr(91) . chr(68) => [
				function () {
					if ($this->direction[1] === 0) {
						$this->nextDirection = [0, -1];
					}
				},
				'[left arrow] left',
			],
            chr(27) . chr(91) . chr(66) => [
                function () {
					if ($this->direction[0] === 0) {
						$this->nextDirection = [1, 0];
					}
				},
				'[down arrow] down',
			],
            chr(27) . chr(91) . chr(67) => [
				function () {
					if ($this->direction[1] === 0) {
						$this->nextDirection = [0, 1];
					}
				},
				'[right arrow] right',
			],
			'p' => [
				function () {
					$this->speed++;
				},
				'[p] speed up',
			],
			'o' => [
				function () {
					$this->speed = max($this->speed - 1, 1);
				},
				'[o] speed down',
			],
			'f' => [
				function () {
					$this->snakeLength++;
				},
				'[f] feed',
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
	}
	
	private function resume()
	{
		$this->isPaused = false;
		$this->previousSceneTime = microtime(true) - 1 / $this->speed;
	}
	
	private function getHelpScreen() {
		$lines = ['','','','','','','',''];
		$tab = str_repeat(' ', 8);
		foreach ($this->getKeyHandlers() as $key => [$handler, $help]) {
            $lines[] = $tab . $help;
        }
        $lines[] = $tab . str_repeat('=', $this->width - 8*2);
        $lines[] = $tab . "Score: " . $this->snakeLength;

		if ($this->dbg) {
			$lines[] = "headX: " . $this->headX;
			$lines[] = "headY: " . $this->headY;
			$lines[] = "snakeLength: " . $this->snakeLength;
			$lines[] = "lastKeyPressed: " . $this->snakeLength;
			$lines = array_merge($lines, $this->log);
		}
		
		return implode(PHP_EOL, $lines);
	}
	
	public function isStopped(): bool
	{
		return $this->gameIsOver;
	}
}
