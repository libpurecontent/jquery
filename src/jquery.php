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
			$this->html .= "\n\n\n" . '<script type="text/javascript" src="//code.jquery.com/jquery.min.js"></script>';
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
		
		# Get the HTML for the heading
		$html = $this->expandable_heading ($headingTag, $saveState, $expandAll);
		
		# Turn the expansion list string into an array
		$expandState = ($expandState && is_string ($expandState) ? explode (',', $expandState) : array ());
		
		# Compile the tables HTML
		$html .= "\n\n" . '<div class="expandable">';
		foreach ($panels as $group => $entry) {
			
			# Extract the heading and the body of the panel, ending if the structure doesn't match
			if (!preg_match ("~^\s*<{$headingTag}[^>]*>(.+)</{$headingTag}>(.+)$~siU", $entry, $matches)) {return false;}
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
	
	
	# Function to provide the expandability heading
	private function expandable_heading ($headingTag, $saveState, $expandAll)
	{
		# Create the HTML
		return $html  = "\n" . '
		<!-- http://www.adipalaz.com/experiments/jquery/multiple_expand_all_collapse_all.html -->
		<script type="text/javascript" src="/sitetech/jquery/expand.js"></script>
		<script type="text/javascript">
			$(function() {' . "
				
				// Toggler
				$('#content {$headingTag}.expand').toggler ();
				" . ($expandAll ? "$('#content div.expandable').expandAll ({trigger: '{$headingTag}.expand'});" : '') .
				
				($saveState ? "
				// State saving
				$('div.expandable {$headingTag}.expand').click (function () {
					var ids = [];
					$('div.expandable {$headingTag}.expand a.open').parent ('{$headingTag}').each (function () {
						ids.push (this.id);
					});
					$.post ('{$this->ajaxTarget}', {username: '" . htmlspecialchars ($this->username) . "', state: ids.join (',')});
				});
				" : '') . '
			});
		</script>
		';
	}
	
	
	# Function to save the state of the heading expansion
	function expandable_data ($database, $table, $usernameField, $stateField = 'state')
	{
		# End if no data posted
		$username = (isSet ($_POST['username']) ? $_POST['username'] : false);
		$state = (isSet ($_POST['state']) ? $_POST['state'] : false);
		#if (!strlen ($username) || !strlen ($state)) {return false;}
		if ($state === false) {return false;}	// State could be an empty string '' so this checks against explicit false
		
		# Check security
		if ($username != $this->username) {return false;}
		
		# Update the database
		$this->databaseConnection->update ($database, $table, array ($stateField => $state), array ($usernameField => $username));
	}
	
	
	# Function to take an existing HTML page and add expansion automatically without marking up
	public function autoExpander ($filename, $tag = 'h2', $expandFirst = true)
	{
		# Read the file
		if (!is_readable ($filename)) {return false;}
		$html = file_get_contents ($filename);
		
		#!# Ideally this would split the text into the data structure used by self::expandable() but this turns out to be quite hard to do reliably in HTML
		
		# Add the expand class to each tag
		require_once ('application.php');
		$html = application::addClassesToTags ($html, $tag, 'expand');
		
		# Add the divs around each block between the specified tag
		$collapseTag = '<div class="collapse">';
		$collapseTagFirst = '<div class="collapse shown">';
		$html  = str_replace ("</{$tag}>", "</{$tag}>\n\n{$collapseTag}\n", $html);
		$html  = str_replace ("<{$tag}", "\n</div>\n\n<{$tag}", $html);
		$html  = "\n<div>" . $html . "\n</div>";
		$html  = "\n" . '<div class="expandable">' . $html . "\n</div>";
		
		# Expand the first if required
		if ($expandFirst) {
			$html = preg_replace ("/{$collapseTag}/", $collapseTagFirst, $html, 1);
		}
		
		# Add the HTML for the heading
		$heading = $this->expandable_heading ($tag, false, false);
		$html = $heading . $html;
		
		# Register the HTML
		$this->html .= $html;
	}
	
	
	
	/* -------------------------------------------- */
	/* ---------------- Lightbox ------------------ */
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
		$this->html .= $html;
	}
	
	
	
	/* -------------------------------------------- */
	/* ---------------- Tabs ---------------------- */
	/* -------------------------------------------- */
	
	
	# Dynamic tabs; note that the label ordering is the ordering used for both labels and panes
	public function tabs ($labels, $panes, $switchToTabNumber = 0, $fixedHtml = false, $class = false, $tabsClass = 'tabs')
	{
		# Define the base name
		$basename = 'switchabletabs';
		$cookieValidityDays = 1;
		
		# Create tab headings
		$tabsHtml = '';
		foreach ($labels as $key => $labelHtml) {
			$tabsHtml .= "\n\t<li id=\"{$basename}-tab-{$key}\"><a href=\"#{$key}\">{$labelHtml}</a></li>";
		}
		
		# Create the content containers, maintaining the same ordering as the tabs for consistency in the HTML (which will matter if the user has javascript disabled)
		$panelsHtml = '';
		foreach ($labels as $key => $labelHtml) {
			if (!isSet ($panes[$key])) {continue;}
			$paneHtml = $panes[$key];
			$panelsHtml .= "\n\t<div id=\"{$key}\">";
			$panelsHtml .= "\n\t\t{$paneHtml}";
			$panelsHtml .= "\n\t</div>";
		}
		
		# Create the tabs
		$html  = '
			<ul id="' . $basename . '" class="' . $tabsClass . '">' . $tabsHtml . "\n\t" . '</ul>
			' . $fixedHtml .	/* HTML which is placed between the tabs themselves and the content panes */
			"\n" . '<div id="' . $basename . '-content"' . ($class ? " class=\"{$class}\"" : '') . '>
			' . $panelsHtml . '
			</div><!-- /#' . $basename . '-content -->';
		
		# jQuery code to enable switchability
		$html .= "
			<script type=\"text/javascript\">
				$(function () {
					
					// Firstly, get the default tab index number
					var cookieName = '{$basename}-current';
					var tabInCookieId = ' #{$basename}-tab-' + readCookie(cookieName,name);
					var tabInCookie = $(tabInCookieId).index('#{$basename} li');		// See: http://api.jquery.com/index/
					var defaultTabNumber = (tabInCookie > -1 ? tabInCookie : " . $switchToTabNumber . ");
					
					// See: http://jqueryfordesigners.com/jquery-tabs/
					var tabContainers = $('#{$basename}-content > div');
					$('ul#{$basename} a').click(function () {
						tabContainers.hide().filter(this.hash).show();
				        
						$('ul#{$basename} li').removeClass('selected');
						$(this).parent().addClass('selected');
						
						createCookie(cookieName,this.hash.slice(1),{$cookieValidityDays});
						
						return false;
					}).filter(':eq(' + defaultTabNumber + ')').click();
				});
				
				// See: http://www.quirksmode.org/js/cookies.html
				function createCookie(name,value,days) {
					if (days) {
						var date = new Date();
						date.setTime(date.getTime()+(days*24*60*60*1000));
						var expires = '; expires='+date.toGMTString();
					}
					else var expires = '';
					document.cookie = name+'='+value+expires+'; path=/';
				}
				function readCookie(name) {
					var nameEQ = name + '=';
					var ca = document.cookie.split(';');
					for(var i=0;i < ca.length;i++) {
						var c = ca[i];
						while (c.charAt(0)==' ') c = c.substring(1,c.length);
						if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
					}
					return null;
				}
				
			</script>
		";
		
		# Register the HTML
		$this->html .= $html;
	}
	
	
	/* -------------------------------------------- */
	/* ---------------- Galleria ------------------ */
	/* -------------------------------------------- */
	
	
	# Galleria wrapper function
	public function galleria ($divId = 'gallery', $delayMs = 5000, $width = 800, $height = 522)
	{
		# Compile the HTML
		$html = '
		<!-- Galleria -->
		<script type="text/javascript" src="/sitetech/jquery/galleria/galleria-1.2.5.min.js"></script>
		<script type="text/javascript">
			Galleria.loadTheme("/sitetech/jquery/galleria/themes/classic/galleria.classic.min.js");
			$(\'#' . $divId . '\').galleria({
				width: ' . $width . ',
				height: ' . $height . ',
				autoplay: ' . $delayMs . ',
				pauseOnInteraction: true,
				extend: function(options) {
        			Galleria.get(0).$(\'info-link\').click();
   				 }
			});
		</script>';
		
		# Register the HTML
		$this->html .= $html;
	}
	
	
	
	/* -------------------------------------------- */
	/* ---------------- Carousel ------------------ */
	/* -------------------------------------------- */
	
	
	# Carousel wrapper function
	public function carousel ($size = 150, $visible = 4, $delay = 4)
	{
		$html = '
		<!-- http://sorgalla.com/projects/jcarousel/ -->
		<script type="text/javascript" src="/sitetech/jquery/jquery-1.3.2.js"></script>
		<script type="text/javascript" src="/sitetech/jquery/jcarousel/lib/jquery.jcarousel.pack.js"></script>
		<link rel="stylesheet" type="text/css" href="/sitetech/jquery/jcarousel/lib/jquery.jcarousel.css" />
		<link rel="stylesheet" type="text/css" href="/sitetech/jquery/jcarousel/skins/purecontent/skin.css" />
		<style type="text/css">
			.jcarousel-skin-purecontent .jcarousel-container-horizontal {
			    width: ' . (($size * $visible) + 20) . 'px;
			}
			
			.jcarousel-skin-purecontent .jcarousel-container-vertical {
			    width: 150px;
			    height: ' . (($size * $visible) + 20) . 'px;
			}
			
			.jcarousel-skin-purecontent .jcarousel-clip-horizontal {
			    width:  ' . (($size * $visible) + 20) . 'px;
			    height: ' . ($size + 100 + 20) . 'px;
			}
			
			.jcarousel-skin-purecontent .jcarousel-clip-vertical {
			    width:  ' . $size . 'px;
			    height: ' . (($size * $visible) + 20) . 'px;	/* 75*3 + 20 = 245 */
			}
			
			.jcarousel-skin-purecontent .jcarousel-item {
			    width: ' . $size . 'px;
			    height: ' . ($size + 100) . 'px;
			}
		</style>
		' . "
		<script type=\"text/javascript\"> 
			
			function mycarousel_itemVisibleInCallback(carousel, item, i, state, evt)
			{
			    // The index() method calculates the index from a
			    // given index who is out of the actual item range.
			    var idx = carousel.index(i, mycarousel_itemList.length);
			    carousel.add(i, mycarousel_getItemHTML(mycarousel_itemList[idx - 1]));
			};
			
			function mycarousel_itemVisibleOutCallback(carousel, item, i, state, evt)
			{
			    carousel.remove(i);
			};
			
			/**
			 * Item html creation helper.
			 */
			function mycarousel_getItemHTML(item)
			{
			    return '<img src=\"' + item.url + '\" width=\"{$size}\" height=\"{$size}\" alt=\"\" /><br >' + item.title;
			};
			
			jQuery(document).ready(function() {
			    jQuery('#mycarousel').jcarousel({
			        wrap: 'circular',
			        itemVisibleInCallback: {onBeforeAnimation: mycarousel_itemVisibleInCallback},
			        itemVisibleOutCallback: {onAfterAnimation: mycarousel_itemVisibleOutCallback},
					// Configuration goes here
					scroll: 1,
					auto: {$delay},
					visible: {$visible}
			    });
			});
			
		</script>";
		
		# Add the HTML
		$html .= '
		<ul id="mycarousel" class="jcarousel-skin-purecontent">
			<!-- The content will be dynamically loaded in here -->
		</ul>';
		
		# Register the HTML
		$this->html .= $html;
	}
}

?>