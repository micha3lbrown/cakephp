<?php
/**
 * TextHelperTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\TextHelper;
use Cake\View\View;

class TextHelperTestObject extends TextHelper {

	public function attach(StringMock $string) {
		$this->_engine = $string;
	}

	public function engine() {
		return $this->_engine;
	}

}

/**
 * StringMock class
 */
class StringMock {
}

/**
 * TextHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class TextHelperTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->View = new View(null);
		$this->Text = new TextHelper($this->View);

		$this->_appNamespace = Configure::read('App.namespace');
		Configure::write('App.namespace', 'TestApp');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Text, $this->View);
		Configure::write('App.namespace', $this->_appNamespace);
		parent::tearDown();
	}

/**
 * test String class methods are called correctly
 */
	public function testTextHelperProxyMethodCalls() {
		$methods = array(
			'highlight', 'stripLinks', 'truncate', 'excerpt', 'toList',
			);
		$String = $this->getMock(__NAMESPACE__ . '\StringMock', $methods);
		$Text = new TextHelperTestObject($this->View, array('engine' => __NAMESPACE__ . '\StringMock'));
		$Text->attach($String);
		foreach ($methods as $method) {
			$String->expects($this->at(0))->method($method);
			$Text->{$method}('who', 'what', 'when', 'where', 'how');
		}
	}

/**
 * test engine override
 */
	public function testEngineOverride() {
		App::build(array(
			'Utility' => array(CAKE . 'Test/TestApp/Utility/')
		), App::REGISTER);
		$Text = new TextHelperTestObject($this->View, array('engine' => 'TestAppEngine'));
		$this->assertInstanceOf('TestApp\Utility\TestAppEngine', $Text->engine());

		App::build(array(
			'Plugin' => array(CAKE . 'Test/TestApp/Plugin/')
		));
		Plugin::load('TestPlugin');
		$Text = new TextHelperTestObject($this->View, array('engine' => 'TestPlugin.TestPluginEngine'));
		$this->assertInstanceOf('TestPlugin\Utility\TestPluginEngine', $Text->engine());
		Plugin::unload('TestPlugin');
	}

/**
 * testAutoLink method
 *
 * @return void
 */
	public function testAutoLink() {
		$text = 'This is a test text';
		$expected = 'This is a test text';
		$result = $this->Text->autoLink($text);
		$this->assertEquals($expected, $result);

		$text = 'Text with a partial www.cakephp.org URL and test@cakephp.org email address';
		$result = $this->Text->autoLink($text);
		$expected = 'Text with a partial <a href="http://www.cakephp.org">www.cakephp.org</a> URL and <a href="mailto:test@cakephp\.org">test@cakephp\.org</a> email address';
		$this->assertRegExp('#^' . $expected . '$#', $result);

		$text = 'This is a test text with URL http://www.cakephp.org';
		$expected = 'This is a test text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a>';
		$result = $this->Text->autoLink($text);
		$this->assertEquals($expected, $result);

		$text = 'This is a test text with URL http://www.cakephp.org and some more text';
		$expected = 'This is a test text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a> and some more text';
		$result = $this->Text->autoLink($text);
		$this->assertEquals($expected, $result);

		$text = "This is a test text with URL http://www.cakephp.org\tand some more text";
		$expected = "This is a test text with URL <a href=\"http://www.cakephp.org\">http://www.cakephp.org</a>\tand some more text";
		$result = $this->Text->autoLink($text);
		$this->assertEquals($expected, $result);

		$text = 'This is a test text with URL http://www.cakephp.org(and some more text)';
		$expected = 'This is a test text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a>(and some more text)';
		$result = $this->Text->autoLink($text);
		$this->assertEquals($expected, $result);

		$text = 'This is a test text with URL http://www.cakephp.org';
		$expected = 'This is a test text with URL <a href="http://www.cakephp.org" class="link">http://www.cakephp.org</a>';
		$result = $this->Text->autoLink($text, array('class' => 'link'));
		$this->assertEquals($expected, $result);

		$text = 'This is a test text with URL http://www.cakephp.org';
		$expected = 'This is a test text with URL <a href="http://www.cakephp.org" class="link" id="MyLink">http://www.cakephp.org</a>';
		$result = $this->Text->autoLink($text, array('class' => 'link', 'id' => 'MyLink'));
		$this->assertEquals($expected, $result);
	}

