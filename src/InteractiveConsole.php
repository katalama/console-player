<?php

declare(strict_types=1);

namespace Katalama\ConsolePlayer;

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
        $game->init($this->width, $this->height, $this->fps);
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
        
        system('stty icanon echo');
        system('tput cnorm');
        echo "\nGame over\n";
    }
    
    private function draw($game)
    {
        $scene = $game->nextScene();
        
        if (null !== $scene) {
            system('clear');
            echo $scene;
        }
    }
}
