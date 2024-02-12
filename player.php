<?php

declare(strict_types=1);

use Katalama\ConsolePlayer\Game\SnakeGame;
use Katalama\ConsolePlayer\InteractiveConsole;

require __DIR__.'/vendor/autoload.php';

declare(ticks = 1);
pcntl_signal(SIGINT, function (int $signo = 0, mixed $siginfo = []) {
	system('stty icanon echo');
	system('tput cnorm');
	echo "\n";
	exit();
});

function main()
{
	$width = $height = null;
	
	if (php_sapi_name() === 'cli') {
		$width ??= (int)exec('tput cols');
		$height ??= (int)exec('tput lines');
	}

	$player = new InteractiveConsole($width, $height);
	$player->run();
}

main();





