=== Pterotype ===
Contributors: jdormit
Tags: ActivityPub,Fediverse,Federation
Requires at least: 4.9.8
Requires PHP: 5.6.0
License: MIT
License URI: https://github.com/jdormit/pterotype/blob/master/LICENSE
Stable tag: 1.4.3
Tested up to: 5.1.1

Pterotype expands your audience by giving your blog an ActivityPub stream, making it a part of the Fediverse.

== Description ==
Pterotype expands your audience by giving your blog an ActivityPub stream, making it a part of the Fediverse. Users of Mastodon, Pleroma, and other Fediverse services will be able to follow and share your posts from the platform of their choice.

== Changelog ==
### 1.4.3
- Fix the error from 1.4.2 the right way ¯\_(ツ)_/¯

### 1.4.2
- Fix an error where array_key_exists was being called on an argument that wasn't always an array

### 1.4.1
- This is a no-op version bump because I screwed up updating the Wordpress plugin repository version info

### 1.4.0
- Compact the actor field before delivering activities
- Fix an issue where the post global wasn't properly set when trying to get the post excerpt

### 1.3.1
- Don't do inbox forwarding to the local instance or for any activities whose object is an actor

### 1.3.0
- Fully support PHP 5.x

### 1.2.13
- Change some syntax that was only supported for PHP >= 5.5

### 1.2.12
- Revert the change made in 1.2.12, as it turns out .well-known can only be at the domain root

### 1.2.11
- Account for blogs not hosted at the root domain for WebFinger discovery

### 1.2.10
- Fix a PHP error where $wpdb->prepare was being called with only one argument

### 1.2.9
- Add opengraph metadata to site if it doesn't already have it
- Handle invalid actor slugs
- Improve handling of upserting objects into the DB

### 1.2.8
- Show Fediverse icons as comment avatars for comments from the Fediverse
- Advertise that followers get automatically approved via the manuallyApprovesFollowers field

### 1.2.7
- Fix a bug where an invalid DB state broke post federation

### 1.2.6
- Add admin dashboard where users can update the site's Fediverse identity - site name, description, and icon

### 1.2.5
- Add functionality to clean up database and tell federated servers when Pterotype is unintalled
- Hydrate actor and object fields of activities before delivery

### 1.2.4
- Fix a SQL error when initializing the plugin for the first time

### 1.2.3
- Send an Update activity when the site logo changes
- Log out activity delivery errors to the server error log

### 1.2.2
- Fix a bug where actor public keys were getting truncated before being delivered to other servers
- Fix the way icons are represented in the actor JSON

### 1.2.1
- Send an Update activity when blog details (name, tagline) change
- Fix a bug in where activities posted to the outbox had their data compacted before it was persisted

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
