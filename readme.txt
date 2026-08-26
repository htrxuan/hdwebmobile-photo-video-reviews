=== HDWebmobile Photo & Video Reviews ===
Contributors: htrxuan
Donate link: https://paypal.me/htrxuan/20
Tags: woocommerce, reviews, photo reviews, video reviews, product reviews
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let customers attach photos and short videos to their WooCommerce product reviews, shown right in the review list.

== Description ==

HDWebmobile Photo & Video Reviews adds an optional photo/video upload field to WooCommerce's own native product review form. Uploaded media is attached to the review and displayed in the review list on the product page, giving shoppers visual, customer-submitted proof alongside the star rating and written review.

= Why this plugin exists =
A competing "Customer Reviews for WooCommerce" plugin shipped a standalone AJAX action for uploading review photos that performed **no authentication, capability, or nonce check at all** (CVE-2026-12684), letting any unauthenticated visitor upload arbitrary image/video files straight into the Media Library. This plugin closes that entire vulnerability class by construction rather than patching around it:

* There is no standalone "upload media" endpoint of any kind, AJAX or otherwise. A file is only ever processed as a side effect of a genuine WordPress comment submission going through `wp-comments-post.php` -- WordPress's own comment pipeline (spam checks, Akismet if installed, moderation) -- never a bespoke always-open surface.
* A product-scoped nonce, generated with the review form and verified before any file is touched, is required in addition to that.
* Media is only ever attached to a comment that is confirmed to be a genuine WooCommerce product review (the comment's parent post is actually a `product`), never any other comment type.
* Every upload goes through `wp_handle_upload()` -- WordPress's own vetted upload handler -- with an explicit image/video mime allow-list, never a hand-rolled file-move.
* If the store requires a verified purchase to leave a review at all, media is only attached for reviewers who actually bought the product, mirroring WooCommerce's own review-gating setting.

= Key Features =
* Optional photo (JPG, PNG, GIF, WebP) and video (MP4, WebM, MOV) upload field added to the native WooCommerce review form -- no separate submission flow
* Up to a configurable number of files per review (default 3), each with its own configurable size cap for photos and videos separately
* Uploaded photos display as a thumbnail gallery (click to view full size); videos display as an inline HTML5 player, directly under the review text
* Video uploads can be turned off entirely to accept photos only
* Respects WooCommerce's own "verified purchase required to review" setting

= Limitations (please read before installing) =
* No moderation queue beyond WordPress's own comment moderation -- if a review is held for moderation, its media is still attached but won't display publicly until the review itself is approved
* No client-side image compression/resizing -- large photos are stored at their original resolution (subject to the configured size cap)
* One combined multi-file field for both photos and videos -- there is no separate "photos only" vs "video only" field in the UI

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/hdwebmobile-photo-video-reviews` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress. WooCommerce must already be installed and active.
3. That's it -- the upload field appears automatically on every product's review form. Adjust limits under WooCommerce > HDWebmobile > Photo & Video Reviews.

== How to Use ==

= 1. No setup required to start collecting media reviews =
As soon as the plugin is active, the "Add a review" form on every product page shows a file field for photos/videos, in addition to WooCommerce's own rating and review-text fields. There's nothing to configure to turn this on.

= 2. What a customer sees =
They pick up to the configured number of photos/videos alongside writing their review as normal, and submit. No separate step, no extra page.

= 3. What shoppers see afterward =
Uploaded photos appear as small thumbnails under the review text -- click one to view it full size in a new tab. Any uploaded video appears as a standard playable video directly under the review.

= 4. Adjusting limits =
Go to **WooCommerce > HDWebmobile > Photo & Video Reviews** to turn video uploads on/off, change the maximum number of files per review, and set separate size caps (in MB) for photos and videos.

== Screenshots ==

1. The photo/video upload field added to WooCommerce's native "Add a review" form.
2. A submitted review showing an uploaded photo thumbnail gallery.
3. The Photo & Video Reviews settings tab under WooCommerce > HDWebmobile.

== Changelog ==

= 1.0.0 =
* Initial release: photo/video upload field on the native WooCommerce review form, secure nonce+context-verified upload processing via wp_handle_upload(), configurable file count/size limits, thumbnail gallery and inline video display in the review list.
