# Radweapst - Rapid Development Web Application Platform by Section Template

It is a simple framework based on the MVC model.  
The biggest feature is that it supports the section template view using PHP associative array.  
A section template is similar to the HTML tag description format, but it has its own command extension that allows you to define a view template that is easier and easier to read than writing a tag format.

This repository is experimental code of a framework for easily creating web applications with PHP.
The code license is based on MIT.

This code is not yet finished.
You will need the app repository and vendor libraries to run as an application.

- app             Application Repository
- vendor          JQuery, and other javascript Library

### SECTION template SAMPLE
```
// Section layout definition
@Header => [// @ ViewTemplate () call
    PageTitle => $ {#TITLE} // Arguments set to variables
    AdditionHeader =>
        ./css/common.css // Combined output
        ./js/common.js // Combined output
    ]
]
*Comment => [ 'What about arrays?' ]
-body => [ bgcolor => white ]     // HTML tag output
+jquery =>                      // JQuery function definition
~
  $(".contents-view").adjustHeight();
~
.appWindow => [                  // Tag name omitted is DIV tag section
  '.split-pane fixed-left' => [
    '.split-pane-component sitemap#left-component' => [
      @TreeMenu
    ]
    .split-pane-divider#v-divider => []
    .split-pane-component#right-component => [
      // Fix at the top
      @Toolbar
      // A block that scrolls #ContentBody (contents-view)
      '.split-pane-component contents-view' =>
         // Area to display content
         #ContentBody => [
           &DocIndex
         ]
       ]
    ]
  ]
]
```
