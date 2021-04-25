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
		+plugins => [
            plugins/misc.js             	// miscellaneous plugin
            plugins/baloon.js               // Balloon Help
            plugins/floatwin.js             // inner window 
            plugins/info-box.js             // info-box for cannot move/resize
            plugins/popup-box.js            // popup-box 
		]
        +jquery => [
            context.js              // context menu popup
            pagerscript.js          // pager button
            window.js               // JQuery-Plugins
            @debugger:debugbar.js   // use DEBUGGER,if AppData['debugger'] in SESSION is not FLASE
        ]
        +import => [
            funcs.js                // common function/prototype
        ]
    ]
    checklist => [			// debug
		+plugins => [
			plugins/checklist.js
		]
        +jquery => [
            window.js               // JQuery-Plugins
        ]
        +import => [
            funcs.js
        ]
	]
]
