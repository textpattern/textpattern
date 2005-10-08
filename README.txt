Textpattern 4.0 - 14 Aug 2005

Released under the Gnu General Public License

Installation

* Extract the files to your site (in the web root, or choose a 
  subdirectory). The top-level index.php should reside in this 
  directory, as should the /textpattern/ directory.
* Create, or establish the existence of, a working mysql database,
  load /textpattern/setup/ (or /subpath/textpattern/setup/ )
  in a browser, and follow the directions.

Upgrading

* Simply replace the two files in your main installation directory 
  (index.php and .htaccess) and everything in your /textpattern/ 
  directory (except config.php) with the corresponding files in this
  distribution.
* When you login to the admin-side, the relevant upgrade script is 
  run automatically. Please take a look into diagnostics to find out 
  whether there are any errors and whether the correct version number 
  is displayed.
