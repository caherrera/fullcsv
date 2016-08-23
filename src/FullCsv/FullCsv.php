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

    CONST
        DEFAULT_LENGTH    = null,
        DEFAULT_DELIMITER = ',',
        DEFAULT_ENCLOSURE = '"',
        DEFAULT_ESCAPE    = "\\",
        DEFAULT_HEADER    = true,
        DEFAULT_PAGESIZE  = 1000
    ;

    /**
     * File pointer to a file successfully opened
     * @var
     */
    var $fp = false;

    /**
     * The optional delimiter parameter sets the field delimiter (one character only).
     * @var string
     */
    var $delimiter;

    /**
     * The optional enclosure parameter sets the field enclosure character (one character only).
     * @var string
     */
    var $enclosure;

    /**
     * The optional escape parameter sets the escape character (one character only).
     * @var string
     */
    var $escape;


    /**
     * Number of records inside file (include header row)
     * @var int
     */
    var $count = 0;


    /**
     * Maximum number of rows rescued when fetch records
     * @var int
     */
    var $pageSize;


    /**
     * Number of pages for read this csv file.
     * @var int
     */
    var $pages = 0;

    /**
     * Current Page
     * @var int
     */
    var $currentPage = 0;

    /**
     * Determinate if this CSV must take first row like columns name
     * @var bool
     */
    var $firstColumnIsHeader;

    /**
     * Name of the columns;
     * @var array
     */
    var $columns = array();

    /**
     * File location
     * @var
     */
    var $filename;


    /**
     * Content or a small proportion of the data contained in this csv file
     * @var array
     */
    var $data = array();

    /**
     * Csv constructor.
     * @param $filename
     * @param int $length
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param bool $header
     * @param int $limit
     * @throws Exception
     * @internal param size $int
     */
    function __construct($filename, $length=null, $delimiter = self::DEFAULT_DELIMITER, $enclosure = self::DEFAULT_ENCLOSURE,$escape=self::DEFAULT_ESCAPE,$header = self::DEFAULT_HEADER, $limit = self::DEFAULT_PAGESIZE)
    {

        if (is_array($length)) { extract(array_merge(
            array(
            'length'             =>self::DEFAULT_LENGTH,
            'delimiter'          =>self::DEFAULT_DELIMITER,
            'enclosure'          =>self::DEFAULT_ENCLOSURE,
            'escape'             =>self::DEFAULT_ESCAPE,
            'header'             =>self::DEFAULT_HEADER,
            'limit'              =>self::DEFAULT_PAGESIZE
            ),$length
        )); }

        $this->length              = $length;
        $this->filename            = $filename;
        $this->delimiter           = $delimiter;
        $this->enclosure           = $enclosure;
        $this->escape              = $escape;
        $this->firstColumnIsHeader = $header;

        if (!is_file($this->filename))     {throw new Exception('File does not exists');}
        if (!is_readable($this->filename)) {throw new Exception('File cannot be accessed');}
        $this->longestLine();
        $this->count();
        $this->setPageSize($limit);

        $this->open();
        $this->rewind();

    }

    /**
     * @param string $mode
     * @return boolean
     * @throws Exception
     */
    function open($mode = 'r')
    {
        if ($this->fp) {return true;}

        if (($this->fp = fopen($this->filename, $mode))!== FALSE) {
            return true;
        }else{
            throw new Exception('File cannot be open');
            return false;
        }
    }

    function isOpen() {
        return $this->fp!==false;
    }

    /**
     * Return maximum size of a line in a file. only for *nix versions of PHP. Windows will return 1000
     * @return int
     * @throws Exception
     */
    function longestLine() {
        if (!$this->length) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $count = 1000;
            } else {
                $exec    = exec("wc -L " . $this->filename . "| awk '{print $1}'");
                $exec    = explode(" ", $exec);
                $longest = array_shift($exec);
            }
            if (is_numeric($longest)) {
                return $this->length = $longest+2;
            } else {
                throw new Exception("Cannot get file size");
            }
        }
        return $this->length;
    }

    /**
     * Return a next row without format
     * @return array
     */
    function fetch() {
        if (!feof($this->fp)) {
            $return = fgetcsv($this->fp, $this->length, $this->delimiter, $this->enclosure, $this->escape);
            return $return;
        }else{
            return false;
        }
    }

    /**
     * Fetch rows from file and merge with current data
     * @param int limit
     * @return array
     * @throws Exception
     */
    function pull($limit=null)
    {
        $this->flush();
        $limit = is_numeric($limit)?$limit:$this->pageSize;
        if (!$this->isOpen()) { throw new Exception('File is not open');}
        if (feof($this->fp)) {return;}
        $x=0;
        while (($data=$this->fetch()) !== FALSE) {
            $x++;
            $this->data[]=$this->saveCombine($this->columns,$data);
            if ($limit && $x>=$limit) {
                break;
            }
        }
        $this->currentPage++;
        return $this->data;
    }

    /**
     * Provide a safe way to combine header and data
     * @param $headers
     * @param $data
     * @return array
     */
    function saveCombine($headers,$data) {
        if (!is_array($data)) {return [];}
        if (count($headers)<count($data)) {
            $headers=array_merge($headers,array_keys($data));
        }elseif (count($headers)>count($data)) {
            $data =array_merge(
                array_fill(count($data),count($headers)-count($data),null),$data
            );
        }
        return array_combine($headers,$data);

    }

    /**
     * Rewind the position of a file pointer
     * @link http://us2.php.net/manual/en/function.rewind.php
     * @return boolean
     */
    function rewind() {
        if ($this->firstColumnIsHeader) {
            if ($return = rewind($this->fp)) {
                $this->columns = $this->fetch();
            }

        }else{
            if ($return = rewind($this->fp)) {
                $this->columns = array_keys($this->fetch());
                $return = rewind($this->fp);
            }

        }
        $this->setCurrentPage(0);
        return $return;
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
            case SEEK_SET:
                $this->rewind($this->fp);
            case SEEK_CUR:
                break;
            default:
                throw new Exception("Whence not valid");
        }
        $r=0;
        if ($offset) {
            while (($data[] = $this->fetch()) !== false && ++$r < $offset - 1) {
                ;
            }
        }

        return true;

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
        $this->seek($start);

        $this->pull($limit);
        return $this->data;
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
            return $this->count = $count - ($this->firstColumnIsHeader?1:0);
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

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     * @return FullCsv
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    /**
     * @param string $enclosure
     * @return FullCsv
     */
    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;

        return $this;
    }

    /**
     * @param string $escape
     * @return FullCsv
     */
    public function setEscape($escape)
    {
        $this->escape = $escape;

        return $this;
    }

    /**
     * @param boolean $firstColumnIsHeader
     * @return FullCsv
     */
    public function setFirstColumnIsHeader($firstColumnIsHeader)
    {
        $this->firstColumnIsHeader = $firstColumnIsHeader;

        return $this;
    }

    /**
     * @param int $pageSize
     * @return FullCsv
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
        $this->pages =($this->pageSize)? ceil($this->count / $this->pageSize):1;
        return $this;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     * @return FullCsv
     */
    public function setColumns(array $columns = array())
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @param int $currentPage
     * @return FullCsv
     */
    public function setCurrentPage($currentPage=null)
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }
}