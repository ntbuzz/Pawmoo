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
			loader_icon.css		// wait icon
            @debugger:debugbar.css   // use DEBUGGER,if AppData['debugger'] in SESSION is not FLASE
        ]
    ]
	debugbar => [
        +import => [
            libstyle.css        // Template Default Style
            debugbar.css
        ]
	]
    testmode => [			// debug
        +import => [
            bodystyle.css        // Template Default Style
            libstyle.css
            context.css
			dropfile.css
            floatwin.css
            markdown.css
            pagerstyle.css
            popup.css
			checklist.css
            debugbar.css
			tabset.css
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
				balloon.js               // Balloon Help
				floatwin.js             // inner window 
				info-box.js             // info-box for cannot move/resize
				popup-box.js            // popup-box 
				checkselect.js			// popup checkbox or radio button
			]
            context.js              // context menu popup
            pagerscript.js          // pager button
            slide-panel.js          // Slide-Panel
            window.js               // JQuery-Plugins
            @debugger:debugbar.js   // use DEBUGGER,if AppData['debugger'] in SESSION is not FLASE
        ]
        +import => [
            prototypes.js           // prototype functions
            funcs.js                // common function/prototype
        ]
    ]
	debugbar => [
        +jquery => [
			plugins => [
				misc.js             	// miscellaneous plugin
			]
            debugbar.js
        ]
	]
    testmode => [			// debug
        +jquery => [
			plugins => [
				misc.js             	// miscellaneous plugin
				balloon.js               // Balloon Help
				floatwin.js             // inner window 
				info-box.js             // info-box for cannot move/resize
				popup-box.js            // popup-box 
				checkselect.js
			]
            context.js              // context menu popup
            pagerscript.js          // pager button
            slide-panel.js          // Slide-Panel
			tabset.js
            window.js               // JQuery-Plugins
        ]
        +import => [
            prototypes.js           // prototype functions
            funcs.js                // common function/prototype
			dropfile.js
        ]
	]
]
