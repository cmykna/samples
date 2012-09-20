<?php
// A site walker using simple_dom. Used to generate excel spreadsheets for 
// a content audit.
header("Content-type: text/html; charset=utf-8");
define('BASE_URL', 'http://www.hmheducation.com/');
define('UTILS_PATH', $_SERVER['DOCUMENT_ROOT'].'/utils/');
define('MS_HEADER', 'ms-boilerplate/sheet-header.inc.html');
define('WRITE_PATH', UTILS_PATH.'audit-xls/HMH-ContentAudit_FINAL_files/');
define('LOGFILE', UTILS_PATH.'audit-tool/error-log.txt');
include UTILS_PATH.'simple_html_dom.php';
include UTILS_PATH.'prod_microsites.php';

function getPageResources($html) {
	// Catalog page resource links
	$resourceTypes = array('pdf', 'doc', 'xls', 'ppt', 'swf');
	$imageTypes = array('gif', 'png', 'jpg');
	$pageResources = array("HTML");
	try {
		if (!$html) {
			throw new Exception("Empty DOM passed to getPageResources");
		}
	} catch (Exception $e) {
		$log = file_get_contents(LOGFILE);
		$log .= "\n".date(DATE_RFC822).":".$e."\n\n";
		file_put_contents(LOGFILE, $log);
		return $pageResources[] = $e->getMessage();
	}
	foreach ($html->find('.buffer a') as $index => $e) {
		foreach ($resourceTypes as $t) {
			if (strpos($e->href, $t)) {
				$pageResources[] = strtoupper($t);
			}				
		}
	}
	foreach ($html->find('.buffer img') as $e) {
		foreach ($imageTypes as $i) {
			if (strpos($e->src, $i)) {
				$pageResources[] = strtoupper($i);
			}
		}
	}
	return implode(", ", array_unique($pageResources));
}

function collectHeaderURLs($html) {
	// Generate a list of top-level links to crawl
	$headerURLs = array();
	if ($html->find('div#top-nav')) 
	{
		foreach ($html->find('div#top-nav a') as $e) 
		{
			if (!$e->find('img[src$=facebook.png]') && !$e->find('img[src$=twitter.png]')) 
			{
				if ($e->class === 'buyit' OR $e->find('img[alt=Buy Now]')) 
				{
					$headerURLs["Buy Now"] = $e->href;
				} 
				else 
				{
					$headerURLs[$e->plaintext] = $e->href;
				}
			}
		}
	} 
	elseif ($html->find('nav#top-nav')) 
	{
		foreach ($html->find('nav#top-nav a') as $e) 
		{
			if ($e->find('img[src$=buy-now.png]')) 
			{
				$headerURLs["Buy Now"] = $e->href;
			} 
			else if ($e->parent()->class !== "social-network") 
			{
				$headerURLs[$e->plaintext] = $e->href;
			} 
		}
	}
	return $headerURLs;
}

function generateHomepageDescription($html) {
	if ($html->find('div.panel') && $html->find('.three-space-bucket h3'))
	{
		$panels = 0;
		$buckets = array();
		foreach ($html->find('div.panel') as $p) {
			$panels++;
		}
		foreach ($html->find('.three-space-bucket h3') as $b) {
			$buckets[] = $b->plaintext;
		}
		$desc = "Includes {$panels} scrolling spotlights, {$buckets[0]}, {$buckets[1]}, and {$buckets[2]} links.";
		return $desc;	
	} else {
		return "This Microsite appears to be outside standard templates.";
	}
}

function extractFirstParagraph($html) {
	if ($html->find('.buffer')) {
		$firstPara = $html->find('.buffer p');
		// Some of the older sites have first-child p
		// elements with images in them, which return an
		// empty string when their plaintext property is 
		// queried (lookin' at you, /careerpathways). To 
		// get around this, we can loop through ALL of .buffer's
		// p children and return the first plaintext that's
		// not the empty string, then break out of the loop.
		foreach ($firstPara as $p) {
			$t = $p->plaintext;
			if ($t === '' OR $t === '&nbsp;') {
				continue;
			} else {
				echo $t;
				break;
			}
		}
	} else {
		echo "query failed";
	}
}

