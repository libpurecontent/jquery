<?php

# Class to abstract jQuery interactions for common server-side data structure patterns
class jQuery
{
	# Class properties
	private $html = '';
	private $databaseConnection = NULL;
	private $username = false;
	private $ajaxTarget = false;	// Data URL
	
	
	# Constructor
	public function __construct ($databaseConnection = false, $ajaxTarget = false, $username = false, $jQueryLoaded = false)
	{
		# Load the jQuery JS if not already loaded
		if (!$jQueryLoaded) {
			$this->html .= "\n\n\n" . '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>';
		}
		
		# Register the username and AJAX target
		$this->databaseConnection = $databaseConnection;
		$this->ajaxTarget = $ajaxTarget;
		$this->username = $username;
		
	}
	
	
	# Getter for the HTML
	public function getHtml ()
	{
		# Return the HTML
		return $this->html;
	}
	
	
	
	/* -------------------------------------------- */
	/* ---------------- Expandable ---------------- */
	/* -------------------------------------------- */
	
	
	# Expandable
	/* $panels needs to be
		array (
			'uniqueId1' => "<h3>Heading</h3>" . "Whatever content you want",
			'uniqueId2' => "<h3>Another heading</h3>" . "Whatever content you want",
			...
		)
		$expandState is true (for expand-all) a comma-separated string of any panels to be opened by default (e.g. 'uniqueId1,uniqueId3')
	*/
	public function expandable ($panels, $expandState = false, $saveState = true, $headingTag = 'h3')
	{
		# Determine whether to enable a button for expanding the whole listing
		$expandAll = ($expandState === true);
		
		# Create the HTML
		$html  = "\n" . '
		<!-- http://www.adipalaz.com/experiments/jquery/multiple_expand_all_collapse_all.html -->
		<script type="text/javascript" src="/sitetech/jquery/expand.js"></script>
		<script type="text/javascript">
			$(function() {' . "
				$('#content {$headingTag}.expand').toggler(" . ($saveState ? "{ajaxTarget: '{$this->ajaxTarget}', username: '" . htmlspecialchars ($this->username) . "'}" : '') . ");
				" . ($expandAll ? "$('#content div.expandable').expandAll({trigger: '{$headingTag}.expand', ref: '{$headingTag}.expand'});" : '') . '
			});
		</script>
		';
		
		# Turn the expansion list string into an array
		$expandState = ($expandState && is_string ($expandState) ? explode (',', $expandState) : array ());
		
		# Compile the tables HTML
		$html .= "\n\n" . '<div class="expandable">';
		foreach ($panels as $group => $entry) {
			
			# Extract the heading and the body of the panel, ending if the structure doesn't match
			if (!preg_match ("~^\s+<{$headingTag}[^>]*>(.+)</{$headingTag}>(.+)$~siU", $entry, $matches)) {return false;}
			$heading = $matches[1];
			$panel = $matches[2];
			
			# Compile the HTML for this entry
			$html .= "\n\n\n<{$headingTag} id=\"{$group}\" class=\"expand\">{$heading}</{$headingTag}>";
			$html .= "\n\n" . '<div class="collapse' . (in_array ($group, $expandState) ? ' shown' : '') . '">';
			$html .= "\n" . $panel;
			$html .= "\n" . '</div>';
		}
		$html .= "\n" . '</div>';
		
		# Register the HTML
		$this->html .= $html;
	}
	
	
	# Function to save the state of the heading expansion
	function expandable_data ($database, $table, $usernameField, $stateField = 'state')
	{
		# End if no data posted
		$username = (isSet ($_POST['username']) ? $_POST['username'] : false);
		$state = (isSet ($_POST['state']) ? $_POST['state'] : false);
		if (!strlen ($username) || !strlen ($state)) {return false;}
		
		# Check security
		if ($username != $this->username) {return false;}
		
		# Update the database
		$this->databaseConnection->update ($database, $table, array ($stateField => $state), array ($usernameField => $username));
	}
	
	
	
	/* -------------------------------------------- */
	/* ---------------- ADD MORE HERE ---------------- */
	/* -------------------------------------------- */
	
	
	# Lightbox
	public function lightbox ($path)
	{
		# Define the HTML
		$html = '
		<!-- http://leandrovieira.com/projects/jquery/lightbox/ -->
		<script type="text/javascript" src="/sitetech/jquery/jquery-1.3.2.js"></script>
		<script type="text/javascript" src="/sitetech/jquery/jquery-lightbox/js/jquery.lightbox-0.5.js"></script>
		<link rel="stylesheet" type="text/css" href="/sitetech/jquery/jquery-lightbox/css/jquery.lightbox-0.5.css" media="screen" />
		<script type="text/javascript">
			$(function() {
				$("' . $path . '").lightBox({
					imageLoading:	"/sitetech/jquery/jquery-lightbox/images/lightbox-ico-loading.gif",		// (string) Path and the name of the loading icon
					imageBtnPrev:	"/sitetech/jquery/jquery-lightbox/images/lightbox-btn-prev.gif",		// (string) Path and the name of the prev button image
					imageBtnNext:	"/sitetech/jquery/jquery-lightbox/images/lightbox-btn-next.gif",		// (string) Path and the name of the next button image
					imageBtnClose:	"/sitetech/jquery/jquery-lightbox/images/lightbox-btn-close.gif",		// (string) Path and the name of the close btn
					imageBlank:		"/sitetech/jquery/jquery-lightbox/images/lightbox-blank.gif",			// (string) Path and the name of a blank image (one pixel)
				});
			});
		</script>';
		
		# Register the HTML
		$this->html = $html;
	}
	
	
	# Dynamic tabs; see http://jqueryfordesigners.com/jquery-tabs/
	public function tabs ($labels, $panes, $switchToTabNumber = '0', $fixedHtml = false, $class = false)
	{
		# Create tab headings
		$tabsHtml = '';
		foreach ($labels as $key => $labelHtml) {
			$tabsHtml .= "\t<li><a href=\"#{$key}\">{$labelHtml}</a></li>";
		}
		
		# Create the content containers
		$panelsHtml = '';
		foreach ($panes as $key => $paneHtml) {
			$panelsHtml .= "\n\t<div id=\"{$key}\">";
			$panelsHtml .= "\n\t\t{$paneHtml}";
			$panelsHtml .= "\n\t</div>";
		}
		
		# Create the tabs
		$html  = '
			<ul id="switchabletabs" class="tabs">' . $tabsHtml . "\n\t" . '</ul>
			' . $fixedHtml .	/* HTML which is placed between the tabs themselves and the content panes */
			"\n" . '<div id="switchabletabs-content"' . ($class ? " class=\"{$class}\"" : '') . '>
			' . $panelsHtml . '
			</div><!-- /#switchabletabs-content -->';
		
		# jQuery code to enable switchability
		$html .= "
			<script type=\"text/javascript\">
				$(function () {
				    var tabContainers = $('#switchabletabs-content > div');
				    
				    $('ul#switchabletabs a').click(function () {
				        tabContainers.hide().filter(this.hash).show();
				        
				        $('ul#switchabletabs li').removeClass('selected');
				        $(this).parent().addClass('selected');
				        
				        return false;
				    }).filter(':eq(" . ($switchToTabNumber) . ")').click();
				});
			</script>
		";
		
		# Register the HTML
		$this->html = $html;
	}
}

?>