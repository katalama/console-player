<?php

declare(strict_types=1);

namespace Katalama\ConsolePlayer\Game;

use Katalama\ConsolePlayer\GameInterface;

class SelectorApp implements GameInterface
{
    private bool $gameIsOver = false;
    private $selectedKey = 0;
    private bool $gameStarted = false;

    /**
     * @param GameInterface[] $games
     */
    public function __construct(private readonly array $games)
    {
    }

    public function init(
		int $width,
		int $height,
		int $fps
	): void {
        foreach ($this->games as $game) {
            $game->init($width, $height, $fps);
        }
    }
	
	private function asText(): string
	{
        $lines = [];
        $lines[] = 'This is selector app';
        $lines[] = 'Select game: ';

        foreach ($this->games as $key => $game) {
            $lines[] = ($this->selectedKey === $key ? '->' : '  ') . "$key: " . $game->getTitle() . " (" . $game->getDescription() . ")";
        }

		return implode(PHP_EOL, $lines);
	}
	
	public function nextScene(): ?string
	{
        if ($this->gameStarted) {
            return $this->games[$this->selectedKey]->nextScene();
        }

		return $this->asText();
	}
	
	public function handleKey(string $key): void
	{
        if ($this->gameStarted) {
            $this->games[$this->selectedKey]->handleKey($key);
            return;
        }

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
                    $this->selectedKey = ($this->selectedKey + 1) % count($this->games);
				},
				'[arrow up] up',
			],
            chr(27) . chr(91) . chr(68) => [
				function () {
				},
				'[left arrow] left',
			],
            chr(27) . chr(91) . chr(66) => [
                function () {
                    $this->selectedKey = (count($this->games) + $this->selectedKey - 1) % count($this->games);
				},
				'[down arrow] down',
			],
            chr(27) . chr(91) . chr(67) => [
				function () {
				},
				'[right arrow] right',
			],
			'p' => [
				function () {
                    $this->gameStarted = true;
				},
				'[p] play',
			],
			'q' => [
				function () {
					$this->gameOver();
				},
				'[q] quit',
			],
		];
	}

    public function gameOver(): void
    {
        $this->gameIsOver = true;
    }

	public function isStopped(): bool
	{
		return $this->gameIsOver;
	}

    public function getTitle(): ?string
    {
        return 'selector';
    }

    public function getDescription(): ?string
    {
        return 'Allows to choose game you like';
    }
}
