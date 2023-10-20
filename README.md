# MFA Protect

This extension lets you force the use of a recent MFA token before rendering a
content element.

This may be used together with standard access restrictions in TYPO3 such as
being part of one or more Frontend user groups.

This screenshot shows how a given content element will be protected:

![Protected content][protected-content]

[protected-content]: https://raw.githubusercontent.com/xperseguers/t3ext-mfa-protect/master/Documentation/Images/protected-content.png "Protected content"


## Installation

Install with Composer (there are currently no releases):

```bash
composer require causal/mfa-protect
```

In addition, you will need some extension adding support for Frontend MFA. At the
time of writing, the only one the author can think of is its own extension
[EXT:mfa_frontend](https://extensions.typo3.org/extension/mfa_frontend).

You may install it with composer:

```bash
composer require causal/mfa-frontend
```


## Configuration

Include the static template "Protect MFA" to your (main) TypoScript template.

You can then use the Constants Editor or pure TypoScript to override the template
location and the validity of the MFA token (it defaults to 30 minutes).

**Hint:** the validity of the MFA token supports
[stdWrap](https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Functions/Stdwrap.html) :-)


## Usage

Edit any content element, switch to Access and toggle the MFA protect flag. That's it!

![Access flag][access-flag]

[access-flag]: https://raw.githubusercontent.com/xperseguers/t3ext-mfa-protect/main/Documentation/Images/access-flag.png "Access Flag"
