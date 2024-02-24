<?php

namespace Katalama\ConsolePlayer;

class Pixel
{
    public function __construct(
        private Symbol $symbol,
        private Color      $color = Color::DEFAULT,
        private Color      $bgColor = Color::DEFAULT,
        private ?int       $value = null
    ) {
    }

    public function getSymbol(): Symbol
    {
        return $this->symbol;
    }

    public function getColor(): Color
    {
        return $this->color;
    }

    public function getBgColor(): Color
    {
        return $this->bgColor;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }
}