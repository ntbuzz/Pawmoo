<script type="text/javascript">

$(function() {

$("#myTable").tablesorter({
        theme : 'blue',
		widthFixed : true,
			headerTemplate: '{content} {icon}', // Add icon for jui theme; new in v2.7!
			widgets: [ 'zebra', 'stickyHeaders', 'filter' ],
			widgetOptions: {
	    	filter_external : '.search',
	    	filter_defaultFilter: { 1 : '~{query}' },
    		filter_columnFilters: true,
      		filter_placeholder: { search : 'Search...' },
			filter_saveFilters : true,
	    	filter_reset: '.reset',
			filter_hideFilters : true,
			stickyHeaders_attachTo:'.list-view' // or $('.wrapper')
		}
});

});
</script>
