<?php
// For the most part, hmhed URIs are mapped directly to the file system.
// Homeworks has like 1000+ products in it, so that's obviously not
// gonna work. Let's slap together some semblance of a routing system.
//
// _In hindsight, I should've just used friggin' codeigniter for this whole project_
//
// All this crap should be broken out into a url/routing class
// and called from the index page. In the interest of getting a working
// prototype out the door, we'll handle it ghetto for now.

// ADDITIONALLY! This is pretty /homeworks specific, which we'll have
// to fix when there's some downtime.

// First, let's get some info from the environment and the current URI to
// help us learn where we are and what we need to do.
$currentDiscipline = ($_SERVER['SCRIPT_NAME']);
$currentSlug = ltrim($_SERVER['QUERY_STRING'], "q=");

// Get a list of all the sections under our current discipline from the left nav menu object
$currentSections = $leftNav->nodes[$callPage];
$discipline = $_SERVER['SCRIPT_NAME'];

// Parse URIs to figure out which product categories or detail pages
// we need to show.
$detailView = preg_match('/^([^\/]+\/)([^\/]+\/)(\d{10}[\/]?|\d{13}[\/]?|details[\/]?)$/', 
						$currentSlug, $detailMatch);
$gradeView = preg_match('/^([^\/]+[\/]?)$/', $currentSlug, $gradeMatch);
$sectionView = preg_match('/^([^\/]+\/)([^\/]+[\/]?)$/', $currentSlug, 
						$sectionMatch);
$disciplineView = empty($_SERVER['QUERY_STRING']);

// For requests that don't validate.
function respondWith404()
{
	header("HTTP/1.1 404 Not Found");
	ob_flush();
	$content = FALSE;
	return $content;
}

// Once we start buffering, we run through a bunch of logic that more or less
// mixes the responsibilities of a URI router and a controller. Definately a good
// candidate for refactoring.
ob_start();

// ### Discipline views ###
// URI: `//math.php`
// Show the discipline index if there's no query string:
if ($disciplineView) 
{
	$page->pageTitle = $currentSections[0]['item'];
	include_once "views/discipline/{$callPage}-index.php";
	$content = ob_get_clean();
} 

// ### Grade-level views ###
// URI: `//reading-literature.php?q=reading-library-collections` [1]
// URI: `//reading-literature.php?q=9-12`				   [2]

// Show multi-grade-level views for specific product lines[1], OR
// show grade-level views for a single discipline[2].
elseif ($gradeView) 
{
	$contentFile = "views/{$currentSlug}.php";
	if (file_exists($contentFile)) {
		include_once($contentFile);
		$content = ob_get_clean();
	} else {
		include_once "localIncludes/oops.php";
		$content = ob_get_clean();
	}
}

// ### Product detail view ###
// URI: `//math.php?q=k-2/just-a-minute-math/9780739879405`
//
// Validation: 10 OR 13-digit number AND exists in `hw_products['isbn']`.
// Show a detail view for a single product.
elseif ($detailView) 
{

	$isbn = $detailMatch[3];
	$slug = $detailMatch[1].rtrim($detailMatch[2], "/");

	$getProduct = new ProductDetail($settings->db, $isbn, $slug);
	$theProduct = $getProduct->details();

	if ($theProduct) {
		// For a very small number of views, we want to do 
		// special-case stuff.
		switch ($detailMatch[2]) {
			case 'carmen-sandiego-on-wii/':
				$hideOptions = TRUE;
				$hidePurchaseButton = TRUE;
				$headerOverride = TRUE;
				break;
			case 'hmh-fuse/':
				$appStoreButton = TRUE;
			case 'math-on-the-spot/':
				$appStoreButton = TRUE;
				break;
			
			default:

				break;
		}
		$page->pageTitle = $theProduct->name;
		include_once "views/detailview.php";
		$content = ob_get_clean();
	} else {
		respondWith404();
	}
}
 // ### Section landing page ###
 // URI: `//science.php?q=3-6/core-skills-science`
 //
 // Validation: matches an entry in site-wide list of defined slugs.
 // Show a multi-product listing for a single grade range in a discipline.

elseif ($sectionView) 
{
	// If there are only two parts to the route, and
	// it wasn't caught above, display the listing
	// that matches the slug.
	if (in_array($sectionMatch[0], $leftNav->slugs)) {
		// More overrides for special-snowflake sections.
		switch ($sectionMatch[2]) {
			case 'free-help-at-home-for-math-expressions':
				$hidePriceLine = TRUE;
				$pdfLinks = TRUE;
				break;
			case 'free-help-at-home-for-go-math':
				$hidePriceLine = TRUE;
				$pdfLinks = TRUE;
				break;
			case 'carmen-sandiego-on-wii':
				$hidePriceLine = TRUE;
				break;
			case 'pair-it-turn-and-learn':
				$hideGradeLine = TRUE;
				$titleOverride = "Pair-It: Turn and Learn, Grades K–3";
				$queryOverride = new NameLookup("%pair-it%", 
												"slug", $settings->db);
				break;
			case 'timeline-graphic-novels':
				$titleOverride = "TIMELINE Graphic Novels, Interest Level Grades 6–12";
				$hideGradeLine = TRUE;
				break;
			case 'reading-for-middle-school':
				$titleOverride = "Reading For Middle School";
				break;
			default:
				break;
		}

		// Even more override handling for special cases.
		if ($queryOverride) {
			$products = $queryOverride->getDetails();
		} else {
			$productList = new ProductList($currentDiscipline, $currentSlug, 
			   						   	   $settings->db, $callPage);
			$products = $productList->display();
		}

		if ($titleOverride) { 
			$page->pageTitle = $titleOverride; 
		} else {
			$page->pageTitle = $products[0]['header'];
		}

		include_once "views/sectionview.php";
		$content = ob_get_clean();
	} else {
		respondWith404();
	}
}

// We've figured out context and collected data, let's render a response!
include_once __TEMPLATES__."/header.tpl.php";
echo '<div class="buffer">';
if ($content) {
	echo $content;
} else {
	include_once "localIncludes/oops.php";
}
echo '</div><hr/>';

// Any unique trademark lines we need to render?
if (strpos($page->pageTitle, "Advanced Placement*") !== FALSE) {
    $page->trademark = $_trademarks['advanced-placement'];
}
if (strpos($page->pageTitle, "AP*") !== FALSE) {
	$page->trademark = $_trademarks['advanced-placement'];
}
if (strpos($page->pageTitle, "HMH Fuse") !== FALSE) {
	$page->trademark = $_trademarks['hmh-fuse'];
}
include_once __TEMPLATES__."/footer.tpl.php";