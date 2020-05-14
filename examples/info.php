<?php // General information
use AKikhaev\Cli\cliUnit;
use AKikhaev\Terminal\Terminal;

/**
 * jobs -  Periodical operations
 */
class info extends cliUnit {

    private function sizeBautify($size):string
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/ (1024 ** $i = floor(log($size, 1024))),2).' '.$unit[$i];
    }

    /**
     * Show script memory usage
     */
    public function memoryUsageAction(): void{
        Terminal::logInfo('Memory peak cli usage: ' .$this->sizeBautify(memory_get_peak_usage()));
        Terminal::logInfo('Memory peak cli usage: ' .$this->sizeBautify(memory_get_peak_usage()));
    }

    /** Calculate folder size
     * @param $path
     */
    public function folderSizeAction($folder):void {
        $size = 0;
        $dirIterator = new RecursiveDirectoryIterator($folder);
        $filesIterator = new RecursiveIteratorIterator($dirIterator);
        foreach ($filesIterator as $item) {
            /* @var SplFileInfo $item */
            if ($item->isFile()) {
                $size += $item->getSize();
            }
        }

        Terminal::logInfo("Folder  size of $folder is: " .$this->sizeBautify($size));
    }

}