@extends('layout')

@section('pageTitle', $subject . ' - Externals')

@section('content')

    <h1 class="text-lg sm:text-3xl font-light mb-8 sm:mb-12">
        <a href="/" title="Back" class="mr-1 text-gray-600">
            <svg class="w-6 inline-block -mt-1" xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><path d='M321.94 98L158.82 237.78a24 24 0 000 36.44L321.94 414c15.57 13.34 39.62 2.28 39.62-18.22v-279.6c0-20.5-24.05-31.56-39.62-18.18z' fill="currentColor"/></svg>
        </a>
        {{ $subject }}
    </h1>

    @if(!empty($user))
        <button class="thread-navigation fixed animate-pulse right-0 bottom-0 z-10 bg-white shadow-lg rounded-md px-4 py-2 mr-5 mb-5 bg-blue-50 text-blue-500 text-center text-sm transition-shadow hover:shadow-xl"
             id="next-unread" type="button" title="press 'n' to jump to the next unread email">
            (n)ext unread email
        </button>
    @endif

    @foreach($thread as $item)
        @include('threads.thread-item', ['item' => $item])
    @endforeach

@endsection

@section('scripts')
    @parent

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Collapse email
            $('.thread-collapse-button').click(function() {
                const target = $(this).data('target');
                $(target).toggle();
            });

            var unreadEmailIds = $('article.unread').map(function() {
                return this.id;
            }).get();

            var i = 0;
            var nextUnread = function() {
                if ((i + 1) > unreadEmailIds.length) {
                    return;
                }

                window.location.hash = '#' + unreadEmailIds[i];
                i++;

                // hide navigation when no more unread messages left
                if ((i + 1) > unreadEmailIds.length) {
                    $('.thread-navigation').remove();
                }
            };

            $('#next-unread').click(nextUnread);
            $(document).keypress(function(event) {
                // 'n' shortcut to jump to the next unread msg
                // when typing occurs outside of input fields (like our search)
                if ( event.target == document.body && event.which == 110) {
                    nextUnread();
                }
            });

            if (unreadEmailIds.length === 0) {
                $('.thread-navigation').remove();
            }
        });
    </script>
@endsection
