// stylesheeet/javascript Template
//
@message => yes
// =====================================================
// stylesheet SECTION
Stylesheet => [
    common => [
        +import => [
            libstyle.css        // Template Default Style
            context.css         // context menu popup
            floatwin.css        // floating Windows
            markdown.css        // markdown sttle
            pagerstyle.css      // pager button
            popup.css           // balloon/popupbox/dialog
        ]
        +section => @debugbar        // active on DEBUGGER flag is ON
    ]
    debugbar => [
        +import => debugbar.css
    ]
]
// =====================================================
// javascript SECTION
Javascript => [
    common => [
        +jquery => [
            baloon.js               // Balloon Help
            info-box.js             // info-box for cannot move/resize
            popup-box.js            // popup-box 
            context.js              // context menu popup
            floatwin.js             // inner window 
            pagerscript.js          // pager button
        ]
        +import => [
            funcs.js                // common function/prototype
            window.js               // JQuery-Plugins
        ]
        +section => @debugbar       // active on DEBUGGER flag is ON
    ]
    debugbar => [
        +jquery => debugbar.js
    ]
]
