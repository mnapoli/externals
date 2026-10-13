<?php

declare(strict_types=1);

use App\Services\Rfc\RfcPageParser;

/**
 * Trimmed down export of https://wiki.php.net/rfc/pipe-operator-v3?do=export_xhtmlbody
 */
function rfcPageHtml(): string
{
    return <<<'HTML'

        <h1 class="sectionedit1" id="php_rfcpipe_operator_v3">PHP RFC: Pipe operator v3</h1>
        <div class="level1">
        <ul>
        <li class="level1"><div class="li"> Version: 0.9</div>
        </li>
        <li class="level1"><div class="li"> Date: 2025-02-05</div>
        </li>
        <li class="level1"><div class="li"> Author: Larry Garfield (larry@garfieldtech.com)</div>
        </li>
        <li class="level1"><div class="li"> Status: Implemented</div>
        </li>
        <li class="level1"><div class="li"> Target version: PHP 8.5</div>
        </li>
        <li class="level1"><div class="li"> Implementation: <a href="https://github.com/php/php-src/pull/17118" class="urlextern" title="https://github.com/php/php-src/pull/17118" rel="ugc nofollow">https://github.com/php/php-src/pull/17118</a></div>
        </li>
        </ul>
        </div>
        <!-- EDIT{&quot;target&quot;:&quot;section&quot;} -->
        <h2 class="sectionedit2" id="introduction">Introduction</h2>
        <div class="level2">
        <p>
        This <abbr title="Request for Comments">RFC</abbr> introduces the “pipe” operator. A <a href="/rfc/function-composition" class="wikilink1" title="rfc:function-composition" data-wiki-id="rfc:function-composition">function composition operator</a> is saved for later, see <code><span class="kw2">array_map</span><span class="br0">&#40;</span><span class="br0">&#41;</span></code>.
        </p>
        <pre class="code php"><span class="re0">$result</span> <span class="sy0">=</span> <span class="st0">&quot;Hello World&quot;</span> <span class="sy0">|&gt;</span> <a href="http://www.php.net/strlen"><span class="kw3">strlen</span></a><span class="br0">&#40;</span>...<span class="br0">&#41;</span><span class="sy0">;</span></pre>
        <p>
        <img src="/_media/rfc/pipe.png" class="media" alt="diagram" />
        </p>
        </div>
        <h2 class="sectionedit3" id="proposed_voting_choices">Proposed Voting Choices</h2>
        <div class="level2">
        <!-- Doodle Plugin -->
        <form action="/rfc/pipe-operator-v3#doodle__form__add_the_pipe_operator" method="post" name="doodle__form" id="doodle__form__add_the_pipe_operator" accept-charset="utf-8" >
        <input type="hidden" name="sectok" value="" />
        <table class="doodle__results">
        <tbody>
        <tr class="title_row"><th class="title_caption" style="grid-column: 1 / 4"> Add the pipe operator? </th></tr>
        <tr class="fields_row"><th class="fields_caption">Real name</th><th class="fields_data">Yes</th><th class="fields_data">No</th></tr>
        <tr class="data_row"><td class="data_caption"> <a href="https://people.php.net/alcaeus">alcaeus</a> </td><td class="data_data" style="background-color:#AFA"><img src="/lib/images/success.png" title="2025/05/14 06:37"></td><td class="data_data" style="background-color:#FCC">&nbsp;</td></tr>
        <tr class="data_row"><td class="data_caption"> <a href="https://people.php.net/alec">alec</a> </td><td class="data_data" style="background-color:#FCC">&nbsp;</td><td class="data_data" style="background-color:#AFA"><img src="/lib/images/success.png" title="2025/05/15 10:00"></td></tr>
        <tr class="results_row"><td class="results_caption">Final result:</td><td class="results_data">33</td><td class="results_data">7</td></tr>
        <tr class="title_row"><td class="title_caption" style="grid-column: 1 / 4"> This poll has been closed. </td></tr>
        </tbody>
        </table>
        </form>
        <form action="/rfc/pipe-operator-v3#doodle__form__secondary" method="post" name="doodle__form" id="doodle__form__secondary" accept-charset="utf-8" >
        <table class="doodle__results">
        <tr class="title_row"><th class="title_caption"> Secondary vote? </th></tr>
        <tr class="fields_row"><th class="fields_caption">Real name</th><th class="fields_data">A</th><th class="fields_data">B</th><th class="fields_data">Abstain</th></tr>
        <tr class="data_row"><td class="data_caption"> <a href="https://people.php.net/alec">alec</a> </td><td class="data_data">&nbsp;</td><td class="data_data"><img src="/lib/images/success.png" title="2026/10/05 16:34"></td><td class="data_data">&nbsp;</td></tr>
        <tr class="results_row"><td class="results_caption">Count:</td><td class="results_data">0</td><td class="results_data">1</td><td class="results_data">0</td></tr>
        <tr class="title_row"><td class="title_caption"> This poll will close on 2026-10-19 13:15:00 UTC. </td></tr>
        </table>
        </form>
        </div>
        <h2 class="sectionedit4" id="references">References</h2>
        <div class="level2">
        <p>
        Prior discussion: <a href="https://externals.io/message/124149" class="urlextern" rel="ugc nofollow">https://externals.io/message/124149</a> and <a href="https://news-web.php.net/php.internals/126318" class="urlextern">the announcement</a>.
        </p>
        </div>
        HTML;
}

