<?php

declare(strict_types=1);

namespace Katalama\ConsolePlayer;

interface GameInterface
{
    public function getTitle(): ?string;

    public function getDescription(): ?string;

	public function init(int $width, int $height, int $fps): void;

    public function nextScene(): ?SceneInterface;

	public function handleKey(string $key): void;

	public function isStopped(): bool;
}
