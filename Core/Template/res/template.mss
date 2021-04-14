// stylesheeet/javascript Template
//
@message => yes
//@comment => off
//@compact => off
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
            @debugger:debugbar.css   // use DEBUGGER,if AppData['debugger'] in SESSION is not FLASE
        ]
    ]
    checklist => [			// debug
        +import => [
            libstyle.css
            context.css
            floatwin.css
            markdown.css
            pagerstyle.css
            popup.css
			checklist.css
            debugbar.css
        ]
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
            @debugger:debugbar.js   // use DEBUGGER,if AppData['debugger'] in SESSION is not FLASE
        ]
        +import => [
            funcs.js                // common function/prototype
            window.js               // JQuery-Plugins
        ]
    ]
    checklist => [			// debug
        +import => [
			checklist.js
            funcs.js
            window.js
        ]
	]
]
