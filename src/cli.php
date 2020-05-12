<?php

namespace AKikhaev\Cli;

use AKikhaev\Terminal\Terminal;

class cli {
    private static $rootCmdList = [];
    public static $options = [];
    
    public const IS_OS_WIN = DIRECTORY_SEPARATOR==='\\'; 

    /**
     * initializing cli
     */
    private static function init()
    {
        GLOBAL $CliUser;

        #function CORE_CLI_TERMINATE(){die();}
        #pcntl_signal(SIGINT, 'CORE_CLI_TERMINATE'); // Ctrl+C
        #pcntl_signal(SIGTERM, 'CORE_CLI_TERMINATE'); // killall myscript / kill <PID>
        #pcntl_signal(SIGHUP, 'CORE_CLI_TERMINATE'); // обрыв связи
        $CliUser = function_exists('posix_getpwuid') ? posix_getpwuid(posix_getuid()) : array('name'=>get_current_user());
        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        //$_SERVER['HTTP_HOST'] = 'CLI:'.Env::$cfg['site_domain'];
        //$_SERVER['SERVER_NAME'] = 'CLI:'.$cfg['site_domain'];
        //$_SERVER['REQUEST_URI'] = core::hidePathForShow('/'.trim($GLOBALS['argv'][0],'/'));
        //$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        #set_error_handler("GlobalErrorHandler");
        if (self::IS_OS_WIN) {
            //system('chcp 65001>null');
            //mb_http_output('cp866'); ob_start('mb_output_handler');
            set_include_path(str_replace(':',';', get_include_path()));
        }

        if (!in_array('--silence_greetings',$_SERVER['argv'])) {
            Terminal::writeLn(
                Terminal::es(Terminal::VIOLET).
                Terminal::es(Terminal::BOLD).
                'ITteka platform '.
                Terminal::es(Terminal::BLUE).
                'CLI MODE'.
                Terminal::es()
            );
        }
    }

    /** Windows Subsystem Linux
     * @return bool
     */
    public static function isWSL(){
        $osVersion = file_get_contents('/proc/version');
        return mb_stripos($osVersion,'Microsoft')!==false ||
            mb_stripos($osVersion,'WSL')!==false;
    }
    private static function extractOptions(&$commands)
    {

        $lastOption = '';
        foreach ($commands as $i=>$command) {
            if (mb_strpos($command,'--')===0) {
                $command = mb_substr($command,2);
                self::$options[$command] = false;
                $lastOption = $command;
                unset($commands[$i]);
            }
            elseif (mb_strpos($command,'-')===0) {
                $command = mb_substr($command,1);
                self::$options[$command] = false;
                $lastOption = $command;
                unset($commands[$i]);
            }
            elseif ($lastOption!==''){
                self::$options[$lastOption] = (self::$options[$lastOption] === false ? $command : self::$options[$lastOption].' '.$command);
                unset($commands[$i]);
            }
        }
    }
    private static function getRootCommandList(){
        if (count(self::$rootCmdList)===0) {
            $mask = __DIR__.'cli/*.php'; // {cli/*.php,u/cli/*.php}
            foreach (glob($mask, GLOB_BRACE) as $item)
                self::$rootCmdList[basename($item, '.php')] = $item;
        }
        return self::$rootCmdList;
    }
    public static function run(){
        self::init();
        self::getRootCommandList();
        $commands = $_SERVER['argv']; unset($commands[0]);
        self::extractOptions($commands);

        if (isset(self::$options['bash_completion_cword']) && count($commands)<(self::$options['bash_completion_cword']===false?1:2)) {
            die(implode(' ',array_keys(self::$rootCmdList)));
        }

        if (count($commands)===0) {
            echo "Command list:\n";
            $maxLenght = 0;
            foreach (self::$rootCmdList as $cmd=>$path) {
                if (mb_strlen($cmd)>$maxLenght) $maxLenght = mb_strlen($cmd);
            }
            foreach (self::$rootCmdList as $cmd=>$path) {
                $handle = fopen($path, 'rb');
                $description = self::mb_trim(str_replace(['<?php',"\n","\r"],'',fgets($handle, 4096)),'\s\#\/');
                fclose($handle);
                echo '  '.str_pad($cmd,$maxLenght).' - '.$description."\n";
            }
        } else {
            $rootCommand = $commands[1];
            unset($commands[1]);
            if (isset(self::$rootCmdList[$rootCommand])) {
                require_once self::$rootCmdList[$rootCommand];
                $cliUnit = new $rootCommand();
                $cliUnit->run(...$commands);
//                call_user_func([$rootCommand,'run']);
            } else echo "Cli command not found\n";
        }
    }

    public static function mb_trim($string, $trim_chars = '\s'){
        return preg_replace('/^['.$trim_chars.']*(?U)(.*)['.$trim_chars.']*$/um', '\\1',$string);
    }
}