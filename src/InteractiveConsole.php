<?php

declare(strict_types=1);

namespace Katalama\ConsolePlayer;

use Katalama\ConsolePlayer\Game\SelectorApp;
use Katalama\ConsolePlayer\Game\SnakeGame;

class InteractiveConsole
{
    public function __construct(
        private readonly int $width,
        private readonly int $height,
        private int $fps = 60
    ) {
        system('stty -icanon -echo');
        system('tput civis');
    }
    
    public function run(?GameInterface $game = null): void
    {
        if (null === $game) {
            $game = new SnakeGame();
//            $game = new SelectorApp([
//                new SnakeGame(),
//                new SnakeGame()
//            ]);
        }

        $game->init($this->width>>1, $this->height, $this->fps);
        $usleep = 1 / $this->fps;
        $readKeyTimeout = (int)($usleep * 1000);
        
        $time = microtime(true) - $usleep;

        stream_set_blocking(STDIN, false);
        while (!$game->isStopped()) {
            if (microtime(true) - $time >= $usleep) {
                $time = microtime(true);
                $this->draw($game);
                
                usleep(1000);
            } else {
                [$rss, $wss, $ess] = [[STDIN], null, null];
                if (@stream_select($rss, $wss, $ess, 0, $readKeyTimeout)) {
                    $s = stream_get_contents(STDIN);
                    $game->handleKey($s);
                    $this->draw($game);
                }
            }
        }

        $this->draw($game);
        system('stty icanon echo');
        system('tput cnorm');
        echo "\n";
    }
    
    private function draw(GameInterface $game)
    {
        $scene = $game->nextScene();

        if (null !== $scene) {
            system('clear');

            $s = '';
            foreach ($scene->getPixels() as $row) {
                foreach ($row as $pixel) {
                    switch ($pixel->getSymbol()) {
                        case Symbol::CHAR:
                            $c = mb_chr($pixel->getValue());
                            break;
                        case Symbol::SPACE:
                            $c = ' ' . ' ';
                            break;
                        case Symbol::CIRCLE:
//                            $c = mb_chr(0xa609) . mb_chr(0xa609);
                            $c = mb_chr(0x2987) . mb_chr(0x2988);
//                            $c = mb_chr(0x1462) . mb_chr(0x145d);
                            break;
                        case Symbol::RING:
                            $c = mb_chr(0x2768) . mb_chr(0x2769);
                            break;
                        case Symbol::APPLE:
                            $c = mb_chr(0x2987) . mb_chr(0x2988) . mb_chr(0x0484);
                            break;
                        case Symbol::UPPER_SEMI_CIRCLE:
                            $c = mb_chr(0x25dc) . mb_chr(0x25dd);
                            break;
                        case Symbol::LOWER_SEMI_CIRCLE:
                            $c = mb_chr(0x25df) . mb_chr(0x25de);
                            break;
                        case Symbol::SQUARE:
                            $c = mb_chr(0x2588) . mb_chr(0x2588);
//                            $c = mb_chr(0x275a) . mb_chr(0x275a);
//                            $c = mb_chr(0x15ed) . mb_chr(0x15ea);
                            break;
                        case Symbol::NEW_LINE:
                            $c = "\n";
                            break;
                    }

                    $fgColor = "\e[0m";
                    switch ($pixel->getColor()) {
                        case Color::YELLOW:
                            $fgColor = "\e[33m";
                            break;
                        case Color::GREEN:
                            $fgColor = "\e[32m";
                            break;
                        case Color::RED:
                            $fgColor = "\e[31m";
                            break;
                        case Color::BLACK:
                            $fgColor = "\e[30m";
                            break;
                        case Color::WHITE:
                            $fgColor = "\e[97m";
                            break;
                        default:
                            $fgColor = "\e[39m";
                            break;
                    }

                    $bgColor = "\e[0m";
                    switch ($pixel->getBgColor()) {
                        case Color::YELLOW:
                            $bgColor = "\e[43m";
                            break;
                        case Color::GREEN:
                            $bgColor = "\e[42m";
                            break;
                        case Color::RED:
                            $bgColor = "\e[41m";
                            break;
                        case Color::BLACK:
                            $bgColor = "\e[40m";
                            break;
                        case Color::WHITE:
                            $bgColor = "\e[107m";
                            break;
                        default:
                            $bgColor = "\e[49m";
                            break;
                    }

                    $s .= "\e[1m" . $fgColor . $bgColor . $c . "\e[0m";
                }
                $s .= "\n";
            }

            echo rtrim($s, "\n");
        }
    }
}
