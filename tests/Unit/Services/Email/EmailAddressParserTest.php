<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Email;

use App\Services\Email\EmailAddressParser;
use Tests\TestCase;

class EmailAddressParserTest extends TestCase
{
    public function test_should_parse_simple_email(): void
    {
        $identities = (new EmailAddressParser('john@example.com'))->parse();

        $this->assertCount(1, $identities);
        $this->assertSame('john@example.com', $identities[0]->email);
        $this->assertNull($identities[0]->name);
    }

    public function test_should_parse_email_with_name(): void
    {
        $identities = (new EmailAddressParser('John Doe <john@example.com>'))->parse();

        $this->assertCount(1, $identities);
        $this->assertSame('john@example.com', $identities[0]->email);
        $this->assertSame('John Doe', $identities[0]->name);
    }

    public function test_should_parse_email_with_quoted_name(): void
    {
        $identities = (new EmailAddressParser('"John Doe" <john@example.com>'))->parse();

        $this->assertCount(1, $identities);
        $this->assertSame('john@example.com', $identities[0]->email);
        $this->assertSame('John Doe', $identities[0]->name);
    }

    public function test_should_parse_email_with_parenthesised_name(): void
    {
        $identities = (new EmailAddressParser('john@example.com (John Doe)'))->parse();

        $this->assertCount(1, $identities);
        $this->assertSame('john@example.com', $identities[0]->email);
        $this->assertSame('John Doe', $identities[0]->name);
    }

    public function test_should_strip_original_marker(): void
    {
        $identities = (new EmailAddressParser('John Doe (original) <john@example.com>'))->parse();

        $this->assertCount(1, $identities);
        $this->assertSame('john@example.com', $identities[0]->email);
        $this->assertSame('John Doe', $identities[0]->name);
    }

    public function test_should_collapse_dotted_email(): void
    {
        $identities = (new EmailAddressParser('john . doe@example.com'))->parse();

        $this->assertCount(1, $identities);
        $this->assertSame('john.doe@example.com', $identities[0]->email);
    }

    public function test_should_invalidate_malformed_email(): void
    {
        $identities = (new EmailAddressParser('not-an-email'))->parse();

        $this->assertCount(1, $identities);
        $this->assertNull($identities[0]->email);
    }

    public function test_should_skip_short_names(): void
    {
        $identities = (new EmailAddressParser('Jo <jo@example.com>'))->parse();

        $this->assertCount(1, $identities);
        $this->assertSame('jo@example.com', $identities[0]->email);
        $this->assertNull($identities[0]->name);
    }

    public function test_should_skip_name_containing_url(): void
    {
        $identities = (new EmailAddressParser('http://example.com <john@example.com>'))->parse();

        $this->assertSame('john@example.com', $identities[0]->email);
        $this->assertNull($identities[0]->name);
    }
}
