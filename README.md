# Textpattern CMS

[![Crowdin](https://badges.crowdin.net/textpattern-cms-textpacks/localized.svg)](https://crowdin.com/project/textpattern-cms-textpacks)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/textpattern)](https://github.com/sponsors/textpattern)

<img src="https://textpattern.com/assets/img/branding/carver/carver.svg" alt="Textpattern Logo" width="128" height="128">

**A flexible, elegant, fast and easy-to-use content management system written in PHP.** Textpattern is [free and open source](#legal) software.

* [System requirements](#system-requirements)
* [Download Textpattern](#download-textpattern)
* [Install Textpattern](#install-textpattern)
* [Upgrade Textpattern](#upgrade-textpattern)
* [Help and Support](#help-and-support)
* [Development](#development)
* [Contributing](#contributing)
* [Thank you](#thank-you)
* [Legal](#legal)

![Textpattern CMS screenshots](https://textpattern.com/assets/img/com/readme-device-screens.png)

## System requirements

Textpattern is installed to a web server with PHP and MySQL.

Ensure the server meets or exceeds the [system requirements](https://textpattern.com/system-requirements) before you continue.

## Download Textpattern

The current production release is version 4.8.7. It can be downloaded from the Textpattern website or GitHub in .zip and .tar.gz varieties.

If you want to use the multi-site functionality in Textpattern, get the .tar.gz archive.

|        |  textpattern.com  | GitHub |
|--------|:-------:|:-----:|
| .zip   | [Download](https://textpattern.com/file_download/111/textpattern-4.8.7.zip) | [Download](https://github.com/textpattern/textpattern/releases/download/4.8.7/textpattern-4.8.7.zip) |
| .tar.gz | [Download](https://textpattern.com/file_download/110/textpattern-4.8.7.tar.gz) | [Download](https://github.com/textpattern/textpattern/releases/download/4.8.7/textpattern-4.8.7.tar.gz) |


## Install Textpattern

Please see [README.txt](https://github.com/textpattern/textpattern/blob/main/README.txt) for details on installing Textpattern.

## Upgrade Textpattern

Please see [README.txt](https://github.com/textpattern/textpattern/blob/main/README.txt) for details on upgrading Textpattern.

## Help and Support

The [Textpattern support forum](https://forum.textpattern.com) is home to a friendly and helpful community of Textpattern users and experts. Textpattern also has a social network presence on [Twitter](https://textpattern.com/@textpattern).

## Development

The development snapshot can be obtained from the [Textpattern repository on GitHub](https://github.com/textpattern/textpattern).

### Anticipated changes to future system requirements

As a development version approaches release, minimum and recommended system requirements are confirmed and the production release system requirements is updated accordingly.

The following table outlines anticipated forthcoming changes to system requirements. It takes into account vendor support, security considerations, overall performance and other factors. Note that minimum and/or recommended versions listed may change multiple times during the development process.

#### Textpattern development versions

Note that targeted versions listed may change multiple times during the development process.

We are targeting Textpattern 4.9 as the next minor release. Refer to the following table for anticipated changes to system requirements.

|        |  Minimum  | Recommended |
|--------|:-------:|:-----:|
| PHP    | 5.6 | [vendor supported](https://php.net/supported-versions.php)<br />(7.3, 7.4 or 8.0) |
| MySQL  | 5.5 | [vendor supported](https://www.mysql.com/support/supportedplatforms/database.html)<br />(typically 5.6, 5.7 or 8.0) |
| Apache | &mdash; | vendor supported<br />(2.4) |
| Nginx  | &mdash; | mainline (1.21) or stable (1.20) |

## Contributing

Do you want to help with the development of Textpattern? Please refer to the [contributing documentation](https://github.com/textpattern/textpattern/blob/dev/.github/CONTRIBUTING.md) for full details.

## GitHub topic tags

If you use GitHub for Textpattern-related development please consider adding some of the following [topic](https://help.github.com/articles/about-topics/) keywords to your public project repositories, so we can expand the network of discoverable resources:

* [`textpattern`](https://github.com/topics/textpattern)
* [`textpattern-plugin`](https://github.com/topics/textpattern-plugin)
* [`textpattern-theme`](https://github.com/topics/textpattern-theme)
* [`textpattern-website`](https://github.com/topics/textpattern-website) (for websites built with Textpattern)
* [`textpattern-development`](https://github.com/topics/textpattern-development) (for development resources)

## Additional development tools

Various components used within Textpattern (such as the bundled themes and language translations) are maintained in standalone repositories. Textpattern has a simple development toolset built on [Node.js](https://nodejs.org/) to pull the distribution files of those repositories into the core as required.

You can install Node.js using the [installer](https://nodejs.org/en/download/) or [package manager](https://nodejs.org/en/download/package-manager/).

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
npm run get-default-theme 4.8.7
npm run get-classic-admin-theme 4.8.7
npm run get-classic-admin-theme 4.8.x
npm run get-hive-admin-theme 4.8.x
npm run get-textpacks 4.8.x
```

You can verify PHP code via a PHP linter from the CLI, like so:

```ShellSession
npm run phplint
```

You can verify JavaScript code via a JavaScript linter from the CLI, like so:

```ShellSession
npm run eslint
```

Release tools:

Usage: `npm run txp-gitdist <version> [dest-dir]` (`dest-dir` defaults to a
temporary location).

```ShellSession
npm run txp-index
npm run txp-checksums
npm run txp-gitdist 1.2.3 ../my-dest-dir
```

## Thank You

Thank you to our [GitHub monthly sponsors](https://github.com/sponsors/textpattern). Your continued support is greatly appreciated!

We are grateful to [DigitalOcean](https://www.digitalocean.com/?utm_source=opensource&utm_campaign=textpattern), [BrowserStack](https://www.browserstack.com) and [1Password](https://1password.com) for their kind considerations in supporting Textpattern CMS development by way of web hosting infrastructure (DigitalOcean), cross-browser testing platform (BrowserStack) and secure password management (1Password). Thank you!

This project is supported by:

<a href="https://www.digitalocean.com/?utm_source=opensource&utm_campaign=textpattern"><img src="https://opensource.nyc3.cdn.digitaloceanspaces.com/attribution/assets/SVG/DO_Logo_horizontal_blue.svg" width="201px"></a>

## Legal

Released under the GNU General Public License. See [LICENSE.txt](https://github.com/textpattern/textpattern/blob/main/LICENSE.txt) for terms and conditions.

Includes contributions licensed under the GNU Lesser General Public License. See [LICENSE-LESSER.txt](https://github.com/textpattern/textpattern/blob/main/textpattern/lib/LICENSE-LESSER.txt) for terms and conditions.

Includes contributions licensed under the New BSD License. See [LICENSE-BSD-3.txt](https://github.com/textpattern/textpattern/blob/main/textpattern/lib/LICENSE-BSD-3.txt) for terms and conditions.

![Textpattern CMS blogging illustration](https://textpattern.com/assets/img/com/readme-footer.png)
