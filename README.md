# DOT Rwanda Moodle Theme

Custom Moodle 5.x theme for the **Digital Opportunity Trust Rwanda** DSE Programme.

## Design System

| Token | Value |
|---|---|
| Primary colour | `#0777AD` (DOT Blue) |
| Secondary colour | `#828282` (DOT Grey) |
| Font family | Poppins (Google Fonts) |
| Theme mode | Dark-first |

## File Structure

```
dotrwanda/
├── config.php          # Theme registration, layout map
├── lib.php             # SCSS callbacks, Google OAuth helper
├── version.php         # Plugin version metadata
├── scss/
│   └── post.scss       # All custom styles (compiled after Boost)
├── layout/
│   └── login.php       # Full custom login page layout
├── templates/          # Mustache overrides (add as needed)
├── pix/                # Theme images & logo overrides
└── lang/en/
    └── theme_dotrwanda.php
```

## Google OAuth Setup

1. Install the [moodle-auth_googleoauth2](https://moodle.org/plugins/auth_googleoauth2) plugin.
2. Site Administration → Plugins → Authentication → Google OAuth2 → configure Client ID & Secret.
3. Enable the plugin. The "Continue with Google" button will appear automatically on the login page.

## Logo

Upload your DOT Rwanda logo PNG via:
**Site Administration → Appearance → Logos**

The login page will render it at 70px height with an inverted white filter against the blue panel. For the white panel variant, upload a version with the DOT Blue wordmark and remove the `filter: brightness(0) invert(1)` line in `post.scss`.

## Deployment

Push to your git remote — GitHub Actions will deploy to your Moodle instance automatically.
After deployment: **Site Administration → Notifications** to trigger any DB upgrades, then **Purge all caches**.

## Colours Reference

```scss
$dot-blue:       #0777AD;
$dot-blue-dark:  #055a82;
$dot-blue-light: #3aa0d4;
$dot-grey:       #828282;
$dot-dark:       #0d1117;   // page background
$dot-surface:    #161b22;   // navbar, panels
$dot-surface-2:  #1c2330;   // cards
$dot-surface-3:  #21262d;   // inputs, code
$dot-border:     #2a3441;
```