/**
 * Test escaping for autoLink
 *
 * @return void
 */
	public function testAutoLinkEscape() {
		$text = 'This is a <b>test</b> text with URL http://www.cakephp.org';
		$expected = 'This is a &lt;b&gt;test&lt;/b&gt; text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a>';
		$result = $this->Text->autoLink($text);
		$this->assertEquals($expected, $result);

		$text = 'This is a <b>test</b> text with URL http://www.cakephp.org';
		$expected = 'This is a <b>test</b> text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a>';
		$result = $this->Text->autoLink($text, array('escape' => false));
		$this->assertEquals($expected, $result);
	}

/**
 * Data provider for autoLinking
 */
	public static function autoLinkProvider() {
		return array(
			array(
				'This is a test text',
				'This is a test text',
			),
			array(
				'This is a test that includes (www.cakephp.org)',
				'This is a test that includes (<a href="http://www.cakephp.org">www.cakephp.org</a>)',
			),
			array(
				'This is a test that includes www.cakephp.org:8080',
				'This is a test that includes <a href="http://www.cakephp.org:8080">www.cakephp.org:8080</a>',
			),
			array(
				'This is a test that includes http://de.wikipedia.org/wiki/Kanton_(Schweiz)#fragment',
				'This is a test that includes <a href="http://de.wikipedia.org/wiki/Kanton_(Schweiz)#fragment">http://de.wikipedia.org/wiki/Kanton_(Schweiz)#fragment</a>',
			),
			array(
				'This is a test that includes www.wikipedia.org/wiki/Kanton_(Schweiz)#fragment',
				'This is a test that includes <a href="http://www.wikipedia.org/wiki/Kanton_(Schweiz)#fragment">www.wikipedia.org/wiki/Kanton_(Schweiz)#fragment</a>',
			),
			array(
				'This is a test that includes http://example.com/test.php?foo=bar text',
				'This is a test that includes <a href="http://example.com/test.php?foo=bar">http://example.com/test.php?foo=bar</a> text',
			),
			array(
				'This is a test that includes www.example.com/test.php?foo=bar text',
				'This is a test that includes <a href="http://www.example.com/test.php?foo=bar">www.example.com/test.php?foo=bar</a> text',
			),
			array(
				'Text with a partial www.cakephp.org URL',
				'Text with a partial <a href="http://www.cakephp.org">www.cakephp.org</a> URL',
			),
			array(
				'Text with a partial WWW.cakephp.org URL',
				'Text with a partial <a href="http://WWW.cakephp.org">WWW.cakephp.org</a> URL',
			),
			array(
				'Text with a partial WWW.cakephp.org &copy, URL',
				'Text with a partial <a href="http://WWW.cakephp.org">WWW.cakephp.org</a> &amp;copy, URL',
			),
			array(
				'Text with a url www.cot.ag/cuIb2Q and more',
				'Text with a url <a href="http://www.cot.ag/cuIb2Q">www.cot.ag/cuIb2Q</a> and more',
			),
			array(
				'Text with a url http://www.does--not--work.com and more',
				'Text with a url <a href="http://www.does--not--work.com">http://www.does--not--work.com</a> and more',
			),
			array(
				'Text with a url http://www.not--work.com and more',
				'Text with a url <a href="http://www.not--work.com">http://www.not--work.com</a> and more',
			),
		);
	}

/**
 * testAutoLinkUrls method
 *
 * @dataProvider autoLinkProvider
 * @return void
 */
	public function testAutoLinkUrls($text, $expected) {
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEquals($expected, $result);
	}

/**
 * Test the options for autoLinkUrls
 *
 * @return void
 */
	public function testAutoLinkUrlsOptions() {
		$text = 'Text with a partial www.cakephp.org URL';
		$expected = 'Text with a partial <a href="http://www.cakephp.org" \s*class="link">www.cakephp.org</a> URL';
		$result = $this->Text->autoLinkUrls($text, array('class' => 'link'));
		$this->assertRegExp('#^' . $expected . '$#', $result);

		$text = 'Text with a partial WWW.cakephp.org &copy; URL';
		$expected = 'Text with a partial <a href="http://WWW.cakephp.org"\s*>WWW.cakephp.org</a> &copy; URL';
		$result = $this->Text->autoLinkUrls($text, array('escape' => false));
		$this->assertRegExp('#^' . $expected . '$#', $result);
	}

