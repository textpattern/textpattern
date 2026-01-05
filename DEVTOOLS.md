# Additional development tools

Various components used within Textpattern (such as the bundled themes and language translations) are maintained in other repositories. Textpattern has a simple development toolset built on [Node.js](https://nodejs.org/) to pull the distribution files of those repositories into the core as required.

You can install Node.js using the [installer](https://nodejs.org/en/download/) or [package manager](https://nodejs.org/en/download/package-manager/).

Install required dev tools:

```ShellSession
npm install
```

Pull the following components from the CLI:

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
npm run get-default-theme 4.9.0
npm run get-classic-admin-theme 4.9.0
npm run get-classic-admin-theme 4.9.x
npm run get-hive-admin-theme 4.9.x
npm run get-textpacks 4.9.x
```

Release tools:

Usage: `npm run txp-gitdist <version> [dest-dir]` (`dest-dir` defaults to a temporary location).

```ShellSession
npm run txp-index
npm run txp-checksums ./textpattern
npm run txp-gitdist 1.2.3 ../my-dest-dir
```
