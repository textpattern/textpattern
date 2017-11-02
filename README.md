# Textpattern CMS

[![Textpattern Logo](https://textpattern.io/assets/img/branding/carver/carver-128px.svg)](https://textpattern.com/)

**A flexible, elegant and easy-to-use content management system.**

Textpattern is [free and open source](#legal) software.

* [System requirements](#system-requirements)
* [Download Textpattern](#download-textpattern)
* [Install Textpattern](#install-textpattern)
* [Upgrade Textpattern](#upgrade-textpattern)
* [Help and Support](#help-and-support)
* [Contributing](#contributing)
* [Legal](#legal)

## System requirements

Textpattern is installed to a web server with PHP and MySQL.

Ensure the server meets or exceeds the
[system requirements](https://textpattern.com/about/119/system-requirements)
before you continue.

## Download Textpattern

### Production release

The current production release is version 4.6.2. It can be downloaded from the
Textpattern website as a
[.zip](https://textpattern.com/latest.zip) or
[.tar.gz](https://textpattern.com/latest.tar.gz) archive.

### Development version

The development version can be obtained from the [Textpattern repository on
GitHub](https://github.com/textpattern/textpattern). Note that development
versions are works-in-progress and not recommended for use on live production
servers.

## Install Textpattern

Please see
[README.txt](https://github.com/textpattern/textpattern/blob/master/README.txt)
for details on installing Textpattern.

## Upgrade Textpattern

Please see
[README.txt](https://github.com/textpattern/textpattern/blob/master/README.txt)
for details on upgrading Textpattern.

## Help and support

The [Textpattern support forum](https://forum.textpattern.io) is home to
a friendly and helpful community of Textpattern users and experts.
Textpattern also has social network presences on
[Google+](https://textpattern.com/+) and [Twitter](https://textpattern.com/@textpattern).

## Contributing

Want to help out with the development of Textpattern CMS? Please refer to the
[Contributing documentation](https://github.com/textpattern/textpattern/blob/dev/.github/CONTRIBUTING.md)
for full details.

## GitHub topic tags

If you use GitHub for Textpattern-related development please consider adding
some of the following [topic](https://help.github.com/articles/about-topics/)
keywords to your public project repositories, so we can build a network of
discoverable resources:

* `textpattern`
* `textpattern-plugin`
* `textpattern-theme`
* `textpattern-website` (for websites built with Textpattern)
* `textpattern-development` (for development resources)

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
npm run get-textpacks
npm run get-dependencies
```

To request a specific branch or tag:

```ShellSession
npm run get-default-theme 4.7.0
npm run get-classic-admin-theme 4.6.1
npm run get-classic-admin-theme 4.6.x
npm run get-hive-admin-theme 4.6.x
npm run get-textpacks 4.6.x
```

You can verify PHP code via a PHP linter from the CLI, like so:

```ShellSession
npm run phplint
```

Release tools:

```ShellSession
npm run get-checksums
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
