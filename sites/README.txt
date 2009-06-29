================================
= Textpattern Multi-Site Howto =
=                              =
= Author: Sam Weiss (artagesw) =
================================

=========================
= Contents of Directory =
=========================

This directory (sites) is optional. If you do not wish to host multiple Textpattern sites
from a single Textpattern installation, you may remove this directory with no ill effects.

This directory may be used to create multiple Textpattern-based sites from a single installation
of Textpattern. With a multi-site setup, you may host multiple Textpattern sites while having only
a single copy of the Textpattern core code to maintain and update.

In a fresh installation, the sites directory will contain a single subdirectory
named site1. You may rename the site1 directory to whatever you like (eg. mysite.mydomain.com).

You may treat the site1 directory as a template for creating new Textpattern sites. Simply duplicate
the entire site1 directory to a new directory (within the sites directory) with a name appropriate for
your new site. For example:

   cp -R site1 site2

IMPORTANT! Be sure to designate the "-R" option to cp so that symbolic links are maintained.

============
= Overview =
============

Textpattern 4.2 introduces new multi-site capabilities. You may take advantage of these capabilities
to:

   1. Create multiple Textpattern-driven web sites from a single installation
      of the Textpattern core code base.
   
   2. Separate the Textpattern admin area from the Textpattern-driven
      web site by placing it into its own web root and subdomain.
   
   3. Gain a modicum of extra security by removing the vast majority of the
      Textpattern core code from the web document root.

   4. Easily protect the admin area via SSL, if you place it into its own web
      root and subdomain.

For example, you might have the following Textpattern-driven sites, all running off a single shared
installation of the Textpattern code:

   http://www.mysite1.com
   http://admin.mysite1.com
   
   http://www.mysite2.com
   https://admin.mysite2.com
   
   http://www.mysite3.com
   https://admin.mysite3.com


========================================
= How to Set up Textpattern Multi-Site =
========================================

The following sections describe how to set up multiple Textpattern sites from a single installation.
There are two slightly different sets of instructions, depending upon whether you prefer to:

	A. Create separate subdomains for your Textpattern admin areas (recommended),
	
	or
	
	B. Allow access to the admin area through a subdirectory of your site (the traditional setup).


==========================================
= A. Multi-Site with Separate Admin Area =
==========================================


This setup method results in separate subdomains for your Textpattern-powered site and its associated
admin area. For example:

   http://www.mysite.com         <-- URL to your site

   http://admin.mysite.com       <-- URL to your site's admin area login
      or
   https://admin.mysite.com      <-- URL to your site's secure admin area login

----------------------------
Step 1: Configure Web Server
----------------------------

In order to separate the admin area into its own subdomain, you will need to use your web host's
facilities to create two virtual hosts per Textpattern site. One virtual host will be used to access
the Textpattern admin area, and the other will be used to access the Textpattern-powered site.
This configuration step is commonly performed via your host's web panel.

You need to set the document root of the admin virtual host to the admin subdirectory of your site.
Set the document root of the site virtual host to the public subdirectory of your site.
Also, be sure that the virtual host allows traversing symbolic links.

Here is an example config for Apache to create our virtual hosts for site1:

      <VirtualHost *:80>
         ServerName mysite.com
         ServerAlias www.mysite.com
         DocumentRoot "/path/to/textpattern/sites/site1/public"
         <Directory "/path/to/textpattern/sites/">
            Options +FollowSymLinks
         </Directory>
      </VirtualHost>
      
      <VirtualHost *:80>
         ServerName admin.mysite.com
         DocumentRoot "/path/to/textpattern/sites/site1/admin"
         <Directory "/path/to/textpattern/sites/">
            Options +FollowSymLinks
         </Directory>
      </VirtualHost>

You may need to restart your web server before it will recognize new virtual hosts.

Create a pair of virtual host configurations for each Textpattern site you will be hosting.

-------------------------
Step 2: Textpattern Setup
-------------------------

You should now be able to proceed through Textpattern's setup process.

   * If you set up your admin area on SSL, go to:
         https://admin.mysite.com/setup
     Otherwise, go to:
         http://admin.mysite.com/setup

   * On MySQL setup page, be sure to enter the correct URL to your Textpattern site (not the admin site) under Site URL.
     Textpattern will default to the URL of your admin site and you will need to change that here.
     
		Example: www.mysite.com

   * Textpattern will tell you to place your config.php file in /textpattern/. This is INCORRECT. Place it in the private
     subdirectory of your site. For example, /sites/site1/private/config.php.
     
   * IMPORTANT! When you create your config.php file, be sure to add the following line just before the closing '?>' tag:

      define('txpath', $txpcfg['txpath']);

