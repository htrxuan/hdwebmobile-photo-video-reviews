# HDWebmobile Photo & Video Reviews

A WooCommerce plugin that lets customers attach photos and short videos to their product reviews, shown right in the review list on the product page.

- **WordPress.org:** https://wordpress.org/plugins/hdwebmobile-photo-video-reviews/ (pending first submission)
- **Requires:** WordPress 6.9+, WooCommerce, PHP 7.4+
- **License:** GPLv2 or later

## Why this plugin exists

A competing "Customer Reviews for WooCommerce" plugin shipped a standalone AJAX action for uploading review photos that performed **no authentication, capability, or nonce check at all** (CVE-2026-12684), letting any unauthenticated visitor upload arbitrary image/video files straight into the WordPress Media Library.

This plugin closes that entire vulnerability class by construction rather than patching around it:

- **No standalone upload endpoint exists at all** — a file is only ever processed as a side effect of a genuine WordPress comment submission going through `wp-comments-post.php`, WordPress's own comment pipeline (spam checks, Akismet if installed, moderation).
- A **product-scoped nonce**, generated with the review form and verified before any file is touched, is required in addition to that.
- Media is **only ever attached to a comment confirmed to be a genuine WooCommerce product review** (the comment's parent post is actually a `product`), never any other comment type.
- Every upload goes through **`wp_handle_upload()`** — WordPress's own vetted upload handler — with an explicit image/video mime allow-list, never a hand-rolled file-move.
- If the store requires a verified purchase to leave a review, media is only attached for reviewers who actually bought the product.

## Features

- Optional photo (JPG, PNG, GIF, WebP) and video (MP4, WebM, MOV) upload field added to the native WooCommerce review form
- Configurable max files per review (default 3) and separate size caps for photos vs. videos
- Uploaded photos display as a thumbnail gallery; videos display as an inline HTML5 player
- Video uploads can be disabled entirely (photos only)
- Settings tab under **WooCommerce > HDWebmobile > Photo & Video Reviews**

## Development

Standard WordPress plugin structure:

```
hdwebmobile-photo-video-reviews.php   Bootstrap
includes/class-hdpvr-activator.php    Activation
includes/class-hdpvr-core.php         Orchestrator
includes/class-hdpvr-upload-handler.php  Secure upload processing (the security-critical piece)
includes/class-hdpvr-frontend.php     Review-form field injection + media display
includes/class-hdpvr-admin.php        Settings + hub-tab registration
includes/class-hdpvr-hub.php          Shared WooCommerce > HDWebmobile admin page (per-plugin-namespaced copy)
```

Part of the [HDWebmobile](https://hdwebmobile.com/plugins/) suite of focused, single-purpose WooCommerce plugins.

## License

GPLv2 or later. See [LICENSE](LICENSE).
