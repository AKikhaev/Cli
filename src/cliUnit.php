<?php

namespace AKikhaev\Cli;

use AKikhaev\Terminal\Terminal;

/**
 * Class cliUnit
 */
class cliUnit {
    /**
     * @var int Не позволяет запускать комманды, количество активных копий которых превышает лимит. 0 - без ограничений
     */
    protected $concurrentLimit = 0;
    protected $runMethod = 'helpAction';
    protected $options_available = [];

    public function __construct()
    {
        if(PHP_SAPI!=='cli')die('<!-- not allowed -->');
        if ($this->concurrentLimit>0 && !isset(cli::$options['bash_completion_cword'])) {
            $find = exec('ps -o command --no-headers -p '.getmypid());
            exec('ps -A -o command --no-headers',$psOut);
            $concurrent = 0; foreach ($psOut as $cmd) if ($cmd==$find) $concurrent++;
            if ($concurrent>$this->concurrentLimit) {
                Terminal::logError('Concurrent limit exceeded! ('.$concurrent.')'); die();
            }
            else {
                Terminal::log("Started $concurrent concurrent process.");
            }
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function run(){
        $commands = func_get_args();
        if (isset(cli::$options['bash_completion_cword'])) $this->runMethod = 'bash_completion';
        elseif (count($commands)>0) {
            $this->runMethod = $commands[0] . 'Action';
            unset($commands[0]);
        }
        if (method_exists($this,$this->runMethod)) {
            $rc = new \ReflectionClass($this);
            $method = $rc->getMethod($this->runMethod);
            if (count($commands)>=$method->getNumberOfRequiredParameters()) {
                try {
                    $this->{$this->runMethod}(...$commands);
                } catch (\Throwable $e) {
                    Terminal::logError($e->getMessage());
                    //core::GlobalExceptionHandler($e);
                }
                flush();
            } else {
                Terminal::logError('Parameters required. See help');
                $parameters = [];
                foreach ($method->getParameters() as $parameter){
                    $parameters[] = $parameter->name;
                }
                $parameters = count($parameters)>0 ? ' {'.implode(',',$parameters).'}' : '';
                $comment = $method->getDocComment();
                if ($comment!==false) {
                    echo '  '.mb_substr($method->getName(),0,-6).$parameters.":\n";
                    foreach (explode("\n",$comment) as $line) {
                        $line = $this->mb_trim($line,'\/\*\s');
                        if ($line==='') continue;
                        if (mb_strpos($line,'@')!==false) break;
                        echo '    '.$line.PHP_EOL;
                    }
                } else {
                    echo '  '.mb_substr($method->getName(),0,-6).$parameters."\n";
                }
            }

        } else echo "Cli sub command not found!\n";
    }

    /**
     * @throws \ReflectionException
     */
    protected function bash_completion()
    {
        $commands = func_get_args();
        $bash_completion_cword = cli::$options['bash_completion_cword'];
        $commands_list = [];
        $rc = new \ReflectionClass($this);
        foreach ($rc->getMethods() as $method) {
            if (mb_substr($method->getName(), -6) === 'Action')
                if ($method->getDocComment() !== false)
                    $commands_list[] = mb_substr($method->getName(), 0, -6);
                else {
                    if ($bash_completion_cword !== false && mb_strpos($method->getName(),$bash_completion_cword)===0)
                        $commands_list[] = mb_substr($method->getName(), 0, -6);
                }
        }
        if (count($commands)<($bash_completion_cword===false?1:2))
            echo implode(' ',array_merge($commands_list,$this->options_available));
        else
            echo implode(' ',$this->options_available);
    }

    private function mb_trim($string, $trim_chars = '\s'){
        return preg_replace('/^['.$trim_chars.']*(?U)(.*)['.$trim_chars.']*$/um', '\\1',$string);
    }

    /** Shows this help
     * @throws \ReflectionException
     */
    protected function helpAction(){
        $rc = new \ReflectionClass($this);
        $comment = $rc->getDocComment();
        if ($rc->getDocComment()!==false) {
            foreach (explode("\r",$comment) as $line) {
                echo $this->mb_trim($line,'\/\*\s');
            }
        }
        foreach ($rc->getMethods() as $method) {
            if (mb_substr($method->getName(),-6)==='Action') {
                $parameters = [];
                foreach ($method->getParameters() as $parameter){
                    $parameters[] = $parameter->name;
                }
                $parameters = count($parameters)>0 ? ' {'.implode(',',$parameters).'}' : '';
                $comment = $method->getDocComment();
                if ($comment!==false) {
                    echo '  '.mb_substr($method->getName(),0,-6).$parameters.":\n";
                    foreach (explode("\n",$comment) as $line) {
                        $line = $this->mb_trim($line,'\/\*\s');
                        if ($line==='') { continue; }
                        if (mb_strpos($line,'@')!==false) { break; }
                        echo '    '.$line.PHP_EOL;
                    }
                } else {
                    echo '  '.mb_substr($method->getName(),0,-6).$parameters."\n";
                }

            }
        }
    }
}