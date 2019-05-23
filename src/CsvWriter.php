<?php


namespace FullCsv;

use Exception;

class CsvWriter {

	/**
	 * @var array|string
	 */
	var $delimiter;
	/**
	 * @var string
	 */
	var $enclosure;
	/**
	 * @var
	 */
	var $escape;
	/**
	 * @var
	 */
	var $header;
	/**
	 * @var
	 */
	var $filename;
	/**
	 * @var array
	 */
	protected $data = [];
	protected $mode;
	protected $isNew;

	protected $savedHeader = false;

	/**
	 * @var bool
	 */
	protected $firstRowIsHeader;
	/**
	 * @var
	 */
	private $fp;

	/**
	 * CsvWriter constructor.
	 *
	 * @param         $filename
	 * @param string  $delimiter
	 * @param string  $enclosure
	 * @param boolean $header
	 */
	function __construct( $filename, $delimiter = FullCsv::DEFAULT_DELIMITER, $enclosure = FullCsv::DEFAULT_ENCLOSURE, $escape = FullCsv::DEFAULT_ESCAPE, $header = FullCsv::DEFAULT_HEADER ) {

		if ( is_array( $delimiter ) ) {
			extract( array_merge(
				array(
					'delimiter' => FullCsv::DEFAULT_DELIMITER,
					'enclosure' => FullCsv::DEFAULT_ENCLOSURE,
					'escape'    => FullCsv::DEFAULT_ESCAPE,
					'header'    => FullCsv::DEFAULT_HEADER,
				), $delimiter
			) );
		}


		$filepath = explode( '/', $filename );
		if ( $filepath[0] != 'php:' ) {
			$this->checkPath( dirname( $filename ) );
		}

		$this->filename         = $filename;
		$this->delimiter        = $delimiter;
		$this->enclosure        = $enclosure;
		$this->escape           = $escape;
		$this->firstRowIsHeader = $header;
	}

	/**
	 * @param null $path
	 * @param int  $mode
	 *
	 * @return bool|string|null
	 */
	public function checkPath( $path = null, $mode = 0777 ) {
		if ( $path ) {
			$path = $this->sanitizePath( $path );
			if ( is_dir( $path ) ) {
				return $path;
			} else {
				try {
					$o = umask( 0 );

					if ( ! mkdir( $path, $mode, true ) ) {
						throw new Exception( "Can't create directory " . $path );
					}
					umask( $o );

					return $path;
				} catch ( Exception $e ) {
					return false;
				}
			}
		}

		return false;
	}

	/**
	 *
	 * @return string
	 * @since  1 Dec 2015
	 * @author Carlos Herrera
	 */
	public function sanitizePath() {
		$path     = func_get_args();
		$filePath = null;
		if ( count( $path ) > 0 ) {
			$filePath = array_shift( $path );
			$filePath = explode( DIRECTORY_SEPARATOR, $filePath );
			$root     = array_shift( $filePath );
			foreach ( $path as $_path ) {
				$_path = explode( DIRECTORY_SEPARATOR, $_path );
				foreach ( $_path as $__path ) {
					$filePath[] = $__path;
				}
			}

			$filePath = array_filter( $filePath, function ( $part ) {
				//remove empty string while keeping 0 or '0'
				return ( is_string( $part ) && strlen( $part ) > 0 ) || is_numeric( $part );
			} );

			return $root . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $filePath );
		}
	}

	/**
	 * @return bool
	 */
	public function isFirstRowIsHeader() {
		return $this->firstRowIsHeader;
	}

	/**
	 * @param bool $firstRowIsHeader
	 *
	 * @return CsvWriter
	 */
	public function setFirstRowIsHeader( $firstRowIsHeader ) {
		$this->firstRowIsHeader = $firstRowIsHeader;

		return $this;
	}

	public function isNew() {
		return $this->isNew;
	}

	/**
	 * @throws Exception
	 */
	function create() {
		if ( file_exists( $this->filename ) ) {
			throw new Exception( 'file already exists' );
		} else {
			$this->createOrReplace();
		}
	}

	function createOrReplace() {
		$this->open( 'w' );

		return $this;
	}

	/**
	 * @param string $mode
	 *
	 * @return bool
	 */
	function open( $mode = 'a' ) {
		$this->isNew = file_exists( $this->filename ) ? false : true;
		$this->mode  = $mode;
		$this->fp    = fopen( $this->filename, $mode );

		return $this->isOpen();
	}

	/**
	 * @return bool
	 */
	function isOpen() {
		return $this->fp !== false;
	}

	/**
	 * @return bool
	 */
	public function createIfNotExists() {

		return $this->open( 'w' );

	}

	function setRows( $rows = [] ) {
		$this->data = [];
		foreach ( $rows as $row ) {
			$this->addRow( $row );
		}
	}

	function addRow( $row ) {
		$fields = [];
		// print_r($row);
		foreach ( $row as $k => $v ) {
			if ( ! is_array( $v ) ) {
				$fields[ $k ] = $v;
			} else {
				$fields[ $k ] = implode( ' , ', $v );
			}
		}
		$this->data[] = $fields;

		return $this;

	}

	/**
	 * @return bool
	 */
	function close() {

		$this->flush();

		return fclose( $this->fp );
	}

	function flush() {
		if ( $this->isOpen() ) {
			$this->saveHeader();
			$r = true;
			foreach ( $this->data as $row ) {
				$r = $r && $this->saveRow( $row );
			}

			return $r;
		}
	}

	protected function saveHeader() {
		if ( ! $this->savedHeader && $this->firstRowIsHeader ) {
			if ( ! $this->hasHeader() ) {
				$header = $this->getHeaderFromFirstRow();
				$this->setHeader( $header );

			}
			$this->saveRow( $this->header );
			$this->savedHeader = true;

		}
	}

	public function hasHeader() {
		return is_array( $this->header ) && count( $this->header );
	}

	public function getHeaderFromFirstRow() {
		if ( count( $this->data ) ) {
			reset( $this->data );
			$first = current( $this->data );

			return array_keys( $first );
		}

		return [];
	}

	/**
	 * @param array $header
	 */
	function setHeader( array $header = [] ) {
		if ( ! $this->header ) {
			$this->header = $header;
		}
	}

	protected function saveRow( array $row ) {
		return fputcsv( $this->fp, $row, $this->delimiter, $this->enclosure, $this->escape );
	}
}