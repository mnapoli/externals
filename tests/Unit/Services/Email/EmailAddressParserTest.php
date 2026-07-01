<?php

declare(strict_types=1);

use App\Services\Email\EmailAddressParser;

test('should parse simple email', function (): void {
    $identities = (new EmailAddressParser('john@example.com'))->parse();

    $this->assertCount(1, $identities);
    $this->assertSame('john@example.com', $identities[0]->email);
    $this->assertNull($identities[0]->name);
});

test('should parse email with name', function (): void {
    $identities = (new EmailAddressParser('John Doe <john@example.com>'))->parse();

    $this->assertCount(1, $identities);
    $this->assertSame('john@example.com', $identities[0]->email);
    $this->assertSame('John Doe', $identities[0]->name);
});

test('should parse email with quoted name', function (): void {
    $identities = (new EmailAddressParser('"John Doe" <john@example.com>'))->parse();

    $this->assertCount(1, $identities);
    $this->assertSame('john@example.com', $identities[0]->email);
    $this->assertSame('John Doe', $identities[0]->name);
});

test('should parse email with parenthesised name', function (): void {
    $identities = (new EmailAddressParser('john@example.com (John Doe)'))->parse();

    $this->assertCount(1, $identities);
    $this->assertSame('john@example.com', $identities[0]->email);
    $this->assertSame('John Doe', $identities[0]->name);
});

test('should strip original marker', function (): void {
    $identities = (new EmailAddressParser('John Doe (original) <john@example.com>'))->parse();

    $this->assertCount(1, $identities);
    $this->assertSame('john@example.com', $identities[0]->email);
    $this->assertSame('John Doe', $identities[0]->name);
});

test('should collapse dotted email', function (): void {
    $identities = (new EmailAddressParser('john . doe@example.com'))->parse();

    $this->assertCount(1, $identities);
    $this->assertSame('john.doe@example.com', $identities[0]->email);
});

test('should invalidate malformed email', function (): void {
    $identities = (new EmailAddressParser('not-an-email'))->parse();

    $this->assertCount(1, $identities);
    $this->assertNull($identities[0]->email);
});

test('should skip short names', function (): void {
    $identities = (new EmailAddressParser('Jo <jo@example.com>'))->parse();

    $this->assertCount(1, $identities);
    $this->assertSame('jo@example.com', $identities[0]->email);
    $this->assertNull($identities[0]->name);
});

test('should skip name containing url', function (): void {
    $identities = (new EmailAddressParser('http://example.com <john@example.com>'))->parse();

    $this->assertSame('john@example.com', $identities[0]->email);
    $this->assertNull($identities[0]->name);
});
