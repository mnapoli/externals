<?php

use App\Exceptions\NotFoundException;
use App\Models\Rfc;
use App\Services\Rfc\RfcContent;
use App\Services\Rfc\RfcLinkExtractor;
use App\Services\Rfc\RfcThreads;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $slug = '';

    public function mount(string $slug): mixed
    {
        $normalized = RfcLinkExtractor::normalizeSlug($slug);
        if (! Rfc::where('slug', $normalized)->exists()) {
            throw new NotFoundException("RFC $slug was not found");
        }
        if ($normalized !== $slug) {
            return $this->redirect("/rfc/$normalized");
        }
        $this->slug = $slug;

        return null;
    }

    public function rendering(View $view): void
    {
        $rfc = $this->rfc();
        $view->title('RFC: ' . $rfc->title . ' - Externals');
        // The original is on the wiki: don't compete with it in search engines
        $view->layoutData(['canonical' => $rfc->wikiUrl(), 'ogUrl' => $rfc->url()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $rfc = $this->rfc();

        return [
            'rfc' => $rfc,
            'threads' => app(RfcThreads::class)->threadsOf($rfc->slug),
            'contentHtml' => $rfc->content !== null ? app(RfcContent::class)->html($rfc) : null,
        ];
    }

    private function rfc(): Rfc
    {
        return Rfc::with('polls')->where('slug', $this->slug)->firstOrFail();
    }
}; ?>

@push('head')
    <link rel="alternate" type="text/markdown" href="/rfc/{{ $rfc->slug }}.md" title="RFC as Markdown">
@endpush

<div>
    <h1 class="text-lg sm:text-3xl font-light mb-3">
        <a href="/rfc" title="All RFCs" class="mr-1 text-gray-600 inline-block align-middle">
            <svg class="w-6 inline-block -mt-1" xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><path d='M321.94 98L158.82 237.78a24 24 0 000 36.44L321.94 414c15.57 13.34 39.62 2.28 39.62-18.22v-279.6c0-20.5-24.05-31.56-39.62-18.18z' fill="currentColor"/></svg>
        </a>
        {{ $rfc->title }}
        <a href="/rfc/{{ $rfc->slug }}.md" type="text/markdown" class="ml-2 align-middle text-xs font-normal text-gray-400 hover:text-gray-600" title="This RFC as Markdown, for AI agents">View as Markdown</a>
    </h1>

    <div class="mb-6 text-sm">
        <span class="inline-block rounded px-2 py-0.5 text-xs {{ $rfc->status->badgeClasses() }}">{{ $rfc->statusLabel() }}</span>
        @if(! $rfc->isListed)
            <span class="ml-1 text-xs text-gray-400">no longer listed on the wiki index</span>
        @endif
    </div>

    <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm mb-10">
        @foreach(['Authors' => $rfc->authors, 'Date' => $rfc->date, 'Version' => $rfc->version, 'Target' => $rfc->targetVersion, 'Implementation' => $rfc->implementation] as $label => $value)
            @if($value)
                <dt class="text-gray-400">{{ $label }}</dt>
                <dd class="text-gray-700 break-words min-w-0">{!! preg_replace('#https?://[^\s<>"]+[^\s<>".,;:)]#', '<a class="text-red-600" href="$0">$0</a>', e($value)) !!}</dd>
            @endif
        @endforeach
        <dt class="text-gray-400">Source</dt>
        <dd class="min-w-0 break-words"><a class="text-red-600" href="{{ $rfc->wikiUrl() }}">{{ Str::after($rfc->wikiUrl(), 'https://') }}</a></dd>
    </dl>

    @if($rfc->polls->isNotEmpty())
        <section class="mb-10">
            <h2 class="text-xl font-light mb-4">{{ Str::plural('Vote', $rfc->polls->count()) }}</h2>
            @foreach($rfc->polls as $poll)
                <div class="mb-6">
                    <div class="mb-2">
                        <span class="text-gray-800">{{ $poll->question }}</span>
                        <span class="text-xs text-gray-400 ml-1">{{ $poll->hasVotes() ? $poll->stateLabel() : 'no votes' }}</span>
                    </div>
                    @if(! $poll->hasVotes())
                        <div class="text-sm text-gray-500">{{ implode(' · ', array_column($poll->choices, 'label')) }}</div>
                    @else
                        @php($total = array_sum(array_column($poll->choices, 'count')))
                        <div class="space-y-1 text-sm">
                            @foreach($poll->choices as $choice)
                                <div class="flex items-center gap-3">
                                    <div class="w-28 shrink-0 truncate text-gray-600" title="{{ $choice['label'] }}">{{ $choice['label'] }}</div>
                                    <div class="flex-grow bg-gray-100 rounded h-3 overflow-hidden">
                                        <div class="h-3 {{ $loop->first ? 'bg-green-400' : 'bg-gray-400' }}" style="width: {{ round($choice['count'] / $total * 100, 1) }}%"></div>
                                    </div>
                                    <div class="w-10 shrink-0 text-right text-gray-700">{{ $choice['count'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if($poll->voters)
                        <details class="mt-2 text-sm">
                            <summary class="cursor-pointer text-gray-400 hover:text-gray-600">{{ count($poll->voters) }} {{ Str::plural('voter', count($poll->voters)) }}</summary>
                            <table class="mt-2 text-xs">
                                @foreach($poll->voters as $voter)
                                    <tr>
                                        <td class="pr-4 py-0.5"><a class="text-gray-700 hover:text-red-600" href="https://people.php.net/{{ $voter['name'] }}">{{ $voter['name'] }}</a></td>
                                        <td class="pr-4 py-0.5 text-gray-600">{{ implode(', ', $voter['choices']) }}</td>
                                        <td class="py-0.5 text-gray-400">{{ $voter['votedAt'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </details>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    <section class="mb-10">
        <h2 class="text-xl font-light mb-4">Discussions on the mailing list</h2>
        @forelse($threads as $thread)
            <div wire:key="thread-{{ $thread->number }}" class="py-2 border-b border-gray-100">
                <a class="text-gray-800 hover:text-red-600" href="/message/{{ $thread->number }}">{{ $thread->subject }}</a>
                <div class="text-xs text-gray-400">
                    <time datetime="{{ $thread->date->toAtomString() }}">{{ $thread->date->format('M j, Y') }}</time>
                    @if($thread->fromName) by {{ $thread->fromName }} @endif
                    · {{ $thread->emailCount }} {{ Str::plural('message', $thread->emailCount) }}
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">
                No thread found. Threads are linked to an RFC when their first email links to the RFC page, or when the RFC page links to them.
            </p>
        @endforelse
    </section>

    <section>
        <h2 class="text-xl font-light mb-1">RFC text</h2>
        <p class="text-xs text-gray-400 mb-4">
            Copied from <a class="underline" href="{{ $rfc->wikiUrl() }}">wiki.php.net</a>@if($rfc->fetchedAt), last synchronized {{ $rfc->fetchedAt->format('M j, Y H:i') }} UTC @endif
        </p>
        @if($contentHtml !== null)
            <div class="rfc-content text-sm sm:text-base">
                {!! $contentHtml !!}
            </div>
        @else
            <p class="text-sm text-gray-500">The text of this RFC has not been imported yet: read it on <a class="underline" href="{{ $rfc->wikiUrl() }}">wiki.php.net</a>.</p>
        @endif
    </section>
</div>
