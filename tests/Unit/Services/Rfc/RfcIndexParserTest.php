<?php

declare(strict_types=1);

use App\Services\Rfc\RfcIndexParser;
use App\Services\Rfc\RfcStatus;

test('parses the RFC index of the wiki', function (): void {
    $source = <<<'WIKI'
        ====== Request for Comments ======

        To create a new RFC, see [[rfc:howto|How To Create an RFC]].

        ===== In voting phase =====

          * [[rfc:end_pear_endorsement|End PEAR Project Endorsement]] (Created 2026-08-27)

        ===== Under Discussion =====

          * [[rfc:csv_extension|CSV Extension (ext/csv)]] (Created 2026-10-08)
          * [[rfc/bcrypt_sha256|Add PASSWORD_BCRYPT_SHA256 to password_hash]]
          * [[rfc::async_scheduler_abi|Concurrency Support in the PHP Engine]] (Created 2026-07-23)

        ===== Accepted =====

        ==== Pending Implementation / Landing ====

          * PHP 9.0: [[rfc:phase_out_serializable|Phasing out Serializable]] (Created 2020-12-07)

        ===== Implemented =====

        ==== PHP 8.5 ====

          * [[rfc:pipe-operator-v3|Pipe operator]]
            * [[rfc:partial_function_application_optional_placeholder|Partial Function Application: Handling of Optional Parameters]]
          * ''[[rfc:var_type|var_type()]]'' \\ Successor for ''gettype()''
          * [[https://github.com/php/php-src/pull/5067|Not an RFC page]]

        ===== Declined =====

          * [[rfc:csv_extension|Duplicate entry]]
          * [[rfc:no_title]]
        WIKI;

    $entries = (new RfcIndexParser)->parse($source);

    expect(array_map(fn($entry) => [$entry->slug, $entry->title, $entry->status, $entry->subsection, $entry->phpVersion, $entry->position], $entries))->toBe([
        ['end_pear_endorsement', 'End PEAR Project Endorsement', RfcStatus::Voting, null, null, 0],
        ['csv_extension', 'CSV Extension (ext/csv)', RfcStatus::Discussion, null, null, 1],
        ['bcrypt_sha256', 'Add PASSWORD_BCRYPT_SHA256 to password_hash', RfcStatus::Discussion, null, null, 2],
        ['async_scheduler_abi', 'Concurrency Support in the PHP Engine', RfcStatus::Discussion, null, null, 3],
        ['phase_out_serializable', 'Phasing out Serializable', RfcStatus::Accepted, 'Pending Implementation / Landing', null, 4],
        ['pipe-operator-v3', 'Pipe operator', RfcStatus::Implemented, 'PHP 8.5', '8.5', 5],
        ['partial_function_application_optional_placeholder', 'Partial Function Application: Handling of Optional Parameters', RfcStatus::Implemented, 'PHP 8.5', '8.5', 6],
        ['var_type', 'var_type()', RfcStatus::Implemented, 'PHP 8.5', '8.5', 7],
        ['no_title', 'no_title', RfcStatus::Declined, null, null, 8],
    ]);
});
