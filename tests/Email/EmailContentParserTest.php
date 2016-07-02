<?php
declare(strict_types = 1);

namespace Externals\Test\Email;

use Externals\Application\Application;
use Externals\Email\EmailContentParser;

require_once __DIR__ . '/../../.puli/GeneratedPuliFactory.php';

class EmailContentParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailContentParser
     */
    private $parser;

    public function setUp()
    {
        $container = (new Application)->getContainer();
        $this->parser = $container->get(EmailContentParser::class);
    }

    /**
     * @test
     */
    public function should_parse_markdown()
    {
        $content = <<<MARKDOWN
This is a paragraph.

    echo 'code';

> Take that!
MARKDOWN;
        $expected = <<<HTML
<p>This is a paragraph.</p>
<pre><code>echo 'code';
</code></pre>
<blockquote>
<p>Take that!</p>
</blockquote>
HTML;
        $this->assertEquals($expected, trim($this->parser->parse($content)));
    }

    /**
     * @test
     */
    public function should_escape_html()
    {
        $content = '<strong>Test</strong> <script>alert("xss")</script>';
        $this->assertEquals('<p>&lt;strong&gt;Test&lt;/strong&gt; &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</p>', trim($this->parser->parse($content)));
    }

    /**
     * @test
     */
    public function should_keep_line_breaks()
    {
        $content = <<<MARKDOWN
This is a paragraph
that spans on 2 lines:

echo 'code';
echo 'another code;
MARKDOWN;
        $expected = <<<HTML
<p>This is a paragraph <br>
that spans on 2 lines:</p>
<p>echo 'code'; <br>
echo 'another code;</p>
HTML;
        $this->assertEquals($expected, trim($this->parser->parse($content)));
    }

    /**
     * @test
     */
    public function should_encode_html_entities()
    {
        $this->markTestSkipped('Waiting for https://github.com/thephpleague/commonmark/issues/252 to be answered');
        $content = <<<'EMAIL'
> and the test:
>
> <?php
> ini_set("pcntl.async_signals", "1");

We use 2/3 vote for "a feature affecting the language itself".
EMAIL;
        $expected = <<<HTML
<p>and the test:</p>
<blockquote>
&gt;?php
ini_set(&quot;pcntl.async_signals&quot;, &quot;1&quot;);
</blockquote>
<p>We use 2/3 vote for &quot;a feature affecting the language itself&quot;.</p>
HTML;
        $this->assertEquals($expected, trim($this->parser->parse($content)));
    }

    /**
     * @test
     */
    public function should_strip_mailing_list_signature()
    {
        $content = <<<MARKDOWN
Hello

---
PHP Internals - PHP Runtime Development Mailing List
To unsubscribe, visit: http://www.php.net/unsub.php
MARKDOWN;
        $this->assertEquals('<p>Hello</p>', trim($this->parser->parse($content)));
    }

    /**
     * @test
     */
    public function should_strip_unindented_trailing_quotation_1()
    {
        $content = <<<MARKDOWN
Hello Georges

---

From: Georges Henry gh@example.com
Sent: Friday, June 24, 2016 6:50:59 PM
To: Pierre Lefroie
Cc: PHP internals
Subject: Re: [PHP-DEV] [RFC] Asynchronous Signal Handling
MARKDOWN;
        $this->assertEquals('<p>Hello Georges</p>', trim($this->parser->parse($content)));
    }

    /**
     * @test
     */
    public function should_strip_unindented_trailing_quotation_2()
    {
        $content = <<<MARKDOWN
Hello Georges

________________________________
From: Georges Henry gh@example.com
Sent: Friday, June 24, 2016 6:50:59 PM
To: Pierre Lefroie
Cc: PHP internals
Subject: Re: [PHP-DEV] [RFC] Asynchronous Signal Handling
MARKDOWN;
        $this->assertEquals('<p>Hello Georges</p>', trim($this->parser->parse($content)));
    }

    /**
     * @test
     */
    public function should_strip_trailing_line_breaks()
    {
        $content = <<<MARKDOWN
Hello


MARKDOWN;
        $this->assertEquals('<p>Hello</p>', trim($this->parser->parse($content)));
    }

    /**
     * @test
     */
    public function should_linkify_links()
    {
        $content = 'Hello http://google.com';
        $expected = '<p>Hello <a href="http://google.com" rel="nofollow">http://google.com</a></p>';
        $this->assertEquals($expected, trim($this->parser->parse($content)));
    }

    /**
     * @test
     */
    public function should_detect_php_functions()
    {
        $content = 'Try to call preg_match() without parameters.';
        $expected = '<p>Try to call <code>preg_match()</code> without parameters.</p>';
        $this->assertEquals($expected, trim($this->parser->parse($content)));
    }

    /**
     * @test
     */
    public function should_detect_php_constants()
    {
        $content = 'Try to use PHP_INT_MAX and you will see.';
        $expected = '<p>Try to use <code>PHP_INT_MAX</code> and you will see.</p>';
        $this->assertEquals($expected, trim($this->parser->parse($content)));
    }
}
