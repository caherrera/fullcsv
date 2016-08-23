<?php
/**
 * Created by PhpStorm.
 * User: carlos
 * Date: 19/08/16
 * Time: 3:39 PM
 */

namespace FullCsvTest;

use FullCsv;
use PHPUnit_Framework_TestCase;

class FullCsvTests extends PHPUnit_Framework_TestCase
{
    /**
     * FullCSV instance
     * @var FullCsv
     */
    var $f = false;
    function setUp()
    {
        $this->f = new FullCsv(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'example' . DIRECTORY_SEPARATOR . 'example.csv');
        parent::setUp(); // TODO: Change the autogenerated stub
    }

    function testFgetcsvFunctions() {
        $data=[];
        if (($handle = fopen($this->f->filename, "r")) !== FALSE) {
            while (($data[] = fgetcsv($handle, 1000, ",")) !== FALSE) ;
        }
        $this->assertNotFalse($handle);
        $this->assertCount(13,$data);
        $this->assertTrue(fclose($handle));
    }

    function testOpen()
    {

        $this->assertTrue($this->f->isOpen(),'Cannot open the file');
    }

    function testLongestLine()
    {
        $this->assertEquals(37,$this->f->longestLine());
    }

    function testPageSize() {
        $this->f->rewind();
        $this->f->setPageSize(4);
        $p=0;$rows=[];
        while($data=$this->f->pull()) {
            $p++;
            $rows+=$data;

        }
        //print_r($rows);
        $this->assertEquals(3,$p);
        $this->assertCount(12,$rows);

    }

    function testPull()
    {
        $this->f->rewind();
        $this->assertCount(2,$this->f->pull(2));
    }

    function testRewind()
    {
        $this->assertTrue($this->f->rewind());
    }

    function testSeek()
    {
        $this->assertTrue($this->f->seek(3,SEEK_SET));
        $row=$this->f->pull(1);
        $this->assertEquals(array(
            array(
                'Email'=>'000907513@tafesa.edu.au',
                'First Name'=>'Zoe',
                'Last Name'=>'Cameron'
            )
        ),$row);

    }

    function testCount()
    {
        $this->assertEquals(12,$this->f->count());
    }

    function testFlush()
    {
        $this->f->pull(1);
        $this->f->flush();
        $this->assertEmpty($this->f->data);
    }

    function testReadall()
    {
        $fetchall=$this->f->fetchAll();
        $this->assertTrue(is_array($fetchall));
    }


    function testClose()
    {
        $r=$this->f->close();
        $this->assertTrue($r,'Internal Error file couldn\'t closed');

    }

}
