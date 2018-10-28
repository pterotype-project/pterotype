=== Pterotype ===
Contributors: jdormit
Tags: ActivityPub,Fediverse,Federation
Requires at least: 4.9.8
Requires PHP: 7.2.11
License: MIT
License URI: https://github.com/jdormit/pterotype/blob/master/LICENSE
Stable tag: 1.1.1

Pterotype expands your audience by giving your blog an ActivityPub stream, making it a part of the Fediverse.

== Description ==
Pterotype expands your audience by giving your blog an ActivityPub stream, making it a part of the Fediverse. Users of Mastodon, Pleroma, and other Fediverse services will be able to follow and share your posts from the platform of their choice.

== Changelog ==

### 1.1.1
- Implement comment syncing between WordPress and the ActivityPub feed. This allows allows people to reply to posts from Mastodon et al. and have those replies reflected as comments in the WordPress site, and vice-versa (WordPress comments become Mastodon et al. replies).
- Fix a bug involving delivering to more than 2 ActivityPub inboxes.

### 1.0.0
- Publish WordPress blog posts to an ActivityPub feed, allowing them to show up in Mastodon et al.
