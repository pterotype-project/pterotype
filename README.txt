=== Pterotype ===
Contributors: jdormit
Tags: ActivityPub,Fediverse,Federation
Requires at least: 4.9.8
Requires PHP: 7.2.11
License: MIT
License URI: https://github.com/jdormit/pterotype/blob/master/LICENSE
Stable tag: 1.2.0
Tested up to: 4.9.8

Pterotype expands your audience by giving your blog an ActivityPub stream, making it a part of the Fediverse.

== Description ==
Pterotype expands your audience by giving your blog an ActivityPub stream, making it a part of the Fediverse. Users of Mastodon, Pleroma, and other Fediverse services will be able to follow and share your posts from the platform of their choice.

== Changelog ==
### 1.2.0
- Fix a bug where incoming ActivityPub replies were getting duplicated if comment moderation was disabled
- Stop leaking guest (non-user) commenter email addresses in their ActivityPub usernames
- Remove the JSON column in the pterotype_objects table to allow sites running older MySQL versions to install Pterotype
- Optimize Pterotype's data storage by never storing more than one copy of the same ActivityPub object
- Optimize Pterotype's network usage by checking for local copies of objects before requesting them from their host
- Use the ActivityPub Article type for posts
- Lower the delay between receiving a Follow and sending an Accept to 2 seconds (from 5)

### 1.1.2
- Disable comment syncing for posts which have comments closed

### 1.1.1
- Implement comment syncing between WordPress and the ActivityPub feed. This allows allows people to reply to posts from Mastodon et al. and have those replies reflected as comments in the WordPress site, and vice-versa (WordPress comments become Mastodon et al. replies).
- Fix a bug involving delivering to more than 2 ActivityPub inboxes.

### 1.0.0
- Publish WordPress blog posts to an ActivityPub feed, allowing them to show up in Mastodon et al.