function getAudience($shortName) {
	if ($shortName === 'sciencefusion-homeschool') {
		return "Parents";
	} else {
		return "Administrators, Teachers";
	}
}

function getGradeLevel($shortName) {
	$stateSites = array(
  		'al', 'ar', 'az', 'ca', 'dc', 'de', 'fl', 'ga', 
  		'hi', 'id', 'il', 'in', 'ky', 'la', 'ma', 'md',
  		'mi', 'nc', 'nj', 'nm', 'ny', 'nyc', 'oh', 'ok', 
  		'or', 'ri', 'tn', 'va', 'wi', 'wv'
	);
	if (in_array($shortName, $stateSites))
	{
		return "K–12";
	}
}
 
// Blastoff!
foreach ($debugArray as $shortName => $micrositeURL):
	echo "Crawling $micrositeURL...<br/>";
	ob_start(); // Main OB
	$siteBeingCrawled = $micrositeURL;
	try {
		$html = file_get_html($siteBeingCrawled);
		if (!$html) {
			throw new Exception("Couldn't fetch a dom for ".$siteBeingCrawled.$url);
		}
	} catch (Exception $e) {
		$log = file_get_contents(LOGFILE);
		$log .= "\n".date(DATE_RFC822).":".$e."\n\n";
		file_put_contents(LOGFILE, $log);
		continue;
	}

	// We're on the homepage now. Let's populate the first table row:
	foreach ($html->find('title') as $e):
		$micrositeName = $e->plaintext;
		$sectionTitle = $e->plaintext;
		generateHomepageDescription($html);
	endforeach;

	// Start OB 2
	ob_start();  
	?>
	<tr class=xl71 height=70 style='height:70.0pt'>
		<td height=70 class=xl94 width=94 style='height:70.0pt;width:94pt'><?php echo $sectionTitle; ?> Homepage</td>
		<td class=xl90 width=212 style='border-left:none;width:212pt'><?php echo $micrositeName; ?></td>
		<td class=xl90 width=206 style='border-left:none;width:206pt'><?php echo $siteBeingCrawled; ?></td>
		<td class=xl93 width=79 style='border-left:none;width:79pt'>No</td>
		<td class=xl90 width=103 style='border-left:none;width:103pt'>Marketing</td>
		<td class=xl91 width=107 style='border-left:none;width:107pt'>Static</td>
		<td class=xl90 width=107 style='border-left:none;width:107pt'><?php echo getPageResources($html); ?></td>
		<td class=xl90 width=107 style='border-left:none;width:107pt'><?php echo $micrositeName; ?> Team</td>
		<td class=xl90 width=107 style='border-left:none;width:107pt'>HMH Web Development Team</td>
		<td class=xl93 width=86 style='border-left:none;width:86pt'>Yes</td>
		<td class=xl90 width=121 style='border-left:none;width:121pt'><?php echo getAudience($shortName); ?></td>
		<td class=xl90 width=104 style='border-left:none;width:104pt'><?php echo getGradeLevel($shortName); ?></td>
		<td class=xl92 style='border-left:none'></td>
		<td class=xl91 width=89 style='border-left:none;width:89pt'>As Needed</td>
		<td class=xl90 width=107 style='border-left:none;width:107pt'>Somewhat Useful</td>
		<td class=xl88 width=257 style='border-left:none;width:257pt'><?php echo generateHomepageDescription($html); ?></td>
		<td class=xl78 width=77 style='width:77pt'>&nbsp;</td>
	</tr>
	<?php
	// End OB 2
	$homeRow = ob_get_clean(); 

	// Crawling the rest of the top-level pages
	$urlToCrawl = collectHeaderURLs($html);
	$topLevelPages = array();
	foreach ($urlToCrawl as $sectionTitle => $url) {
		if ($sectionTitle !== "Buy Now") {

			try {
				$html = file_get_html($siteBeingCrawled.$url);
				if (!$html) {
					throw new Exception("Couldn't fetch a dom for ".$siteBeingCrawled.$url);
				}
			} catch (Exception $e) {
				$log = file_get_contents(LOGFILE);
				$log .= "\n".date(DATE_RFC822).":".$e->getMessage()."\n";
				file_put_contents(LOGFILE, $log);
				continue;
			}

			// Uh, did we actually get anything?
			if ($html) {
				// Now we're crawling top-level links.

				// Page title for our most common 2-column layout
				if ($html->find('#content-right') && $html->find('.buffer h1')) {
					foreach ($html->find('h1') as $e) { 
						$topLevelPageHeader = $e->plaintext;
					}
				// Page title for rarer one-column layouts.
				} else if (!$html->find('#content-right') && $html->find('.buffer h1')) { 
					foreach ($html->find('h1') as $e) {
						$topLevelPageHeader = $e->plaintext;
					}
				// Some ridiculous pages don't have H1s, so we'll use the page title	
				} else if (!$html->find('.buffer h1')) { 
					foreach ($html->find('title') as $e) {
						$topLevelPageHeader = $e->plaintext;
					}
				}
				// Quick check to see if we're on a form page
				if ($html->find('form.contact-form')) {
					$contentType = "Form";
						// Logic to list fields here?
				} else {
					$contentType = "Static";
				}
				// Shove each top level page into a buffer
				
				// Start OB 2
				ob_start(); 
				?>
				<tr class=xl71 height=70 style='height:70.0pt'>
					<!-- Section title -->
					<td height=70 class=xl94 width=94 style='height:70.0pt;width:94pt'><?php echo $sectionTitle; ?></td>
					<!-- Page title -->
					<td class=xl90 width=212 style='border-left:none;width:212pt'><?php echo $topLevelPageHeader; ?></td>
					<!-- Content URL -->
					<td class=xl90 width=206 style='border-left:none;width:206pt'><?php echo $siteBeingCrawled.$url; ?></td>
					<!-- Authentication -->
					<td class=xl93 width=79 style='border-left:none;width:79pt'>No</td>
					<!-- Content category -->
					<td class=xl90 width=103 style='border-left:none;width:103pt'>Marketing</td>
					<!-- Content type -->
					<td class=xl91 width=107 style='border-left:none;width:107pt'>Static</td>
					<!-- Page resources -->
					<td class=xl90 width=107 style='border-left:none;width:107pt'><?php echo getPageResources($html); ?></td>
					<!-- Owner -->
					<td class=xl90 width=107 style='border-left:none;width:107pt'><?php echo $micrositeName; ?> Team</td>
					<!-- Maintainer -->
					<td class=xl90 width=107 style='border-left:none;width:107pt'>HMH Web Development Team</td>
					<!-- Includes shared content? -->
					<td class=xl93 width=86 style='border-left:none;width:86pt'>Yes</td>
					<td class=xl90 width=121 style='border-left:none;width:121pt'><?php echo getAudience($shortName); ?></td>
					<td class=xl90 width=104 style='border-left:none;width:104pt'><?php echo getGradeLevel($shortName); ?></td>
					<td class=xl92 style='border-left:none'></td>
					<td class=xl91 width=89 style='border-left:none;width:89pt'>As Needed</td>
					<td class=xl90 width=107 style='border-left:none;width:107pt'>Somewhat Useful</td>
					<td class=xl88 width=257 style='border-left:none;width:257pt'><?php echo extractFirstParagraph($html); ?></td>
					<td class=xl78 width=77 style='width:77pt'>&nbsp;</td>
				</tr>
				<?php
				// End OB 2
				$topLevelPageRow = ob_get_clean(); 
				$topLevelPages[] = $topLevelPageRow;

				$secondaryUrlToCrawl = array();

				foreach ($html->find('ul.tabs a') as $e) {
					$secondaryUrlToCrawl[] = $e->href;
				}
				foreach ($html->find('div.buffer a') as $e) {
					$secondaryUrlToCrawl[] = $e->href;
				}
			} else { 
				// Aw crap, no DOM object returned. This is usually because of some abnormal markup somewhere on the page.
				
				// Start OB 2
				ob_start(); 
				?>
				<tr class=xl71 height=70 style='height:70.0pt'>
					<td colspan=17 class=xl94>Couldn't open a DOM for this URL.</td>
				</tr>
				<?php 
				$topLevelPageRow = ob_get_clean();
				$topLevelPages[] = $topLevelPageRow;
			}
		} else if ($sectionTitle === "Buy Now") {
			// Start OB 2
			ob_start(); 
			?>
			<tr class=xl71 height=70 style='height:70.0pt'>
				<td height=70 class=xl94 width=94 style='height:70.0pt;width:94pt'><?php echo $sectionTitle; ?></td>
				<td class=xl90 width=212 style='border-left:none;width:212pt'>Buy Now</td>
				<td class=xl90 width=206 style='border-left:none;width:206pt'><?php echo $url; ?></td>
				<td class=xl93 width=79 style='border-left:none;width:79pt'>No</td>
				<td class=xl90 width=103 style='border-left:none;width:103pt'>Product</td>
				<td class=xl91 width=107 style='border-left:none;width:107pt'>Static</td>
				<td class=xl90 width=107 style='border-left:none;width:107pt'><?php echo getPageResources($html); ?></td>
				<td class=xl90 width=107 style='border-left:none;width:107pt'><?php echo $micrositeName; ?> Team</td>
				<td class=xl90 width=107 style='border-left:none;width:107pt'>HMH E-Commerce Team</td>
				<td class=xl93 width=86 style='border-left:none;width:86pt'>No</td>
				<td class=xl90 width=121 style='border-left:none;width:121pt'><?php echo getAudience($shortName); ?></td>
				<td class=xl90 width=104 style='border-left:none;width:104pt'><?php echo getGradeLevel($shortName); ?></td>
				<td class=xl92 style='border-left:none'></td>
				<td class=xl91 width=89 style='border-left:none;width:89pt'>As Needed</td>
				<td class=xl90 width=107 style='border-left:none;width:107pt'>Somewhat Useful</td>
				<td class=xl88 width=257 style='border-left:none;width:257pt'></td>
				<td class=xl78 width=77 style='width:77pt'>&nbsp;</td>
			</tr>
			<?php
			// End OB2
			$buyNowRow = ob_get_clean(); 
			$topLevelPages[] = $buyNowRow;
		}
	}	

	echo $homeRow;
	foreach ($topLevelPages as $row) {
		echo $row;
	}
	echo '</table></body></html>';
	$mainBuffer = ob_get_clean();

	// We've got all the info we care about, but we need to 
	// do some quick s/r action to the Excel HTML boilerplate we're 
	// appending the data to.

	// Filename is just 'microsite-name.html'
	$fname = trim(parse_url($micrositeURL, PHP_URL_PATH), "/").'.html';

	// Copy the contents of the Excel boilerplate to microsite-name.html,
	// then open it up so we can start munging it.
	if (file_exists(MS_HEADER) && is_dir(WRITE_PATH)) {
		copy(MS_HEADER, WRITE_PATH.$fname);
		$mungeString = file_get_contents(WRITE_PATH.$fname);
	}

	// SnR time
	$patterns = array("__MICROSITE_URL__", "__MICROSITE_NAME__", "<title>Content Audit Template</title>");
	$replacements = array($micrositeName, $siteBeingCrawled, "<title>Content Audit Template::{$fname}</title>");
	$outfile = str_replace($patterns, $replacements, $mungeString);

	// NOW we can write out all the crawling stuff we did earlier.
	$outfile .= $mainBuffer;
	file_put_contents(WRITE_PATH.$fname, $outfile);
	echo "done.<br/>";
	sleep(5);
endforeach;


