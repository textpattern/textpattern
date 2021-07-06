# Contributing to Textpattern

If you want to help with the development of Textpattern, there are plenty of ways to get involved. Please take a moment to review this document in order to make the contribution process easy and effective for everyone.

## Who can contribute?

Anyone can contribute. You do not have to ask for permission.

## How can I contribute?

### Help with translations

To make corrections to existing translations, or to add new ones, [please follow these instructions](https://github.com/textpattern/textpacks/blob/main/README.md).

### Write documentation

Want to get involved in the Textpattern CMS user documentation project? Spot any errors? Want to add more documents or fix others? Then [please follow these instructions](https://github.com/textpattern/textpattern.github.io/blob/master/README.md).

### Contribute code

Core developers and project maintainers accept Pull Requests. The [main code repository](https://github.com/textpattern/textpattern) uses [Git](https://www.sitepoint.com/git-for-beginners/) for its version control and is split into at least three branches:

* `main`: for stable releases only. Core devs only ever merge production-ready code here at release time.
* `x.y.z`: for patching the most recent stable release.
* `dev`: for development of the next major version.

There may be other branches with partially-completed features awaiting merge, but the above are always present. Once you have cloned/forked the repository, ensure you have checked out the correct branch before submitting a Pull Request.

The general steps for Pull Requests:

* Switch to the correct branch (`git checkout branch-name`), where `branch-name` is either `x.y.z` to patch or bug fix the existing stable product, or `dev` for a feature/fix to go in the next major version.
* Pick an [existing issue](https://github.com/textpattern/textpattern/issues) you intend to work on, or [create a new issue](https://github.com/textpattern/textpattern/issues/new) if no existing issue matches your topic.
* Make a new branch for your work.
* Hack along.
* Push your changes to your fork on GitHub.
* Visit your repository's page on GitHub and click the 'Pull Request' button.
* Label the pull request with a clear title and description.

### Make it testable

This is the most important part. It makes the development team's job easier if the code is deemed supportable and maintainable - after all, we're the ones who will receive the bug reports and cries for help. The more you can do to help test your code, the better: examples of input and expected output, a test plan, notes on what you have and haven't tested.

If you have a big patch, consider splitting it into smaller, related chunks. Git branches are ideal for this as you can commit to each branch and hop between them, then submit each as a separate pull request. Also, please ensure your patch has the latest branch from our repo merged into it immediately prior to submission. If you have written the patch against the `dev` branch, for example, do `git merge dev` when on your branch to pull forward any recent changes to dev from other developers, then prepare your pull request. This step makes it easier for us to pull the patch down and test it in our development environments.

Scripted unit tests are becoming increasingly important in the Textpattern release process. You can make your code more testable by using a [functional design](https://en.wikipedia.org/wiki/Functional_design) with minimal coupling. A function that can be run in isolation, and returns a value based on its arguments, is easy to test. A function that prints output based on global variables, database records and configuration values is much harder to test (conveniently, Textpattern tag handler functions are usually easy to test).

### Coding standard

The project follows the [PSR-4](https://www.php-fig.org/psr/psr-4/) and [PSR-12](https://www.php-fig.org/psr/psr-12/) standards with PHP 5.3 style namespacing. You can use [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) to make sure your additions follow them, too:

~~~ ShellSession
$ ./vendor/bin/phpcs --standard=phpcs.xml *.php textpattern
~~~

### Versioning

The project follows [Semantic Versioning](https://semver.org/) and the `major.minor.patch` format.

## Increasing the likelihood of code being accepted

We accept most, but not all code that is submitted for inclusion in the Textpattern core. Sometimes we'll accept part of a patch or pull request, or include a modified or abridged version.

Textpattern is open source, so you don't need our permission to make your own modifications or extensions. However, if you want to maximize the chances it will be accepted and included in the official distribution, here is a quick guide to the Textpattern development philosophy.

### Do the simplest thing that could possibly work

Is there a shorter or easier way to achieve the same result? Then do it that way. Less code often means fewer bugs and is easier to maintain.

Don't reinvent the wheel. Is there already a function in PHP or Textpattern that makes your job easier? Use it.

### Minimize assumptions

Don't try to solve a problem unless you've tested it. This is particularly important for performance enhancements: measure the speed before and after - is the improvement really significant? If not, the simplest solution might be to leave it alone.

Similarly, don't write a bunch of functions or tag attributes on the assumption that they might be useful in the future. Unless you have a use case, leave it out.

Sure, we break our own rules sometimes. But, as a rule, we err on the side of simplicity.

## License

[GNU General Public License, version 2](https://github.com/textpattern/textpattern/blob/main/LICENSE.txt) (also known as GPLv2). By contributing to the project, you agree to license your contributions under the GPLv2 license.

## Code of conduct

Please see [Contributor covenant code of conduct](https://github.com/textpattern/textpattern/blob/dev/.github/CODE_OF_CONDUCT.md).
