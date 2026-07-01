<?php

declare(strict_types=1);

use App\Services\Email\EmailSubjectParser;

beforeEach(function (): void {
    $this->parser = new EmailSubjectParser;
});

test('should strip php dev tag', function (): void {
    $this->assertSame('Hello world', $this->parser->sanitize('[PHP-DEV] Hello world'));
});

test('should strip re prefix', function (): void {
    $this->assertSame('Hello world', $this->parser->sanitize('Re: Hello world'));
});

test('should strip re prefix case insensitively', function (): void {
    $this->assertSame('Hello world', $this->parser->sanitize('RE: Hello world'));
    $this->assertSame('Hello world', $this->parser->sanitize('re: Hello world'));
});

test('should strip re prefix without space', function (): void {
    $this->assertSame('Hello world', $this->parser->sanitize('Re:Hello world'));
});

test('should strip multiple re prefixes', function (): void {
    $this->assertSame('Hello world', $this->parser->sanitize('Re: Re: Re: Hello world'));
});

test('should strip php dev and re together', function (): void {
    $this->assertSame('Hello world', $this->parser->sanitize('Re: [PHP-DEV] Hello world'));
    $this->assertSame('Hello world', $this->parser->sanitize('[PHP-DEV] Re: Hello world'));
});

test('should leave normal subject untouched', function (): void {
    $this->assertSame('Hello world', $this->parser->sanitize('Hello world'));
});

test('should not strip re in the middle of subject', function (): void {
    $this->assertSame('Talking about Re: discussions', $this->parser->sanitize('Talking about Re: discussions'));
});
