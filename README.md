## Pawmoo

This repository is a personal PHP framework experiment site.  
  
It is a simple framework based on the MVC model.  
The most distinctive feature is the support for section template views using PHP associative arrays.
A section template is similar to the HTML tag description format, but it has its own command extension that allows you to define a view template that is easier and easier to read than writing a tag format.

This repository is experimental code of a framework for easily creating web applications with PHP.
The code license is based on MIT.

This code is not yet finished.
You will need the app repository and vendor libraries to run as an application.

```
- app             Application Repository
- vendor          JQuery, and other javascript/PHP Library
```
'app' and 'vendor' folder structure, refer to 'tools/docs/Manual.txt' (JP-UTF8)

## System Requirement

+ PHP-5.6 or Higher.
+ Apache 2.4 Web Server or IIS 7.0
+ SQLite3 or PostgreSQL9.6 and each Admin Tools

## Installation

1. clone this repository.
1. create 'vendor' directory, and download JQuery  
    vendor/webroot/js/jquery-3.2.1.min.js  
    vendor/webroot/js/jquery-ui-1.12.1/jquery-ui.min.js  
    vendor/webroot/js/jquery-ui-1.12.1/jquery-ui.min.css  
1. adjusted '.htaccess' or 'web.config' for your Web-Server.
1. execute application Setup


## Application Setup

1. Create Application Folder
     # ./setup.sh create <appname>
1. Create Application Database Specification
     # ./setup.sh spec <appname>
     (edit Database specification CSV into appSpec folder. See SpecCSV.)
1. Create Model Scheama and Model Class,Lang resource from Spec CSV.
     # ./setup.sh schema <appname>
        if specific module convert, 'setup <appname> <module>'
1. Create Model Class and Lang resource from Model Schema.
     # ./setup.sh model <appname>
        if specific module, setup <appname> <module>
　   ./setup.sh setup ... command execute <schema> and <model> at onece.
1. Create Database Table (with table view,and import data-csv)
     # ./setup.sh table <appname>
1. Create Module folder into 'app/<appname>/modules' folder.
     # ./setup.sh module <appname> <module>
1. Copy to Model class file from 'appSpec/<appname>/Models' folder to app module folder
1. Edit 'Controller','Model','Helper' files for Application.
