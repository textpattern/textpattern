# Textpattern CMS

[![Dependencies Status](https://david-dm.org/textpattern/textpattern/status.svg)](https://david-dm.org/textpattern/textpattern)
[![devDependencies Status](https://david-dm.org/textpattern/textpattern/dev-status.svg)](https://david-dm.org/textpattern/textpattern?type=dev)

[![Textpattern Logo](https://textpattern.com/assets/img/branding/carver/carver-128px.svg)](https://textpattern.com/)

**A flexible, elegant and easy-to-use content management system.**

Textpattern is [free and open source](#legal) software.

* [System requirements](#system-requirements)
* [Download Textpattern](#download-textpattern)
* [Install Textpattern](#install-textpattern)
* [Upgrade Textpattern](#upgrade-textpattern)
* [Help and Support](#help-and-support)
* [Development](#development)
* [Contributing](#contributing)
* [Legal](#legal)

## System requirements

Textpattern is installed to a web server with PHP and MySQL.

Ensure the server meets or exceeds the
[system requirements](https://textpattern.com/about/119/system-requirements)
before you continue.

## Download Textpattern

The current production release is version 4.7.1. It can be downloaded from the
Textpattern website as a
[.zip](https://textpattern.com/file_download/86/textpattern-4.7.1.zip) or
[.tar.gz](https://textpattern.com/file_download/87/textpattern-4.7.1.tar.gz) archive.

## Install Textpattern

Please see
[README.txt](https://github.com/textpattern/textpattern/blob/master/README.txt)
for details on installing Textpattern.

## Upgrade Textpattern

Please see
[README.txt](https://github.com/textpattern/textpattern/blob/master/README.txt)
for details on upgrading Textpattern.

## Help and Support

The [Textpattern support forum](https://forum.textpattern.com) is home to
a friendly and helpful community of Textpattern users and experts.
Textpattern also has a social network presence on
[Twitter](https://textpattern.com/@textpattern).

## Development

The development version can be
obtained from the [Textpattern repository on GitHub](https://github.com/textpattern/textpattern). Note that development
versions are works-in-progress and not recommended for use on live production
servers.

### Anticipated changes to future system requirements

System requirements for the development version may differ from the production
release [system requirements](https://textpattern.com/about/119/system-requirements).

As a development version approaches release, minimum and recommended system
requirements are confirmed and the production release [system requirements](https://textpattern.com/about/119/system-requirements) will
be updated accordingly.

The following table outlines anticipated forthcoming changes to system
requirements for future releases. It takes into account vendor support, security
considerations and other factors.

Note that minimum and/or recommended versions listed may change multiple times
during the development process.

|        |  Minimum<br />(v4.8.0)  | Recommended<br />(v4.8.0) |
|--------|:-------:|:-----:|
| PHP    | 5.5 | 7.2 |
| MySQL  | &mdash; | &mdash; |
| Apache | &mdash; | &mdash; |
| Nginx  | 1.10 | mainline (1.15) or stable (1.14) |

## Contributing

Want to help out with the development of Textpattern CMS? Please refer to the
[Contributing documentation](https://github.com/textpattern/textpattern/blob/dev/.github/CONTRIBUTING.md)
for full details.

## GitHub topic tags

If you use GitHub for Textpattern-related development please consider adding
some of the following [topic](https://help.github.com/articles/about-topics/)
keywords to your public project repositories, so we can build a network of
discoverable resources:

* [`textpattern`](https://github.com/topics/textpattern)
* [`textpattern-plugin`](https://github.com/topics/textpattern-plugin)
* [`textpattern-theme`](https://github.com/topics/textpattern-theme)
* [`textpattern-website`](https://github.com/topics/textpattern-website) (for websites built with Textpattern)
* [`textpattern-development`](https://github.com/topics/textpattern-development) (for development resources)

## Additional development tools

Various components used within Textpattern CMS (such as the bundled themes and
language translations) are maintained in standalone repositories. We have a
simple development toolset built on [Node.js](https://nodejs.org/) to pull the
distribution files of those repositories into the core as required.

You can install Node.js using the [installer](https://nodejs.org/en/download/)
or [package manager](https://nodejs.org/en/download/package-manager/).

Install required dev tools:

```ShellSession
npm install
```

You can then pull the following components from the CLI, like so:

```ShellSession
npm run get-default-theme
npm run get-classic-admin-theme
npm run get-hive-admin-theme
npm run get-pophelp
npm run get-textpacks
npm run get-dependencies
```

To request a specific tag or branch:

```ShellSession
npm run get-default-theme 4.7.1
npm run get-classic-admin-theme 4.7.1
npm run get-classic-admin-theme 4.7.x
npm run get-hive-admin-theme 4.7.x
npm run get-textpacks 4.7.x
```

You can verify PHP code via a PHP linter from the CLI, like so:

```ShellSession
npm run phplint
```

Release tools:

Usage: `npm run txp-gitdist <version> [dest-dir]` (`dest-dir` defaults to a
temporary location).

```ShellSession
npm run txp-index
npm run txp-checksums
npm run txp-gitdist 1.2.3 ../my-dest-dir
```

## Legal

Released under the GNU General Public License. See
[LICENSE.txt](https://github.com/textpattern/textpattern/blob/master/LICENSE.txt)
for terms and conditions.

Includes contributions licensed under the GNU Lesser General Public License. See
[LICENSE-LESSER.txt](https://github.com/textpattern/textpattern/blob/dev/textpattern/lib/LICENSE-LESSER.txt)
for terms and conditions.

Includes contributions licensed under the New BSD License. See
[LICENSE-BSD-3.txt](https://github.com/textpattern/textpattern/blob/dev/textpattern/lib/LICENSE-BSD-3.txt)
for terms and conditions.
