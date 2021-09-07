<?php


class Command
{
    private $commandName;
    private $arguments;
    private $options;
    private $file_name;

    public function __construct(array $inputArgs, $file_path)
    {
        $this->file_name = $file_path;
        $this->parseArguments($inputArgs);
        $this->parseOptions($inputArgs);
        $this->commandName = $inputArgs[1];
        $commands = Command::readCommands($this->file_name);

        foreach ($commands as $command){
            if($this->commandName == $command->getName()){
                if( !(count($this->arguments) or count($this->options))) {
                    $command->executeCommand($inputArgs);
                    return $command;
                }
                else{
                    echo "This command already exists!";
                    return false;
                }
            }
        }
        return $this->save();
    }

    private function executeCommand($inputArgs){
        unset($inputArgs[1]);
        foreach($inputArgs as $inputArg){
            $definedArgument = false;
            $definedOption = false;

            if(in_array($inputArg, $this->arguments) or $inputArg == "help") {
                $definedArgument = true;
            }

            foreach ($this->options as $index => $dict){
                foreach ($dict as $key => $value) {
                    $option = explode("=", $inputArg);
                    if(count($option) < 2){
                        continue;
                    }
                    $option_key = $option[0];
                    $option_value = $option[1];
                    if ($option_key == $key and in_array($option_value, $value)) {
                        $definedOption = true;
                    }
                }
            }
            if( !$definedArgument and !$definedOption){
                echo "\nUndefined argument or option $inputArg\n";
            }
            elseif ($definedArgument and $inputArg == "help"){
                Command::help($this);
            }
        }
    }

    public static function readCommands($file_name){
        $commands = array();
        $fh = fopen($file_name,'r');
        while ($line = fgets($fh)) {
            $command = unserialize($line);
            array_push($commands, $command);
        }
        fclose($fh);
        return $commands;
    }

    private function save(){
        if(! (count($this->arguments) or count($this->options)) ){
            echo "A command should have at least one argument or option.";
            return false;
        }
        $fp = fopen($this->file_name,"a");
        fwrite($fp,serialize($this)."\n");
        fclose($fp);
        Command::help($this);
        return $this;
    }

    private function removeSymbols($symbols, $str){
        $symbolsArr = str_split($symbols);
        foreach ($symbolsArr as $symbol){
            $str = str_replace($symbol, "", $str);
        }
        return $str;
    }

    private function parseArguments(array $inputArgs)
    {
        $resultParams = [];
        foreach ($inputArgs as $argument) {
            preg_match('/^{[a-zA-Z]+.*}/', $argument, $matches);
            if (!empty($matches)) {
                foreach ($matches as $match) {
                    $argsArr = explode(",", $match);
                    foreach ($argsArr as $arg) {
                        $arg = $this->removeSymbols("{}", $arg);
                        array_push($resultParams, $arg);
                    }
                }
            }
        }
        $this->arguments = $resultParams;
    }

    private function parseOptions(array $inputArgs){
        $resultParams = [];
        foreach ($inputArgs as $argument){
            $argument = str_replace(" ", "", $argument);
            preg_match('/[[a-zA-Z]+.*]/', $argument, $matches);
            if (!empty($matches)) {
                foreach ($matches as $match) {
                    $match = $this->removeSymbols("[]", $match);
                    $paramArr = explode("=", $match);
                    $paramName = $paramArr[0];
                    $paramValue = $paramArr[1];
                    $valuesArr = explode(",", $paramValue);
                    $valuesArrResult = [];
                    foreach ($valuesArr as $value){
                        $value = $this->removeSymbols("{ }", $value);
                        array_push($valuesArrResult, $value);
                    }
                    array_push($resultParams, [$paramName => $valuesArrResult]);
                }
            }
        }
        $this->options = $resultParams;
    }

    public function getArgs(){
        return $this->arguments;
    }

    public function getOptions(){
        return $this->options;
    }

    public function getName(){
        return $this->commandName;
    }

    public static function help($command){
        echo("\nCalled command: " . $command->getName() . "\n");
        echo("\nArguments: \n");
        foreach ($command->arguments as $arg){
            echo("\t- $arg \n");
        }
        echo("\nOptions: \n");
        foreach ($command->options as $index => $dict){
            foreach ($dict as $key => $values) {
                echo("\t- $key \n");
                foreach ($values as $value){
                    echo("\t\t- $value \n");
                }
            }
        }
    }
}

