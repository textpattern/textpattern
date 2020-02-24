Textpattern CMS 4.8.0

Released under the GNU General Public License.
See LICENSE.txt for terms and conditions.

Includes contributions licensed under the GNU Lesser General Public License.
See textpattern/lib/LICENSE-LESSER.txt for terms and conditions.

Includes contributions licensed under the New BSD License.
See textpattern/lib/LICENSE-BSD-3.txt for terms and conditions.


== About ==

Textpattern CMS is a flexible, elegant and easy-to-use content management
system. Textpattern is free and open source.


== Getting Started ==

* The Textpattern FAQ is available at https://textpattern.com/faq/
* In-depth documentation and a comprehensive tag index is available in the
  Textpattern user documentation at https://docs.textpattern.com/
* You can get support and information via:
   Forum:    https://forum.textpattern.com/
   GitHub:   https://textpattern.com/github
   Twitter:  https://textpattern.com/@textpattern


== Installation ==

* Extract the Textpattern files to your site (in the web root, subdomain or
  subdirectory). The top-level index.php file should reside in this directory,
  as should the /textpattern/, /files/, /images/, /rpc/ and /themes/
  directories.
* If you are running Textpattern on an Apache web server, ensure the top-level
  .htaccess file is transferred to your site. The file is hidden by default on
  some operating systems, including macOS. FTP clients and IDEs typically have
  an option to show these hidden files. Otherwise, you can temporarily show
  hidden files in macOS using Terminal.app - follow these instructions:
    https://textpattern.com/hidden-files-osx
* Create or verify the existence of a working MySQL database with valid username
  and password. From Textpattern 4.6.0, the MySQL database user requires INDEX
  permissions in addition to pre-existing permissions. Refer to the system
  requirements for a complete list:
    https://textpattern.com/system-requirements
* Load /textpattern/setup/ (or /subdirectory/textpattern/setup/) in your
  browser to start the installation process and follow the directions.
* When the installation is complete, remove the /textpattern/setup/ directory
  from your site.
* If you are running Textpattern on an Apache web server, you can rename
  /files/.htaccess-dist to /files/.htaccess to prohibit direct URL access to
  your uploaded files. Thus the only route to these files becomes through the
  /file_download/ directory. It is recommended you consider employing this
  feature. Alternatively, move your /files/ directory out of a web-accessible
  location. Once moved, you can specify your new directory location from the
  Textpattern Admin Preferences (Admin -> Preferences -> Admin).


== IMPORTANT ==

* Check back regularly at https://textpattern.com/ to see if an update is
  available. Updates are as painless as possible, often extending functionality,
  fixing bugs and/or security-related issues. Textpattern's diagnostics
  (Admin -> Diagnostics) will display a message if a new version is available.


== Upgrading: preflight ==

* Read the release notes in HISTORY.txt, both for the version you are upgrading
  to and any prior versions you are skipping as these changes will apply.
  Ensure you understand any changes and enhancements that may affect your
  Textpattern site functionality. If in doubt, refer to the resources in the
  Getting Started section above before you undertake the upgrade process.
* Confirm your web server meets or exceeds the Textpattern system requirements:
    https://textpattern.com/system-requirements
* It is recommended that the browser cache is cleared to ensure the newer files
  are used in preference to the older files.
* NOTE: Versions prior to 4.2.0 should be upgraded to 4.2.0 before subsequent
  version upgrades. Upgrades from versions prior to 4.2.0 will present warnings
  and/or errors upon the first login to the admin-side. These may include:
    Unknown column 'user_name' in 'where clause' select name,
    val from txp_prefs where prefs_id=1 AND user_name='' in
    /path/to/your/site/textpattern/lib/txplib_db.php on line xx
    Undefined variable: language in
    /path/to/your/site/textpattern/index.php at line xx
    Undefined variable: gmtoffset in
    /path/to/your/site/textpattern/update/_to_4.2.0.php at line xx
  This is expected behaviour. The messages will disappear with subsequent
  navigation in the admin-side.
* NOTE: Upgrades from versions prior to 4.7.0 may present an 'Unknown section'
  message on the front side after updated files have been transferred and
  before the first admin-side log in. The message will disappear following the
  first log in when the database upgrade scripts are run.

== Upgrading ==

* Log out of the Textpattern admin-side.
* Verify the existence of a known-good Textpattern database and file backup.
* Ensure your Textpattern database user has INDEX permissions in addition to
  any permissions previously granted.
* Replace the top-level index.php, css.php and .htaccess files (if Textpattern
  is running on an Apache or derivative web server), everything in the /rpc/
  directory and everything in the /textpattern/ directory (except
  /textpattern/config.php and the /textpattern/setup/ directory) with the
  corresponding files in this distribution. In addition, ensure the /themes/
  directory exists if it is not already there, and is writable.
  When uploading the /textpattern/ directory, ensure you preserve any custom
  files in your existing installation.
  Note: css.php and /rpc/ may not exist if the installed version of
  Textpattern is prior to 4.2.0.
* Log in to the Textpattern admin-side. The upgrade script(s) will run
  automatically. Check the site diagnostics (Admin -> Diagnostics) to confirm
  the correct Textpattern version number is displayed and whether there are any
  errors.
* Verify all preference settings (Admin -> Preferences).


== Additional Resources ==

* There are additional resources for the default front-side theme, such as
  Sass preprocessor files, available at:
    https://github.com/textpattern/textpattern-default-theme/


== Contributing ==

Want to help out with the development of Textpattern CMS? Please refer to the
Contributing documentation https://docs.textpattern.com/development/contributing
for full details.
