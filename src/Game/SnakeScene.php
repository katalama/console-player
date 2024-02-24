<?php

declare(strict_types=1);

namespace Katalama\ConsolePlayer\Game;

use Katalama\ConsolePlayer\Color;
use Katalama\ConsolePlayer\Pixel;
use Katalama\ConsolePlayer\SceneInterface;
use Katalama\ConsolePlayer\Symbol;

class SnakeScene implements SceneInterface
{
    public function __construct(
        private readonly array $gameState
    ) {
    }

    private function getFormattedTime(float $time, $precision = 2): string
    {
        $mod = pow(10, $precision);
        $time = round($time, 6);
        $afterPoint = floor($time * $mod) % $mod;
        $timeObject = \DateTime::createFromFormat('U.u', (string)$time);
        if (false === $timeObject) return '';

        return match (true) {
            $time < 60 => $timeObject->format('s') . '.' . $afterPoint,
            $time < 60*60 => $timeObject->format('i:s') . '.' . $afterPoint,
            true => $timeObject->format('H:i:s') . '.' . $afterPoint,
        };
    }

    /**
     * @param string $text
     * @return Pixel[]
     */
    private function transformText(string $text, Color $color = Color::DEFAULT, Color $bgColor = Color::DEFAULT): array
    {
        $pixels = [];

        foreach (mb_str_split($text) as $unicodeChar) {
            $pixels[] = new Pixel(Symbol::CHAR, $color, $bgColor, mb_ord($unicodeChar));
        }

        return $pixels;
    }

    /**
     * @param $state
     * @return Pixel[][]
     */
    public function getGameOverScreen($state): array
    {
        $lines[] = $this->transformText('      #####     ##           #    #    #####            ');
        $lines[] = $this->transformText('     #          # #         ##   ##    #                ');
        $lines[] = $this->transformText('     #   ###    ####       # #  # #    #####            ');
        $lines[] = $this->transformText('     #     #    #   #     #  # #  #    #                ');
        $lines[] = $this->transformText('      #####     #    #   #   #    #    #####            ');
        $lines[] = $this->transformText('                                                        ');
        $lines[] = $this->transformText('                  #####    ####                         ');
        $lines[] = $this->transformText('                    #     #                             ');
        $lines[] = $this->transformText('                    #       #                           ');
        $lines[] = $this->transformText('                    #         #                         ');
        $lines[] = $this->transformText('                  #####   ####                          ');
        $lines[] = $this->transformText('                                                        ');
        $lines[] = $this->transformText('           ####    #    #   #####   ####                ');
        $lines[] = $this->transformText('          #    #   #   #    #       #   #               ');
        $lines[] = $this->transformText('          #    #   #  #     #####   ####                ');
        $lines[] = $this->transformText('          #    #   # #      #       ##                  ');
        $lines[] = $this->transformText('           ####    #        #####   #  #                ');
        $lines[] = [];

        return $lines;
    }
    /**
     * @param $state
     * @return Pixel[][]
     */
    private function getHelpScreen($state): array
    {
        $lines = [[],[],[],[],[],[],[],[]];

        $tab = str_repeat(' ', 8);
        foreach ($state['handlers'] as [$key, $help]) {
            $lines[] = $this->transformText($tab . $help);
        }

        $lines[] = $this->transformText($tab . str_repeat('=', $state['width'] - 8*2));
        $lines[] = $this->transformText($tab . "Score: " . $state['score']);
        $lines[] = $this->transformText($tab . "Snake future/real length: " . $state['snakePotentialLength'] . " / " . $state['snakeLength']);
        $lines[] = $this->transformText($tab . "Time spent: " . $this->getFormattedTime($state['gameTime']));
        $lines[] = $this->transformText($tab . str_repeat('=', $state['width'] - 8*2));

        ksort($state['records']);
        $recordsToShow = 3;
        $lines[] = $this->transformText($tab . 'Time to next record:');
        foreach ($state['records'] as $score => $time) {
            if ($recordsToShow === 0) {
                break;
            }

            if ($score > $state['score']) {
                $timeToBeat = $time - $state['gameTime'];
                $lines[] = array_merge(
                    $this->transformText($tab . $tab . "[$score] " . $this->getFormattedTime(abs($timeToBeat), 3), ($timeToBeat ?? 0) < 0 ? Color::RED : Color::GREEN)
                );

                $recordsToShow--;
            }
        }
//        if ($this->dbg) {
//            $lines[] = "headX: " . $this->headX;
//            $lines[] = "headY: " . $this->headY;
//            $lines[] = "snakePotentialLength: " . $this->snakePotentialLength;
//            $lines[] = "snakeLength: " . $this->snakeLength;
//            $lines[] = "lastKeyPressed: " . $this->snakePotentialLength;
//            $lines = array_merge($lines, $this->log);
//        }


        return $lines;
    }

    public function getPixels(): array
    {
        $state = $this->gameState;

        if ($state['gameIsOver']) {
            return $this->getGameOverScreen($state);
        }

        if ($state['isPaused']) {
            return $this->getHelpScreen($state);
        }

        $timeToBeat = '*';
        if (!is_null($state['timeToBeat'])) {
            $timeToBeat = $this->getFormattedTime(abs($state['timeToBeat']));
        }

        $lines[] = array_merge(
            $this->transformText("SCORE: "  . $state['score'] . " x "), [new Pixel(Symbol::APPLE, Color::YELLOW)],
            $this->transformText("    SNAKE LENGTH: "  . $state['snakeLength'] . " x "), [new Pixel(Symbol::CIRCLE, Color::GREEN)],
            $this->transformText("    SPEED: "  . $state['speed']),
            $this->transformText("    TIME: "  . $state['time']),
            $this->transformText("    beat: "), $this->transformText($timeToBeat, ($state['timeToBeat'] ?? 0) < 0 ? Color::RED : Color::GREEN),
        );

        $lines[] = array_fill(0, $state['width'], new Pixel(Symbol::SQUARE));

        foreach ($state['field'] as $r => $row) {
            $s = [];
            foreach ($row as $c => $val) {
                if ($val > 0) {
                    // snake body
                    // $symbol = new Pixel((($r+$c)%2) ? Symbol::UPPER_SEMI_CIRCLE : Symbol::LOWER_SEMI_CIRCLE, Color::GREEN, Color::BLACK);
                    $symbol = new Pixel(Symbol::CIRCLE, Color::GREEN);
                    if ($val === $state['snakePotentialLength']) {
                        $symbol = new Pixel(match ($state['direction']) {
                            [ 0,  1] => Symbol::CIRCLE,
                            [ 0, -1] => Symbol::CIRCLE,
                            [ 1,  0] => Symbol::CIRCLE,
                            [-1,  0], [ 0,  0] => Symbol::CIRCLE,
                        });
                    }

                    $s[] = $symbol;
                } else if ($val === -1) {
                    // apple
                    $s[] = new Pixel(Symbol::APPLE, Color::RED);
                } else {
                    // nothing
                    $s[] = new Pixel(Symbol::SPACE, Color::BLACK);
                }
            }

            $lines[] = $s;
        }

        return $lines;
    }
}