test('parses the header of an RFC page', function (): void {
    $page = (new RfcPageParser)->parse(rfcPageHtml());

    expect($page->title)->toBe('Pipe operator v3');
    expect($page->metadata)->toBe([
        'version' => '0.9',
        'date' => '2025-02-05',
        'authors' => 'Larry Garfield (larry@garfieldtech.com)',
        'rawStatus' => 'Implemented',
        'targetVersion' => 'PHP 8.5',
        'implementation' => 'https://github.com/php/php-src/pull/17118',
    ]);
});

test('parses the votes of an RFC page', function (): void {
    $page = (new RfcPageParser)->parse(rfcPageHtml());

    expect($page->polls)->toHaveCount(2);

    [$main, $secondary] = $page->polls;
    expect($main->question)->toBe('Add the pipe operator?');
    expect($main->isClosed)->toBeTrue();
    expect($main->closesAt)->toBeNull();
    // Totals come from the wiki, voters from the table
    expect($main->choices)->toBe([['label' => 'Yes', 'count' => 33], ['label' => 'No', 'count' => 7]]);
    expect($main->voters)->toBe([
        ['name' => 'alcaeus', 'choices' => ['Yes'], 'votedAt' => '2025-05-14 06:37'],
        ['name' => 'alec', 'choices' => ['No'], 'votedAt' => '2025-05-15 10:00'],
    ]);

    expect($secondary->question)->toBe('Secondary vote?');
    expect($secondary->isClosed)->toBeFalse();
    expect($secondary->closesAt)->toBe('2026-10-19 13:15:00 UTC');
    expect($secondary->summary())->toBe('A: 0 · B: 1 · Abstain: 0');
});

test('finds the threads an RFC page links to', function (): void {
    expect((new RfcPageParser)->parse(rfcPageHtml())->linkedEmailNumbers)->toBe([124149, 126318]);
});

test('converts the text of an RFC page to markdown', function (): void {
    $content = (new RfcPageParser)->parse(rfcPageHtml())->content;

    expect($content)
        ->toStartWith("## Introduction\n\nThis RFC introduces the “pipe” operator.")
        ->toContain('[function composition operator](https://wiki.php.net/rfc/function-composition)')
        ->toContain('see `array_map()`.')
        ->toContain("```php\n\$result = \"Hello World\" |> strlen(...);\n```")
        ->toContain('![diagram](https://wiki.php.net/_media/rfc/pipe.png)')
        ->toContain('**Vote: Add the pipe operator?** — Yes: 33 · No: 7 (closed)')
        ->toContain('**Vote: Secondary vote?** — A: 0 · B: 1 · Abstain: 0 (open)')
        ->not->toContain('Version: 0.9')
        ->not->toContain('PHP RFC: Pipe operator v3')
        ->not->toContain('EDIT')
        ->not->toContain('alcaeus')
        ->not->toContain('<span')
        ->not->toContain('<div');
});
