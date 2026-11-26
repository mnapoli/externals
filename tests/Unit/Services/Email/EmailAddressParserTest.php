<?php

declare(strict_types=1);

use App\Services\Email\EmailAddressParser;
use App\Support\Email\EmailAddress;
use ZBateson\MailMimeParser\MailMimeParser;

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

function parseSender(string $from): ?EmailAddress
{
    $header = (new MailMimeParser)->parse("From: $from\r\nSubject: x\r\n\r\nbody", false)->getHeader('from');

    return EmailAddressParser::sender($header);
}

test('the sender name is decoded when the mail client encoded it', function (string $from, string $name): void {
    expect(parseSender($from))->email->toBe('tim@example.com')->name->toBe($name);
})->with([
    'quoted-printable' => ['=?UTF-8?Q?Tim_D=C3=BCsterhus?= <tim@example.com>', 'Tim Düsterhus'],
    'base64' => ['=?utf-8?B?VGltIETDvHN0ZXJodXM=?= <tim@example.com>', 'Tim Düsterhus'],
]);

test('a comma in a quoted sender name is kept', function (): void {
    expect(parseSender('"Doe, John" <john@example.com>'))->email->toBe('john@example.com')->name->toBe('Doe, John');
});

test('a sender name written as a comment after the address is kept', function (): void {
    expect(parseSender('john@example.com (John Doe)'))->email->toBe('john@example.com')->name->toBe('John Doe');
});

test('a sender without a name has only an address', function (): void {
    expect(parseSender('john@example.com'))->email->toBe('john@example.com')->name->toBeNull();
});

test('an obfuscated sender address is still read', function (): void {
    expect(parseSender('John Doe <john . doe@example.com>'))->email->toBe('john.doe@example.com')->name->toBe('John Doe');
});
