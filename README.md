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
+ SQLite3 or PostgreSQL9.6 or MariaDB 5.5, and each Admin Tools

## Installation

1. clone this repository.
1. create 'vendor' directory, and download JQuery  
    vendor/webroot/js/jquery-3.2.1.min.js  
    vendor/webroot/js/jquery-ui-1.12.1/jquery-ui.min.js  
    vendor/webroot/js/jquery-ui-1.12.1/jquery-ui.min.css  
1. create 'app' directory and copy sample application,  
    'tools/app_sample' copy to 'app/sample'.  
1. adjusted '.htaccess' or 'web.config' for your Web-Server.
1. Open Browser, Typical URL is http://localhost/sample/index/

### SECTION template SAMPLE

Detail specification is [Here](../../wiki/Home)


```
// Section layout definition
@Header                           // call other template
<body bgcolor='white'>            // HTML tag output
.appWindow => [                   // Tag name omitted is DIV tag section
  .split-pane.fixed-left => [
    .split-pane-component.sitemap#left-component => [
      @TreeMenu
    ]
    .split-pane-divider#v-divider => []
    .split-pane-component#right-component => [
      .split-pane-component.contents-view.fitWindow =>
         // Area to display content
         #ContentBody => [
           &DocIndex
         ]
       ]
    ]
  ]
]
</body>
```
Rewriting the above section in HTML would be messy as follows:
```
<?php 
  // Section layout definition
  require('Header.php'); 
?>
<body bgcolor='white'>
<div class="appWindow">
  <div class="split-pane fixed-left">
    <div class="split-pane-component sitemap" id="left-component">
      <?php require('TreeMenu.php'); ?>
    </div>
    <div class="split-pane-divider" id="v-divider"></div>
    <div class="split-pane-component" id="right-component">
      <div class="split-pane-component contents-view fitWindow">
        <div id="ContentBody">
          <?php $Helper->DocIndex(); ?>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
```