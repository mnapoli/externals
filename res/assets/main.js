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

// map our commands to the classList methods
const fnmap = {
    'toggle': 'toggle',
    'show': 'add',
    'hide': 'remove'
};

const collapse = (selector, cmd) => {
    const targets = Array.from(document.querySelectorAll(selector));
    targets.forEach(target => {
        target.classList[fnmap[cmd]]('show');
    });
}

// Grab all the trigger elements on the page
const triggers = Array.from(document.querySelectorAll('[data-toggle="collapse"]'));

// Listen for click events, but only on our triggers
window.addEventListener('click', (ev) => {
    const elm = ev.target;
    if (triggers.includes(elm)) {
        const selector = elm.getAttribute('data-target');
        collapse(selector, 'toggle');
    }
}, false);