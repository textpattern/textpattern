=================================
= Textpattern multi-site how-to =
=                               =
= Author: Sam Weiss (artagesw)  =
=================================


=========================
= Contents of directory =
=========================

This directory (sites) is optional. If you do not wish to host multiple
Textpattern sites from a single Textpattern installation, you may remove this
directory with no ill effects.

This directory may be used to create multiple Textpattern-based sites from a
single installation of Textpattern. With a multi-site setup, you may host
multiple sites while having only a single copy of the Textpattern core code to
maintain and update.

In a fresh installation, the sites directory contains a single subdirectory
named 'site1'. This is a template of a default Textpattern multi-site. You can
use the site1 directory directly and rename it to whatever you like. If you 
plan to add further sites to your multi-site installation at a later date, you
can also retain it as a blank template for future sites.

To set up a new site in your multi-site installation, simply duplicate the 
entire site1 directory to a new directory (within the sites directory) with 
a name appropriate for your new site.
In the terminal, for example, change to the sites directory and enter:

    cp -R site1 your-site-name

IMPORTANT! Be sure to designate the "-R" option to cp so that symbolic links
are maintained.

Repeat this for each site in your multi-site installation.

============
= Overview =
============

Textpattern 4.2 introduced multi-site capabilities. You may take advantage of
these capabilities to:

* Create multiple Textpattern-driven web sites from a single installation of
  the Textpattern core code base.

* Separate the Textpattern admin area from the Textpattern-driven web site by
  placing it into its own web root and subdomain.

* Gain a modicum of extra security by removing the vast majority of the
  Textpattern core code from the web document root.

* Easily protect the admin area via SSL, if you place it into its own web root
  and subdomain (see option A below).

For example, you might have the following Textpattern-driven sites, all running
off a single shared installation of the Textpattern code:

    http://www.example.com
    http://admin.example.com

    http://www.example.net
    https://admin.example.net

    http://www.example.org
    https://admin.example.org


========================================
= How to set up Textpattern multi-site =
========================================

The following sections describe how to set up multiple Textpattern sites from a
single installation. There are two slightly different sets of instructions,
depending upon whether you prefer to:

A. Create separate subdomains for your Textpattern admin areas (recommended), or

B. Allow access to the admin area through a subdirectory of your site (the
   traditional setup).


==========================================
= A. Multi-site with separate admin area =
==========================================

This setup method results in separate subdomains for your Textpattern-powered
site and its associated admin area. For example:

    http://www.example.com     <-- URL to your site

    http://admin.example.com   <-- URL to your site's admin area login
OR:
    https://admin.example.com  <-- URL to your site's secure admin area login

----------------------------
Step 1: Configure web server
----------------------------

In order to separate the admin area into its own subdomain, you will need to use
your web host's facilities to create two virtual hosts per Textpattern site: one
virtual host will be used to access the Textpattern admin area, and the other
will be used to access the Textpattern-powered site. You need to:

* Set the document root of the admin virtual host (e.g. admin.example.com)
  to the /admin subdirectory of your site. 

* Set the document root of the public site virtual host (e.g. www.example.com)
  to the /public subdirectory of your site. 

This configuration step is commonly performed via your host's control panel.

If your host allows you to manage your server config files directly, here is an 
example config for Apache to create virtual hosts for site1:

    <VirtualHost *:80>
        ServerName example.com
        ServerAlias www.example.com
        DocumentRoot "/path/to/multi-site-basedir/sites/site1/public"
        <Directory "/path/to/multi-site-basedir/sites/">
            Options +FollowSymLinks
        </Directory>
    </VirtualHost>

    <VirtualHost *:80>
        ServerName admin.example.com
        DocumentRoot "/path/to/multi-site-basedir/sites/site1/admin"
        <Directory "/path/to/multi-site-basedir/sites/">
            Options +FollowSymLinks
        </Directory>
    </VirtualHost>

where '/path/to' is your web server's path to your user/account directory and 
'multi-site-basedir' is the name you have chosen to install Textpattern in. 
This directory should not be accessible from the web.

Note: if setting up a secured admin area on SSL, replace *:80 with *:443 (SSL) 
in the second example and include the necessary certificates as per your host's
instructions.

Also, be sure that the virtual host allows traversing symbolic links.

You may need to restart your web server before it will recognize new virtual
hosts. Create a pair of virtual host configurations for each Textpattern site
you will be hosting.

-------------------------
Step 2: Textpattern setup
-------------------------

You should now be able to proceed through Textpattern's setup process.

* If you set up your admin area on SSL, go to:

    https://admin.example.com/setup

  Otherwise, go to:

    http://admin.example.com/setup

* On the MySQL setup page, be sure to enter the correct URL to your Textpattern
  site (not the admin site) under Site URL. Textpattern will default to the URL
  of your admin site and you will need to change that here. For example:

    www.example.com

* Textpattern will tell you to place your config.php file in /textpattern/.
  This is INCORRECT. Place it in the private subdirectory of your site.
  For example:

    /sites/site1/private/config.php

* IMPORTANT! When you create your config.php file, be sure to add the following
  line just before the closing '?>' tag:

    define('txpath', $txpcfg['txpath']);

* After completing the installation routine, log in to the admin area at:

    http://admin.example.com/
  
  Visit the Admin > Preferences panel and under the “Admin” preferences, 
  correct the “File directory path” to match the /files folder in your new 
  site’s public directory. For example:
  
    /path/to/multi-site-basedir/sites/site1/public/files