-------------------------------
Step 3: Secure the Installation
-------------------------------

   For security reasons, you should remove the Setup directory once the setup has been completed.

   Remove both of the following:
      sites/site1/admin/setup
      sites/site1/public/setup
   
   --
   
   The site1 template includes a symbolic link to the admin directory inside the public directory. This is only used
   for the alternate setup described below ("Multi-Site with Integrated Admin Area"). Since we will be accessing
   the admin area from a separate subdomain, this symbolic link is not necessary and should be removed:
   
   Remove the following:
      sites/site1/public/admin
   
   --
   
   Once setup is complete, access to the theme directory is no longer needed from the public side.
   
   Remove the following:
      sites/site1/public/theme
   
   --
   
   You may also want to remove the following files and directories from the top-level Textpattern
   directory, as they are not needed when running multi-site:
   
   	files
   	images
   	index.php
      
---------------------------
Step 4: There is no Step 4!
---------------------------

   But if you want to to set up additional sites, simply repeat steps 1 through 3 for each site.
   Your sites directory layout will look like the following:
      sites/
         mysite1.com/
            admin/
            private/
            public/
         mysite2.com/
            admin/
            private/
            public/
         mysite3.com/
            admin/
            private/
            public/


============================================
= B. Multi-Site with Integrated Admin Area =
============================================


This setup method results in the more traditional Textpattern installation, where the admin area
is located in a subdirectory of the main site's domain. For example:

   http://www.mysite.com         <-- URL to your site
   http://www.mysite.com/admin   <-- URL to your site's admin area login

----------------------------
Step 1: Configure Web Server
----------------------------

You will need to use your web host's facilities to create a single virtual host for each of your
Textpattern sites. This configuration step is commonly performed via your host's web panel.

Set the document root of the site virtual host to the public subdirectory of your site.
Also, be sure that the virtual host allows traversing symbolic links.

Here is an example config for Apache to create our virtual host for site1:

      <VirtualHost *:80>
         ServerName mysite.com
         ServerAlias www.mysite.com
         DocumentRoot "/path/to/textpattern/sites/site1/public"
         <Directory "/path/to/textpattern/sites/">
            Options +FollowSymLinks
         </Directory>
      </VirtualHost>

You may need to restart your web server before it will recognize new virtual hosts.

Create a single virtual host configuration for each Textpattern site you will be hosting.

-------------------------
Step 2: Textpattern Setup
-------------------------

You should now be able to proceed through Textpattern's setup process.

   * Go to: http://mysite.com and click the textpattern/setup/ link.

   * Textpattern will tell you to place your config.php file in /textpattern/. This is INCORRECT. Place it in the private
     subdirectory of your site. For example, /sites/site1/private/config.php.
     
   * IMPORTANT! When you create your config.php file, be sure to add the following line just before the closing '?>' tag:

      define('txpath', $txpcfg['txpath']);

   * Once Textpattern creates your site's database tables, it will present a link to the "main interface." This link will
     be INCORRECT, so do not click it. Instead, manually enter the address of your admin area into your browser's address
     bar at this point:
     
        http://mysite.com/admin

-------------------------------
Step 3: Secure the Installation
-------------------------------

   For security reasons, you should remove the Setup directory once setup has completed.

   Remove both of the following:
      sites/site1/admin/setup
      sites/site1/public/setup
   
   --
   
   Once setup is complete, access to the theme directory is no longer needed from the public side.
   
   Remove the following:
      sites/site1/public/theme
   
   --
   
   You may also want to remove the following files and directories from the top-level Textpattern
   directory, as they are not needed when running multi-site:
   
   	files
   	images
   	index.php
      
---------------------------
Step 4: There is no Step 4!
---------------------------

   But if you want to to set up additional sites, simply repeat steps 1 through 3 for each site.
   Your sites directory layout will look like the following:
      sites/
         mysite1.com/
            admin/
            private/
            public/
         mysite2.com/
            admin/
            private/
            public/
         mysite3.com/
            admin/
            private/
            public/


=========================
= Additional Miscellany =
=========================

The default article that is displayed after a clean installation will have some broken links.
This is to be expected, as the links are hard-coded to the traditional /textpattern/ back-end.
