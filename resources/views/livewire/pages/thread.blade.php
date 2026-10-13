<?php

use App\Actions\MarkEmailAsRead;
use App\Exceptions\NotFoundException;
use App\Models\Email;
use App\Services\Email\ThreadQuery;
use App\Services\Rfc\RfcThreads;
use App\Support\Email\ThreadStructuredData;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public int $number = 0;

    public string $subject = '';

    /** @var \App\Support\Email\ThreadItem[] */
    protected array $thread = [];

    public function mount(int $number, ThreadQuery $threads): mixed
    {
        $email = Email::where('number', $number)->first();
        if (! $email) {
            throw new NotFoundException("Email $number was not found");
        }

        if (! $email->isThreadRoot()) {
            $root = Email::find($email->threadId);
            if ($root) {
                return $this->redirect("/message/{$root->number}#$number");
            }
            // Root message not found — render the thread from this URL anyway
        }

        $user = auth()->user();
        // Build the thread view BEFORE marking the thread as read
        $this->thread = $threads->getThreadView($email, $user);
        $this->number = $number;
        $this->subject = $email->subject;

        if ($user) {
            app(MarkEmailAsRead::class)->handle($email, $user);
        }

        return null;
    }

    public function rendering(View $view): void
    {
        $view->title($this->subject . ' - Externals');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'thread' => $this->thread,
            'structuredData' => ThreadStructuredData::build($this->thread, $this->number, $this->subject),
            'rfcs' => app(RfcThreads::class)->rfcsOf($this->number),
        ];
    }
}; ?>

@push('head')
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR) !!}</script>
    <link rel="alternate" type="text/markdown" href="/message/{{ $number }}.md" title="Thread as Markdown">
@endpush

<div x-data="{
        ids: [],
        cursor: 0,
        get hasUnread() { return this.cursor < this.ids.length },
        init() { this.ids = [...this.$root.querySelectorAll('article.unread')].map(a => a.id) },
        next() {
            if (this.cursor >= this.ids.length) return;
            window.location.hash = '#' + this.ids[this.cursor];
            this.cursor++;
        },
     }"
     @keydown.window="if ($event.key === 'n' && $event.target === document.body) next()">

    <h1 class="text-lg sm:text-3xl font-light mb-8 sm:mb-12">
        <a href="/" title="Back" class="mr-1 text-gray-600 inline-block align-middle">
            <svg class="w-6 inline-block -mt-1" xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><path d='M321.94 98L158.82 237.78a24 24 0 000 36.44L321.94 414c15.57 13.34 39.62 2.28 39.62-18.22v-279.6c0-20.5-24.05-31.56-39.62-18.18z' fill="currentColor"/></svg>
        </a>
        {{ $subject }}
        <a href="/message/{{ $number }}.md" type="text/markdown" class="ml-2 align-middle text-xs font-normal text-gray-400 hover:text-gray-600" title="This thread as Markdown, for AI agents">View as Markdown</a>
    </h1>

    @if($rfcs->isNotEmpty())
        <div class="-mt-4 sm:-mt-8 mb-8 sm:mb-10 space-y-2">
            @foreach($rfcs as $rfc)
                @php($poll = $rfc->polls->first())
                <a href="/rfc/{{ $rfc->slug }}" class="flex flex-wrap items-baseline gap-x-2 gap-y-1 rounded border border-gray-200 bg-gray-50 hover:border-gray-300 px-3 py-2 text-sm">
                    <span class="text-gray-400">RFC</span>
                    <span class="text-gray-800">{{ $rfc->title }}</span>
                    <span class="rounded px-2 py-0.5 text-xs {{ $rfc->status->badgeClasses() }}">{{ $rfc->statusLabel() }}</span>
                    @if($poll?->hasVotes())
                        <span class="text-xs text-gray-500">{{ $poll->summary() }}@if(! $poll->isClosed) (vote open)@endif</span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif

    @auth
        <button x-show="hasUnread" x-cloak
                class="thread-navigation fixed animate-pulse right-0 bottom-0 z-10 shadow-lg rounded-md px-4 py-2 mr-5 mb-5 bg-blue-50 text-blue-500 text-center text-sm transition-shadow hover:shadow-xl cursor-pointer"
                type="button" title="press 'n' to jump to the next unread email"
                @click="next()">
            (n)ext unread email
        </button>
    @endauth

    @foreach($thread as $item)
        <x-thread-item :item="$item" />
    @endforeach
</div>
