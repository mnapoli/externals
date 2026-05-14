<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Email;

use App\Services\Email\EmailSubjectParser;
use Tests\TestCase;

class EmailSubjectParserTest extends TestCase
{
    private EmailSubjectParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new EmailSubjectParser;
    }

    public function test_should_strip_php_dev_tag(): void
    {
        $this->assertSame('Hello world', $this->parser->sanitize('[PHP-DEV] Hello world'));
    }

    public function test_should_strip_re_prefix(): void
    {
        $this->assertSame('Hello world', $this->parser->sanitize('Re: Hello world'));
    }

    public function test_should_strip_re_prefix_case_insensitively(): void
    {
        $this->assertSame('Hello world', $this->parser->sanitize('RE: Hello world'));
        $this->assertSame('Hello world', $this->parser->sanitize('re: Hello world'));
    }

    public function test_should_strip_re_prefix_without_space(): void
    {
        $this->assertSame('Hello world', $this->parser->sanitize('Re:Hello world'));
    }

    public function test_should_strip_multiple_re_prefixes(): void
    {
        $this->assertSame('Hello world', $this->parser->sanitize('Re: Re: Re: Hello world'));
    }

    public function test_should_strip_php_dev_and_re_together(): void
    {
        $this->assertSame('Hello world', $this->parser->sanitize('Re: [PHP-DEV] Hello world'));
        $this->assertSame('Hello world', $this->parser->sanitize('[PHP-DEV] Re: Hello world'));
    }

    public function test_should_leave_normal_subject_untouched(): void
    {
        $this->assertSame('Hello world', $this->parser->sanitize('Hello world'));
    }

    public function test_should_not_strip_re_in_the_middle_of_subject(): void
    {
        $this->assertSame('Talking about Re: discussions', $this->parser->sanitize('Talking about Re: discussions'));
    }
}
