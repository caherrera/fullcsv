<?php
/**
 * Class FullCsv
 *
 * CSV reader Simple feature with a small use of memory.
 * This class allow to save memory using caching and read based on pages instead of read all records form a csv.
 * please use only with local files for any other schema download the file first.
 *
 * @author Carlos Herrera
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @link http://us2.php.net/manual/en/function.fopen.php
 * @link http://us2.php.net/manual/en/function.fgetcsv.php
 * @link http://us2.php.net/manual/en/function.fclose.php
 */
class FullCsv
{

    /**
     * File pointer to a file successfully opened
     * @var
     */
    var $fp;

    /**
     * The optional delimiter parameter sets the field delimiter (one character only).
     * @var string
     */
    var $delimiter=',';

    /**
     * The optional enclosure parameter sets the field enclosure character (one character only).
     * @var string
     */
    var $enclosure='"';

    /**
     * The optional escape parameter sets the escape character (one character only).
     * @var string
     */
    var $escape="\\";

    var $count = 0;

    var $firstColumnIsHeader = true;

    /**
     * File location
     * @var
     */
    var $filename;

    /**
     * Csv constructor.
     * @param $filename
     * @param int $length
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param bool $firstColumnIsHeader
     */
    function __construct($filename, $length=null, $delimiter = ',', $enclosure = '"',$escape="\\",$firstColumnIsHeader = true)
    {

        $this->length              = $length;
        $this->filename            = $filename;
        $this->delimiter           = $delimiter;
        $this->enclosure           = $enclosure;
        $this->escape              = $escape;
        $this->firstColumnIsHeader = $firstColumnIsHeader;

        $this->open();
    }

    /**
     * @param string $mode
     * @return boolean
     * @throws Exception
     */
    function open($mode = 'a')
    {
        if (!is_file($this->filename))     {throw new Exception('File does not exists');}
        if (!is_readable($this->filename)) {throw new Exception('File cannot be accessed');}
        $this->longestLine();
        $this->count();
        if ($this->fp = fopen($this->filename, $mode)) {
            return true;
        }else{
            throw new Exception('File cannot be open');
            return false;
        }
    }

    /**
     * Return maximum size of a line in a file. only for *nix versions of PHP. Windows will return 1000
     * @return int
     * @throws Exception
     */
    function longestLine() {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $count=1000;
        } else {
            $exec=exec("wc -L " . $this->filename . "| awk '{print $1}'");
            $exec=explode(" ",$exec);
            $count = array_shift($exec);
        }
        if (is_numeric($count)) {
            return $this->count;
        }else{
            throw new Exception("Cannot get file size");
        }
    }

    /**
     * Fetch rows from file and merge with current data
     * @param int limit
     * @return array
     */
    function pull($limit=0)
    {
        $x=0;
        while ($this->data[]=fgetcsv($this->fp, null,$this->delimiter, $this->enclosure, $this->escape) && (!$limit || ($limit && $x++<$limit)));
        return $this->data;
    }

    /**
     * Rewind the position of a file pointer
     * @link http://us2.php.net/manual/en/function.rewind.php
     * @return boolean
     */
    function rewind() {
        return rewind($this->fp);
    }

    /**
     * Seeks on a file pointer.
     * @link http://php.net/manual/en/function.fseek.php
     * @param int $offset The offset. To move to a position based in rows.
     * @param int $whence
     * @return boolean
     * @throws Exception
     */

    function seek($offset = 0 , $whence = SEEK_SET  ) {
        switch ($whence) {
            case SEEK_CUR:
            case SEEK_SET:
                return fseek($this->fp,$offset,$whence)===0 ? true:false;
                break;
            default:
                throw new Exception("Whence not valid");
        }

    }

    /**
     * Flush cached data
     */
    function flush() {
        $this->data=array();
    }

    /**
     * Flush cached data and fetch new rows from file
     * @param int $limit
     * @param int $page
     * @return array
     */
    function fetchAll($limit=0,$page = 0) {
        $start=($limit*$page);
        $this->flush();
        $this->rewind();
        $this->seek($start);

        return $this->fetch($limit);
    }

    /**
     * Return number of rows inside file
     * @return integer
     * @throws Exception
     */
    function count() {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $exec=exec("find /v /c \"\" " . $this->filename);
            $exec=explode(" ",$exec);
            $count = array_pop($exec);
        } else {
            $exec=exec("wc -l " . $this->filename . "| awk '{print $1}'");
            $exec=explode(" ",$exec);
            $count = array_shift($exec);
        }
        if (is_numeric($count)) {
            return $this->count;
        }else{
            throw new Exception("Cannot get file size");
        }
    }

    /**
     * The file is closed. Returns TRUE on success or FALSE on failure.
     * @return bool
     */
    function close()
    {
        return fclose($this->fp);
    }
}