-------------------------------
Step 3: Secure the installation
-------------------------------

For security reasons, you should remove the 'Setup' directory once the setup has
been completed. Remove both of the following:

    sites/site1/admin/setup
    sites/site1/public/setup

--

The site1 template includes a symbolic link to the admin directory inside the
public directory. This is only used for the alternate setup described below
("Multi-site with integrated admin area"). Since we will be accessing the admin
area from a separate subdomain, this symbolic link is not necessary and should
be removed.

Remove the following:

    sites/site1/public/admin

--

Once setup is complete, access to the theme directory is no longer needed from
the public side.

Remove the following:

    sites/site1/public/theme

--

You may also want to remove the following files and directories from the
top-level Textpattern directory, as they are not needed when running multi-site:

    files
    images
    index.php

See also the "Additional notes" below.

---------------------------
Step 4: There is no step 4!
---------------------------

But if you want to to set up additional sites, simply repeat steps 1 through 3
for each site. Your sites directory layout will look like the following:

    multi-site-basedir/
        sites/
            example.com/
                admin/
                private/
                public/
            example.net/
                admin/
                private/
                public/
            example.org/
                admin/
                private/
                public/
        textpattern/


============================================
= B. Multi-site with integrated admin area =
============================================

This setup method results in the more traditional Textpattern installation,
where the admin area is located in a subdirectory of the main site's domain.
For example:

   http://www.example.com        <-- URL to your site
   http://www.example.com/admin  <-- URL to your site's admin area login

----------------------------
Step 1: Configure web server
----------------------------

You will need to use your web host's facilities to create a single virtual host
for each of your Textpattern sites. This configuration step is commonly
performed via your host's control panel.

Set the document root of the site virtual host to the public subdirectory of
your site. Also, be sure that the virtual host allows traversing symbolic links.

If your host allows you to manage your server config files directly, here is an 
example config for Apache to create our virtual host for site1:

    <VirtualHost *:80>
        ServerName example.com
        ServerAlias www.example.com
        DocumentRoot "/path/to/multi-site-basedir/sites/site1/public"
        <Directory "/path/to/multi-site-basedir/sites/">
            Options +FollowSymLinks
        </Directory>
    </VirtualHost>

You may need to restart your web server before it will recognize new virtual
hosts. Create a single virtual host configuration for each Textpattern site you
will be hosting.

-------------------------
Step 2: Textpattern setup
-------------------------

You should now be able to proceed through Textpattern's setup process.

* Go to: http://example.com and click the textpattern/setup/ link.

* Follow the instructions. On the MySQL setup page, Textpattern will tell you
  to place your config.php file in /textpattern/. This is INCORRECT. Place it 
  in the private subdirectory of your site.
  For example:

    /sites/site1/private/config.php

* IMPORTANT! When you create your config.php file, be sure to add the following
  line just before the closing '?>' tag:

    define('txpath', $txpcfg['txpath']);

* Once Textpattern creates your site's database tables, it will present a link
  to the "main interface." This link will be INCORRECT, so do not click it.
  Instead, manually enter the address of your admin area into your browser's
  address bar at this point:

    http://example.com/admin

* Visit the Admin > Preferences panel and under the “Admin” preferences, 
  correct the “File directory path” to match the /files folder in your new 
  site’s public directory. For example:
  
    /path/to/multi-site-basedir/sites/site1/public/files

-------------------------------
Step 3: Secure the installation
-------------------------------

For security reasons, you should remove the 'Setup' directory once setup has
completed.

Remove both of the following:

    sites/site1/admin/setup
    sites/site1/public/setup

--

Once setup is complete, access to the theme directory is no longer needed from
the public side.

Remove the following:

    sites/site1/public/theme

--

You may also want to remove the following files and directories from the
top-level Textpattern directory, as they are not needed when running multi-site:

    files
    images
    index.php

See also the "Additional notes" below.

---------------------------
Step 4: There is no step 4!
---------------------------

If you want to to set up additional sites, simply repeat steps 1 through 3
for each site. Your sites directory layout will look like the following:

    multi-site-basedir/
      sites/
          example.com/
              admin/
              private/
              public/
          example.net/
              admin/
              private/
              public/
          example.org/
              admin/
              private/
              public/
      textpattern/


====================
= Additional notes =
====================

Adjustments, known problems and possible workarounds:

* Errors on the Admin > Diagnostics panel:

  * /path/to/multi-site-basedir/textpattern/setup/ still exists
  * Site URL preference might be incorrect: admin.example.com

  These two errors can be ignored.
  
  If you also deleted index.php, and the /files and /images folders in the 
  base directory of your multi-site installation  you may additionally see:
  
  * Missing files: /../index.php
  
  This too can be ignored. If you also see:
   
  * File directory path is not writable: /path/to/multi-site-basedir/files
  
  you have forgotten to correct the file directory path to match your new 
  site's /file directory (see above) and/or not given it write permissions.

* The default article that is displayed after a clean installation will have 
  some broken links. This is to be expected, as the links are hard-coded to 
  the traditional /textpattern/ back-end.
  
* When using a unique admin domain, new user registration /activation emails 
  will contain the wrong login url for the admin area.
  
* Some textpattern plugins may need manual adjustments to work with the 
  multi-site setup.

Please check the Textpattern forum – forum.textpattern.com – for further 
details and tips for multi-site configurations.
