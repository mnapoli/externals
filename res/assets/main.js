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

var fnmap = {
    'toggle': 'toggle',
    'show': 'add',
    'hide': 'remove' };

function collapse(selector, cmd) {
    var targets = Array.from(document.querySelectorAll(CSS.escape(selector)));
    targets.forEach(function (target) {
        target.classList[fnmap[cmd]]('show');
    });
};

// Handler that uses various data-* attributes to trigger
// specific actions, mimicing bootstraps attributes
var triggers = Array.from(document.querySelectorAll('[data-toggle="collapse"]'));

window.addEventListener('click', function (ev) {
    var elm = ev.target;
    if (triggers.includes(elm)) {
        var selector = elm.getAttribute('href');
        collapse(selector, 'toggle');
    }
}, false);
