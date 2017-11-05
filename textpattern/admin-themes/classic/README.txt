Classic admin-side theme for Textpattern CMS

You are free to modify this theme, reuse code herein, and distribute all its
supporting files as long as licences are respected (see below).


== Installation instructions ==

# Upload the directory 'classic' into your Textpattern installation ->
  admin-themes directory.
# Log in to your Textpattern admin area. Navigate to admin -> preferences ->
  admin.
# Select 'Classic' from the 'Admin-side theme' dropdown list. Save the
  preferences.


== Optional custom files for advanced users ==

We provide an optional user-defined custom CSS and JavaScript file for
customization of the admin-side theme. By default, these are located at:

    assets -> css -> custom.css
    assets -> js -> custom.js

You may use different file names by adding the following line(s) within your
Textpattern 'config.php' file (before the closing '?>' line):

    define('admin_custom_css', 'your_custom_rules.css');
    define('admin_custom_js', 'your_custom_javascript.js');

You can then use those files to customize the admin-side theme to your
requirement.

Note: be careful not to overwrite your own custom rules when updating to a new
version of Textpattern!
