Textpattern CMS 4.6-dev

Released under the GNU General Public License.
See LICENSE.txt for terms and conditions.

Includes contributions licensed under the GNU Lesser General Public License.
See LICENSE-LESSER.txt for terms and conditions.

Includes contributions licensed under the New BSD License.
See LICENSE-BSD-3.txt for terms and conditions.


== About ==

Textpattern CMS is a flexible, elegant and easy-to-use content management
system. Textpattern is both free and open source.


== Installation ==

* Extract the Textpattern files to your site (in the web root, or choose a
  subdirectory). The top-level index.php file should reside in this directory,
  as should the /textpattern/ and /rpc/ directories.
* Ensure the root .htaccess file is transferred across correctly to your install
  destination. The file is hidden by default on some operating systems,
  including OS X. Most FTP clients and IDEs have an option to show these hidden
  files. Otherwise, you can temporarily show hidden files in OS X by using the
  Terminal.app - follow these instructions:
  http://textpattern.com/hidden-files-osx
* Create or establish the existence of a working MySQL database along with valid
  username and password credentials, then load /textpattern/setup/
  (or /subdirectory/textpattern/setup/) in your browser to start the
  installation process and follow the directions.
* When the installation is complete, remove the /textpattern/setup/ directory
  from your site.


== Upgrading ==

* Log out of the admin-side.
* Verify the existence of a working Textpattern database and file backup.
* Replace the three files in your Textpattern installation directory (index.php,
  css.php and .htaccess), everything in your /js/ directory, everything in
  your /rpc/ directory and everything in your /textpattern/ directory
  (except /textpattern/config.php) with the corresponding files in this
  distribution. Note that css.php and /rpc/ may not exist in your current site
  if you are upgrading from Textpattern prior to version 4.2.0.
* It is recommended that you flush the cache of your browser, to ensure
  old cached files are not being used in preference to any newer versions within
  the upgrade.
* When you log in to the admin-side, the upgrade script(s) run automatically.
  Please check the diagnostics (Admin -> Diagnostics) to confirm the correct
  version number is displayed and whether there are any errors.
  NOTE: Upgrades from versions prior to 4.2.0 will present this warning
  upon your very first login to the admin-side:
    Warning: Unknown column 'user_name' in 'where clause' select name,
    val from txp_prefs where prefs_id=1 AND user_name='' in
    /path/to/your/site/textpattern/lib/txplib_db.php on line xx
  This is expected behaviour for the very first login after an upgrade.
  The warning will disappear with subsequent navigation in the admin-side.
* Verify all preference settings.
* When the upgrade is complete, remove the /textpattern/setup/ directory from
  your site.


== Getting Started ==

* The Textpattern FAQ is available at http://textpattern.com/faq/
* In-depth documentation and a comprehensive tag index is available in the
  Textpattern documentation at http://textpattern.net/
* You can get support and information via:
   Forum:    http://forum.textpattern.com/
   Twitter:  http://textpattern.com/@textpattern
   Google+:  http://textpattern.com/+
   Facebook: http://textpattern.com/facebook
* If you are running an Apache web server, rename the .htaccess-dist file
  in the /files/ directory to .htaccess to prohibit direct URL access to
  your files. Thus the only route to these files becomes through the
  /file_download/ directory. It is recommended you consider employing this
  feature or that you move your /files/ directory out of a web-accessible
  location. Once moved, you can tell Textpattern of your new directory location
  from the Advanced Preferences.
* There are additional resources for the default front-side theme, such as
  Sass preprocessor files, available at:
    http://textpattern.com/default-theme/tree/master/


== IMPORTANT ==

* Check back regularly at http://textpattern.com to see if updates are
  available. Updates are as painless as possible, often fixing important bugs
  and/or security-related issues.
