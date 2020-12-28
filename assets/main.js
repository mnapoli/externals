var jquery = require('jquery');
window.$ = window.jQuery = jquery;

$(function () {
    $('body')
        .on('click', '.upvote', function(e) {
            e.preventDefault();
            vote($(this), 1);
        })
        .on('click', '.downvote', function(e) {
            e.preventDefault();
            vote($(this), -1);
        });
});

function vote($voteLink, value) {
    if (typeof userId === 'undefined') {
        alert('You must log in to vote. To log in, click the "log in" link in the top-right corner.');
        return;
    }

    var emailId = $voteLink.data('email-id');
    if ($voteLink.hasClass('active')) {
        value = 0;
    }

    $.post('/votes/' + emailId, {
        userId: userId,
        value: value,
    })
    .done(function(data) {
        $voteLink.parent().find('.vote-action').removeClass('active');
        if (data.newValue === 1) {
            $voteLink.parent().find('.upvote').addClass('active');
        } else if (data.newValue === -1) {
            $voteLink.parent().find('.downvote').addClass('active');
        }
        $voteLink.parent().find('.vote-value').text(data.newTotal);
    })
    .fail(function() {
        alert('An error occurred');
    });
}
