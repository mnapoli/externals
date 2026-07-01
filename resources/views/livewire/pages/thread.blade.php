<?php

use App\Actions\MarkEmailAsRead;
use App\Exceptions\NotFoundException;
use App\Models\Email;
use App\Services\Email\ThreadQuery;
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
        return ['thread' => $this->thread];
    }
}; ?>

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
    </h1>

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
