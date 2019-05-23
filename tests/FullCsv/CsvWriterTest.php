<?php


namespace Test\FullCsv;


use Faker\Factory as Faker;
use FullCsv\CsvWriter;
use PHPUnit\Framework\TestCase;

class CsvWriterTest extends TestCase {

	/**
	 * @var CsvWriter
	 */
	public $new;

	public function testCreatingFile() {

		$this->new->createIfNotExists();
		$this->assertNotFalse( $this->new->isOpen() );
		$this->assertTrue( $this->new->close() );
	}

	public function testsWritingMultipeLines() {
		$this->new->createIfNotExists();
		$this->new->setFirstRowIsHeader( false );

		$faker = Faker::create();
		$data  = [];
		for ( $i = 0; $i < 10; $i ++ ) {
			$data[] = [
				$faker->firstName,
				$faker->lastName,
				$faker->email,

			];

		}
		$this->new->setRows( $data );
		$this->new->close();

		$this->assertCount( 10, file( $this->new->filename ) );
	}

	public function testsWritingSingleLine() {
		$this->new->createOrReplace()->setFirstRowIsHeader( false );

		$faker = Faker::create();

		$data = [
			$faker->firstName,
			$faker->lastName,
			$faker->email,

		];


		$this->new->addRow( $data );
		$this->new->close();

		$this->assertCount( 1, file( $this->new->filename ) );
	}

	public function testAddRowAndCustomHeader() {
		$this->new->createOrReplace();
		$this->new->setHeader( [ 'first', 'last', 'email' ] );
		$this->new->close();

		$this->assertCount( 1, file( $this->new->filename ) );

	}

	public function testAddRowsWithHeaders() {
		$this->new->createIfNotExists();

		$faker = Faker::create();
		$data  = [];
		for ( $i = 0; $i < 10; $i ++ ) {
			$data[] = [
				'firstName' => $faker->firstName,
				'lastName'  => $faker->lastName,
				'email'     => $faker->email,

			];

		}
		$this->new->setRows( $data );
		$this->new->close();
		$file = file( $this->new->filename );
		$this->assertEquals( "firstName,lastName,email", trim( $file[0] ) );
		$this->assertCount( 11, $file );

	}

	protected function setUp() {
		$this->filename = dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'example' . DIRECTORY_SEPARATOR . 'exampleWriting.csv';
		$this->new      = new CsvWriter( $this->filename );

	}
}