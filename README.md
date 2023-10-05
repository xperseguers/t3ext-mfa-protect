# MFA Protect

This extension lets you force the use of a recent MFA before rendering a content
element.

This may be used together with standard access restrictions in TYPO3 such as
being part of one or more Frontend user groups.


## Installation

Install with Composer (there are currently no releases):

```bash
composer require causal/mfa-protect:dev-main
```

In addition, you will need some extension adding support for Frontend MFA. At the
time of writing, the only one the author can think of is its own fork of
[EXT:cf_google_authenticator](https://extensions.typo3.org/extension/cf_google_authenticator).

You may install it by adding/extending this to your site's `composer.json`:

```json
"repositories": [
    {"type": "git", "url": "https://github.com/xperseguers/cf_google_authenticator.git"}
],
```

and then:

```bash
composer require codefareith/cf-google-authenticator:dev-feature/TYPO3v12
```

_(don't worry about reading "TYPO3v12" as it's actually compatible with both
TYPO3 v11 and v12)_


## Configuration

Include the static template "Protect MFA" to your (main) TypoScript template.

You can then use the Constants Editor or pure TypoScript to override the template
location and the validity of the MFA token (it defaults to 30 minutes).

**Hint:** the validity of the MFA token supports
[stdWrap](https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Functions/Stdwrap.html) :-)


## Usage

Edit any content element, switch to Access and toggle the MFA protect flag. That's it!
