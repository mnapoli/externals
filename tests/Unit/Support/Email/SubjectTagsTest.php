<?php

declare(strict_types=1);

use App\Support\Email\SubjectTags;

test('leading tags are split off the subject', function (): void {
    $subject = SubjectTags::parse('[RFC] [Discussion] Primary Constructors');

    expect($subject->tags)->toBe(['RFC', 'Discussion']);
    expect($subject->title)->toBe('Primary Constructors');
});

test('a subject without tags is kept as is', function (): void {
    $subject = SubjectTags::parse('PHP 8.4.24 Released');

    expect($subject->tags)->toBe([]);
    expect($subject->title)->toBe('PHP 8.4.24 Released');
});

test('brackets in the middle of a subject are not tags', function (): void {
    $subject = SubjectTags::parse('Re: the [] operator');

    expect($subject->tags)->toBe([]);
    expect($subject->title)->toBe('Re: the [] operator');
});

test('a subject made only of tags keeps them as title', function (): void {
    $subject = SubjectTags::parse('[RFC] [VOTE]');

    expect($subject->tags)->toBe([]);
    expect($subject->title)->toBe('[RFC] [VOTE]');
});

test('some tags can be excluded, ignoring case', function (): void {
    $subject = SubjectTags::parse('[RFC] [discussion] [VOTE] Pipes');

    expect($subject->tagsExcept('RFC', 'Discussion'))->toBe(['VOTE']);
});
