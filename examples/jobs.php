<?php // Cron jobs
use AKikhaev\Cli\cliUnit;
use AKikhaev\Terminal\Terminal;

/**
 * jobs -  Periodical operations
 */
class jobs extends cliUnit {
    /**
     * Check left space, notify when necessary
     */
    public function diskSizeAction(): void{
        global $cfg;
        exec('df -h',$data);

        $data = array_filter($data,static function($v){
            return mb_strpos($v,'/dev/loop')===false;
        });
        $out = implode(PHP_EOL,$data);
        if (preg_match('/([9]\d|100)%/u',$out)) {
            Terminal::logError('Some drive almost filled!');
            // send notification here
        } else {
            Terminal::log('Free space enough on all drives');
        }
    }

}