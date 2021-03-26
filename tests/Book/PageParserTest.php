<?php

namespace App\Tests\Book;

use App\PageParser;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * @covers PageParser
 */
class PageParserTest extends TestCase {
	/**
	 * @var PageParser
	 */
	private $pageParser;

	public function setUp(): void {
		$this->pageParser = $this->parseFile( __DIR__ . '/fixtures/Tales_of_Unrest/Navigation.html' );
	}

	public function testGetChaptersList() {
		$chapterList = $this->pageParser->getChaptersList( [], [] );
		$this->assertCount( 14, $chapterList );
		$this->assertEquals( "Tales_of_Unrest/Author's_Note", $chapterList[0]->title );
		$this->assertEquals( "Karain/Chapter_V", $chapterList[6]->title );
	}

	public function testGetFullChaptersList() {
		$chapterList = $this->pageParser->getFullChaptersList( 'Tales Of Unrest', [], [] );
		$this->assertCount( 14, $chapterList );
	}

	public function testPictureList() {
		$pictureList = $this->pageParser->getPicturesList();
		$this->assertCount( 2, $pictureList );
		$this->assertArrayHasKey( 'Wikimedia-logo.svg-18px-Wikimedia-logo.svg.png', $pictureList );
		$this->assertArrayHasKey( 'PD-icon.svg-48px-PD-icon.svg.png', $pictureList );
	}

	public function testGetPagesListEmpty() {
		$pagesList = $this->pageParser->getPagesList();
		$this->assertCount( 0, $pagesList );
	}

	public function testGetPagesList() {
		$parser = $this->parseFile( __DIR__ . '/fixtures/Pacotilha_poetica/Se_namora_por_gosto_ou_por_precisão.html' );
		$pagesList = $parser->getPagesList();
		$this->assertEquals( [
			'Página:Pacotilha_poetica.pdf/10',
			'Página:Pacotilha_poetica.pdf/11',
			'Página:Pacotilha_poetica.pdf/12',
			'Página:Pacotilha_poetica.pdf/13'
		], $pagesList );
	}

	public function testMetadataIsSetReturnsTrueIfSet() {
		$this->assertTrue( $this->pageParser->metadataIsSet( 'ws-author' ) );
	}

	public function testMetadataIsSetReturnsFalseIfNotSet() {
		$this->assertFalse( $this->pageParser->metadataIsSet( 'ws-xxx' ) );
	}

	public function testGetMetadataReturnsValueIfSet() {
		$data = $this->pageParser->getMetadata( 'ws-author' );
		$this->assertEquals( 'Joseph Conrad', $data );
	}

	public function testGetMetadataReturnsEmptyIfNotSet() {
		$data = $this->pageParser->getMetadata( 'ws-xxx' );
		$this->assertEmpty( $data );
	}

	public function testGetContentReturnsADOMDocument() {
		$content = $this->pageParser->getContent( true );
		$this->assertInstanceOf( DOMDocument::class, $content );
	}

	public function testCleanIds() {
		$doc1 = new DOMDocument();
		$doc1->loadHTML( '<span id="1"></span><span id="lorem"></span><a id=".123 foo:bar" href="/foobar#1"></a>' );
		$pageParser = new PageParser( $doc1 );
		$doc2 = new DOMDocument();
		$doc2->loadHTML( '<span id="1"></span><span id="lorem"></span>' );
		$pageParser2 = new PageParser( $doc2 );

		// Parse the documents.
		$html = $pageParser->getContent( false )->saveHTML();
		$pageParser2->getContent( false );

		// Test outputs.
		$this->assertCount( 9, PageParser::getIds() );
		$this->assertArrayHasKey( 'id-1', PageParser::getIds() );
		$this->assertArrayHasKey( 'id-1-n8', PageParser::getIds() );
		$this->assertArrayHasKey( 'lorem', PageParser::getIds() );
		$this->assertArrayHasKey( 'lorem-n9', PageParser::getIds() );
		$this->assertArrayHasKey( 'id-.123_foo:bar', PageParser::getIds() );

		$this->assertStringContainsString( '<span id="id-1"></span>', $html );
		$this->assertStringContainsString( '<a id="id-.123_foo:bar" href="/foobar#id-1"></a>', $html );
	}

	private function parseFile( $filename ) {
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadHTMLFile( $filename ), 'parsing of "' . $filename . '"" failed' );
		return new PageParser( $doc );
	}

	/**
	 * @dataProvider provideAlignAttr()
	 */
	public function testAlignAttr( string $in, string $out ) {
		$doc1 = new DOMDocument();
		$doc1->loadHTML( $in );
		$pageParser1 = new PageParser( $doc1 );
		$this->assertStringContainsString( $out, $pageParser1->getContent( false )->saveHTML() );
	}

	public function provideAlignAttr() {
		return [
			[
				'<table align="center"></table>',
				'<table style="margin: auto;"></table>',
			],
			[
				'<table align="right"></table>',
				'<table style="margin-left: auto;"></table>',
			],
			[
				'<div align="center" style="color: green"></div>',
				'<div style="text-align: center; color: green"></div>',
			],
			[
				'<div align="right"></div>',
				'<div style="text-align: right;"></div>',
			],
		];
	}

	/**
	 * If a work doesn't include ws-summary, we want to get all of it's subpage links as the ToC.
	 */
	public function testSubpagesEncodedHrefs() {
		$doc = new DOMDocument();
		$doc->loadHTML( '<body><a href="./F(o)o%E2%99%A5/Bar" title="F(o)o♥/Bar">Bar</a></body>' );
		$pageParser = new PageParser( $doc );
		$this->assertCount( 1, $pageParser->getFullChaptersList( 'F(o)o♥', [], [] ) );
	}

	/**
	 * @dataProvider provideGetPicturesList
	 */
	public function testGetPicturesList( string $in, string $out, string $picTitle, string $picName ): void {
		$doc1 = new DOMDocument();
		$doc1->loadXML( $in );
		$pageParser1 = new PageParser( $doc1 );
		$pictures = $pageParser1->getPicturesList();
		$this->assertStringContainsString( $out, $pageParser1->getContent( false )->saveXML() );
		$this->assertSame( $picTitle, $pictures[$picTitle]->title );
		$this->assertSame( $picName, $pictures[$picTitle]->name );
	}

	public function provideGetPicturesList(): array {
		return [
			'Image gets a data-title attribute'  => [
				'<p><img src="foo/bar.jpg" /></p>',
				'<p><img src="foo/bar.jpg" data-title="bar.jpg"/></p>',
				'bar.jpg',
				'bar.jpg',
			],
			'Figure caption is not removed' => [
				'<figure><img src="foo/bar.jpg" /><figcaption>Lorem</figcaption></figure>',
				'<figure><img src="foo/bar.jpg" data-title="bar.jpg"/><figcaption>Lorem</figcaption></figure>',
				'bar.jpg',
				'bar.jpg',
			],
			'Title extracted from MediaWiki non-thumb URL' => [
				'<p><img src="//example.org/0/00/example.jpg" /></p>',
				'<p><img src="//example.org/0/00/example.jpg" data-title="example.jpg"/></p>',
				'example.jpg',
				'example.jpg',
			],
			'Title extracted from MediaWiki thumb URL' => [
				'<p><a class="image"><img src="//example.org/thumb/2/9A/example.jpg/500px-example.jpg" /></a></p>',
				'<p><a class="image"><img src="//example.org/thumb/2/9A/example.jpg/500px-example.jpg" data-title="example.jpg-500px-example.jpg"/></a></p>',
				'example.jpg-500px-example.jpg',
				'example.jpg',
			],
		];
	}
}
