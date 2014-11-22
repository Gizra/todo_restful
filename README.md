[![Build Status](https://travis-ci.org/Gizra/todo_restful.svg)](https://travis-ci.org/Gizra/todo_restful)

# Drupal 7 - Install Profile ToDo

1. Follow the below [Installation](https://github.com/Gizra/todo_restful#installation) notes
1. If you Drupal site is not running under ``http://localhost/todo_restful/www`` then in ``client/Gruntfile.js`` 
  change the ``apiEndpoint`` in line 404 to the correct one
1. Under ``client`` directory execute ``grunt serve``
1. Add to Drupal's ``settings.php``:
```
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, access_token, Content-Type');
```

## Installation

**Warning:** you need to setup [Drush](https://github.com/drush-ops/drush)
first or the installation and update scripts will not work.

Clone the project from [GitHub](https://github.com/Gizra/todo_restful).

#### Create config file

Copy the example configuration file to config.sh:

	$ cp default.config.sh config.sh

Edit the configuration file, fill in the blanks.


#### Run the install script

Run the install script from within the root of the repository:

	$ ./install

You can login automatically when the installation is done. Add the -l argument
when you run the install script.

  $ ./install -l


#### Configure web server

Create a vhost for your webserver, point it to the `REPOSITORY/ROOT/www` folder.
(Restart/reload your webserver).

Add the local domain to your ```/etc/hosts``` file.

Open the URL in your favorite browser.



## Reinstall

You can Reinstall the platform any type by running the install script.

	$ ./install


#### The install script will perform following steps:

1. Delete the /www folder.
2. Recreate the /www folder.
3. Download and extract all contrib modules, themes & libraries to the proper
   subfolders of the profile.
4. Download and extract Drupal 7 core in the /www folder
5. Create an empty sites/default/files directory
6. Makes a symlink within the /www/profiles directory to the /todo
   directory.
7. Run the Drupal installer (Drush) using the ToDo profile.

#### Warning!

* The install script will not preserve the data located in the
  sites/default/files directory.
* The install script will clear the database during the installation.

**You need to take backups before you run the install script!**



## Upgrade

It is also possible to upgrade Drupal core and contributed modules and themes
without destroying the data in tha database and the sites/default directory.

Run the upgrade script:

	$ ./upgrade

You can login automatically when the upgrade is finished. Add the -l argument
when you run the upgrade script.

  $ ./upgrade -l


#### The upgrade script will perform following steps:

1. Create a backup of the sites/default folder.
2. Delete the /www folder.
3. Recreate the /www folder.
4. Download and extract all contrib modules, themes & libraries to the proper
   subfolders of the profile.
5. Download and extract Drupal 7 core in the /www folder.
6. Makes a symlink within the /www/profiles directory to the
   /todo 7. directory.
7. Restore the backup of the sites/default folder.
