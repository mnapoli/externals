@php($email = $item->email)

<article id="{{ $email->number }}"
         class="mb-6 overflow-hidden {{ $email->isRead ? '' : 'unread' }}">

    <header class="text-xs sm:text-sm text-gray-300 bg-gray-600 rounded-tl py-1 px-2 relative">
        <button data-target="#{{ $email->number }}-body" type="button" class="thread-collapse-button mr-2">
            <svg class="w-4 h-4 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </button>
        @if($email->from->email)
            <img class="hidden sm:block absolute top-0 right-0 mt-1 mr-2 rounded-full border border-gray-100 shadow-lg w-16 h-16 text-white bg-white" src="https://unavatar.io/{{ $email->from->email }}"
                 alt="{{ $email->from->getNameOrEmail() }}" title="{{ $email->from->getNameOrEmail() }}">
        @endif
        <a class="underline" href="#{{ $email->number }}" title="{{ $email->date->format('Y-m-d H:i') }}">{{ \Carbon\Carbon::instance($email->date)->diffForHumans() }}</a>
        by <strong>{{ $email->from->getNameOrEmail() }}</strong>
        <span class="text-xs text-gray-500">
            — <a href="/email/{{ $email->number }}/source" target="_blank" rel="nofollow">view source</a>
            — <a href="{{ \App\Support\Email\EmailReplyUrl::build($email) }}" rel="nofollow">reply</a>
        </span>
        @if(! $email->isRead)
            <div class="ml-4 inline-block bg-blue-100 rounded px-2 py-1 leading-none text-xs text-blue-600">unread</div>
        @endif
    </header>

    <div id="{{ $email->number }}-body" class="border-l border-gray-200 pl-2 sm:pl-4 pt-1 pb-1">
        <div class="email-content overflow-hidden text-sm sm:text-base mb-6">
            {!! $email->content !!}
        </div>
        <div class="sm:ml-2">
            @foreach($item->replies as $reply)
                @include('threads.thread-item', ['item' => $reply])
            @endforeach
        </div>
    </div>

</article>
