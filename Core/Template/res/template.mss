// stylesheeet/javascript Template
//
@message => yes
//@comment => off
//@compact => off
// =====================================================
// stylesheet SECTION
Stylesheet => [
    htmlstyle => [
        +import => [
            bodystyle.css        // Template Default Style
		]
        +section => common       //
	]
    common => [
        +import => [
            libstyle.css        // Template Default Style
            context.css         // context menu popup
            floatwin.css        // floating Windows
            markdown.css        // markdown sttle
            slide-panel.css     // slide-panel
            pagerstyle.css      // pager button
            popup.css           // balloon/popupbox/dialog
            @debugger:debugbar.css   // use DEBUGGER,if AppData['debugger'] in SESSION is not FLASE
        ]
    ]
    checklist => [			// debug
        +import => [
            bodystyle.css        // Template Default Style
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
			plugins => [
				misc.js             	// miscellaneous plugin
				baloon.js               // Balloon Help
				floatwin.js             // inner window 
				info-box.js             // info-box for cannot move/resize
				popup-box.js            // popup-box 
			]
            context.js              // context menu popup
            pagerscript.js          // pager button
            slide-panel.js          // Slide-Panel
            window.js               // JQuery-Plugins
            @debugger:debugbar.js   // use DEBUGGER,if AppData['debugger'] in SESSION is not FLASE
        ]
        +import => [
            funcs.js                // common function/prototype
        ]
    ]
    checklist => [			// debug
        +jquery => [
			plugins => [
				checkselect.js
			]
        ]
        +import => [
            funcs.js
        ]
	]
]
