EMPS Web Framework

EMPS stands for 'Engine', 'Model', 'Procedure', 'Smarty'. The earlier versions of the engine used to contain folders named 'e', 'm', 'p', and 'Smarty' to store the different component scripts of the framework. Those letters comprise the acronym 'emps'. The latest versions of EMPS do not contain those folders, but the name stuck anyway.

Smarty (http://www.smarty.net/) is a vital component of the framework, you have to store it somewhere in the include_path of your server's PHP. EMPS will expect to find Smarty 3 at: "Smarty3/libs/Smarty.class.php". Earlier versions supported Smarty 2, but this one only accepts Smarty 3.

EMPS is an MVC framework, which means that PHP code is totally separated from HTML templates by means of Smarty. Controllers and views are stored together as a *.php and a *.nn.htm file in a sub-folder of the "modules" folder. The "nn" in the view's file name means 'default language'. You can have two or more views for different languages.

The PHP controllers are regular plain PHP procedure scripts. The scripts can access all EMPS functions through the $emps object variable and all Smarty functions through the $smarty object variable.

EMPS supports multiple websites on a single set of modules (one engine - many websites) and several languages across websites or even on a single website.

The core of the EMPS framework is supposed to be loaded through "require_once" from somewhere on the "include_path". This will enable several websites on the same server to share the EMPS code (which will enable updates, bugfixing, etc.).

EMPS is Git-friendly. No data, no HTML templates, and no code vital to the website being developed is never stored in the database, all code and templates is stored in the module folders as files.

The SQL database structure is stored in a specially-cooked SQL file that enables "sqlsync" - automatic synchronization of the actual database structure with the SQL file. Update the SQL file, call /sqlsync/ on the website, and your website's SQL (MySQL) database gets updated automatically (no manual adding of new fields in phpMyAdmin).

MORE INFORMATION HERE:

http://php-emps.net/