/**
 * Test autoLinkUrls with the escape option.
 *
 * @return void
 */
	public function testAutoLinkUrlsEscape() {
		$text = 'Text with a partial <a href="http://www.cakephp.org">link</a> link';
		$expected = 'Text with a partial <a href="http://www.cakephp.org">link</a> link';
		$result = $this->Text->autoLinkUrls($text, array('escape' => false));
		$this->assertEquals($expected, $result);

		$text = 'Text with a partial <iframe src="http://www.cakephp.org" /> link';
		$expected = 'Text with a partial <iframe src="http://www.cakephp.org" /> link';
		$result = $this->Text->autoLinkUrls($text, array('escape' => false));
		$this->assertEquals($expected, $result);

		$text = 'Text with a partial <iframe src="http://www.cakephp.org" /> link';
		$expected = 'Text with a partial &lt;iframe src=&quot;http://www.cakephp.org&quot; /&gt; link';
		$result = $this->Text->autoLinkUrls($text, array('escape' => true));
		$this->assertEquals($expected, $result);

		$text = 'Text with a url <a href="http://www.not-working-www.com">www.not-working-www.com</a> and more';
		$expected = 'Text with a url &lt;a href=&quot;http://www.not-working-www.com&quot;&gt;www.not-working-www.com&lt;/a&gt; and more';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEquals($expected, $result);

		$text = 'Text with a url www.not-working-www.com and more';
		$expected = 'Text with a url <a href="http://www.not-working-www.com">www.not-working-www.com</a> and more';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEquals($expected, $result);

		$text = 'Text with a url http://www.not-working-www.com and more';
		$expected = 'Text with a url <a href="http://www.not-working-www.com">http://www.not-working-www.com</a> and more';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEquals($expected, $result);

		$text = 'Text with a url http://www.www.not-working-www.com and more';
		$expected = 'Text with a url <a href="http://www.www.not-working-www.com">http://www.www.not-working-www.com</a> and more';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEquals($expected, $result);
	}

/**
 * testAutoLinkEmails method
 *
 * @return void
 */
	public function testAutoLinkEmails() {
		$text = 'This is a test text';
		$expected = 'This is a test text';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEquals($expected, $result);

		$text = 'Text with email@example.com address';
		$expected = 'Text with <a href="mailto:email@example.com"\s*>email@example.com</a> address';
		$result = $this->Text->autoLinkEmails($text);
		$this->assertRegExp('#^' . $expected . '$#', $result);

		$text = "Text with o'hare._-bob@example.com address";
		$expected = 'Text with <a href="mailto:o&#039;hare._-bob@example.com">o&#039;hare._-bob@example.com</a> address';
		$result = $this->Text->autoLinkEmails($text);
		$this->assertEquals($expected, $result);

		$text = 'Text with email@example.com address';
		$expected = 'Text with <a href="mailto:email@example.com" \s*class="link">email@example.com</a> address';
		$result = $this->Text->autoLinkEmails($text, array('class' => 'link'));
		$this->assertRegExp('#^' . $expected . '$#', $result);
	}

/**
 * test invalid email addresses.
 *
 * @return void
 */
	public function testAutoLinkEmailInvalid() {
		$result = $this->Text->autoLinkEmails('this is a myaddress@gmx-de test');
		$expected = 'this is a myaddress@gmx-de test';
		$this->assertEquals($expected, $result);
	}

/**
 * testAutoParagraph method
 *
 * @return void
 */
	public function testAutoParagraph() {
		$text = 'This is a test text';
		$expected = <<<TEXT
<p>This is a test text</p>

TEXT;
		$result = $this->Text->autoParagraph($text);
		$text = 'This is a <br/> <BR> test text';
		$expected = <<<TEXT
<p>This is a </p>
<p> test text</p>

TEXT;
		$result = $this->Text->autoParagraph($text);
		$this->assertEquals($expected, $result);
		$result = $this->Text->autoParagraph($text);
		$text = 'This is a <BR id="test"/><br class="test"> test text';
		$expected = <<<TEXT
<p>This is a </p>
<p> test text</p>

TEXT;
		$text = <<<TEXT
This is a test text.
This is a line return.
TEXT;
		$expected = <<<TEXT
<p>This is a test text.<br />
This is a line return.</p>

TEXT;
		$result = $this->Text->autoParagraph($text);
		$this->assertEquals($expected, $result);
		$text = <<<TEXT
This is a test text.

This is a new line.
TEXT;
		$expected = <<<TEXT
<p>This is a test text.</p>
<p>This is a new line.</p>

TEXT;
		$result = $this->Text->autoParagraph($text);
		$this->assertEquals($expected, $result);
	}

}