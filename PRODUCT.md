# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **PHP developers who are not subscribed to the internals mailing list.** They want to follow what is happening in PHP's development (new RFCs, important debates, votes) without living in a mailing list.
- **PHP core developers and RFC authors.** They use the site as a reader for the list: unread tracking, permalinks to a message, replying by email.

AI agents and tools also read the site (Markdown views, JSON API, RSS, `llms.txt`), but they are a secondary audience and must not drive the human interface.

## Product Purpose

externals.io opens PHP's #internals mailing list to the outside: it turns the list's archive into threads you can browse, read, vote on and link to.

Success means a visitor:
- sees what is new since their last visit;
- picks out what matters in the noise: new RFCs, new important discussions;
- knows where an RFC stands (status, vote, linked discussions).

## Positioning

It is the only place where the internals mailing list and the RFC wiki are woven together: RFC status chips on threads, RFC pages with their official polls, voters and every discussion that mentions them.

## Operating Context

- Content is plain-text email: hard-wrapped around 72 columns, heavily quoted, with signatures. Threads are trees built from `In-Reply-To`; some are long (90+ messages) and deeply nested (18 levels).
- New messages are synced from the PHP NNTP server every 15 minutes.
- Logged-in users (GitHub login) get read tracking, an "n" next-unread shortcut and votes.

## Capabilities and Constraints

- Laravel + Livewire/Volt + Tailwind v4, serverless on AWS Lambda via Bref.
- Pages: latest threads, top, RFC list and RFC pages, thread pages, search (Algolia), stats, news.
- Signals that coexist and must stay visually distinct:
  - thread votes (▲/▼, net score; drives "Top");
  - per-message emoji reactions, GitHub's set (👍 👎 😄 🎉 😕 ❤️ 🚀 👀), shown on the thread page only, never in the thread list;
  - the RFC "community vote" (▲/▼ counts);
  - the official RFC polls by php-src voters, which must never be confused with the community signals.
- Thread view stays a tree. Indentation is capped; deeper replies show who they answer. Subtrees collapse. Long threads get an outline and a "first unread" jump.
- New replies can land anywhere in the tree, not only at the end: unread tracking and the outline must surface unread messages wherever they sit.
- Every thread, message and RFC keeps a stable URL and its `.md` alternative. The `.md` views serve agents (footer, `Accept` header); they are not promoted in the human UI.

## Product Principles

1. **Signal over noise.** The list is noisy; the site's value is surfacing what matters (new RFCs, important discussions, votes) before everything else.
2. **The tree is the core.** Who answers whom is the structure of a debate; never flatten it, make it navigable.
3. **Official is not community.** Official RFC votes and community signals (votes, reactions) are always visibly separate.
4. **Respect the source.** Emails are shown faithfully (links, source, reply by email); presentation improves reading without rewriting what people wrote.

## Accessibility & Inclusion

WCAG AA: readable contrast for metadata, visible keyboard focus, labelled controls, reduced motion respected.
