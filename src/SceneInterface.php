<?php

declare(strict_types=1);

namespace Katalama\ConsolePlayer;

interface SceneInterface
{
    /**
     * @return Pixel[][]
     */
    public function getPixels(): array;
}