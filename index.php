<?php
require_once "Command\Command.php";


unset($argv[0]);

$file_path = "commands.txt";
$commands = Command::readCommands($file_path);

if(count($argv) == 0){
    echo("Saved commands: \n");
    foreach ($commands as $command){
        echo("\t - " . $command->getName(). "\n");
    }
}
else {
    $newCommand = new Command($argv, $file_path);
}


