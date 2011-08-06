var TinyMce = function() {
	
	var priv = { };
	
	return { };
} ();

$(document).ready(function() {
	tinyMCE.init({
        mode : "specific_textareas",
        editor_selector : "mce-editor",
        theme : "advanced",
        theme_advanced_buttons1 : "bold,italic,underline,strikethrough," 
        	+ "|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,|,hr,removeformat",
        theme_advanced_buttons2 : "bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|" +
        	",link,unlink,image,cleanup|preview",
        theme_advanced_buttons3 : "",
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom",
        theme_advanced_resizing : true
	});
});