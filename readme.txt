=== iDenfy | Identity verification service ===
Contributors: iDenfy
Tags: identity-verification, kyc, kyb, aml, fraud-prevention
Requires at least: 4.9
Tested up to: 7.0
Stable tag: 1.1.0
Requires PHP: 7.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Verify people and businesses on your WordPress site with iDenfy — KYC identity checks and KYB business verification for AML compliance.

== Description ==

[iDenfy](https://www.idenfy.com) is an identity verification service that lets you onboard and verify your users straight from WordPress. Add a verification button or embed a full business-verification flow with a shortcode — no coding required.

Verification runs on iDenfy's platform and includes:

* ID document verification
* Facial recognition and 3D liveness detection
* AML screening for KYC/AML compliance
* 24/7 human supervision

= What this plugin does =

* **Identity verification (KYC)** — add the `[IDENFY]` shortcode anywhere to show a verification button. Visitors complete identity verification in a hosted iDenfy flow.
* **Business verification (KYB)** — embed business verification with the `[IDENFY_KYB]` shortcode. Pick the flow, theme, and questionnaire per page, tag sessions with your own order or reference IDs, gate a form until verification finishes, or redirect the visitor on success.
* **Button customization** — change the verification button text, colors, and styling with a live preview, or add your own CSS.

https://www.youtube.com/watch?v=N9KGl7OvJxg

== Installation ==

1. Install and activate the plugin.
2. Open **iDenfy → Settings** and enter your iDenfy API Key and API Secret. No account yet? [Create one here](https://www.idenfy.com/get-started/?source=wordpress).
3. Once your credentials are saved and verified, copy the `[IDENFY]` shortcode (KYC) or `[IDENFY_KYB]` shortcode (KYB) and add it to any page or post.

Business verification (KYB) requires a KYB flow configured in your iDenfy dashboard and KYB session creation enabled on your account by iDenfy support.

== Frequently Asked Questions ==

= Do I need an iDenfy account? =

Yes. You need an iDenfy account with an API Key and Secret. You can create one at https://www.idenfy.com/get-started/?source=wordpress.

= How do I add identity (KYC) verification? =

Add the `[IDENFY]` shortcode to any page or post. It renders a button that starts the verification flow.

= How do I add business (KYB) verification? =

Add the `[IDENFY_KYB]` shortcode. You can target a specific flow, theme, or questionnaire and react when verification finishes. The KYB tab in the plugin settings lists every attribute with examples.

== Screenshots ==

1. Identity (KYC) verification completed successfully.
2. Customization tab — change the verification button's text, colors, and styling with a live preview.
3. KYC identity verification running in an embedded window on a WordPress page.
4. KYB business verification embedded on a page, with a "Next" button locked until verification finishes.
5. After KYB verification finishes, the "Next" button unlocks so the visitor can continue.

== Changelog ==

= 1.1.0 =
* KYB added
* Module improvements

= 1.0.7 =
* Successful revision completed with WordPress 6.8.2

= 1.0.6 =
* Successful revision completed with WordPress 6.4.2

= 1.0.5 =
* Sanitization added

= 1.0.4 =
* Code improvements

= 1.0.3 =
* Code improvements

= 1.0.2 =
* Code improvements

= 1.0.1 =
* Code improvements

= 1.0.0 =
* Initial release of the plugin.

== Upgrade Notice ==

= 1.1.0 =
* KYB added
* Module improvements

== Copyright ==

iDenfy is a registered trademark of UAB "Identifikaciniai Projektai" / UAB "iDenfy".
