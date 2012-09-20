<?php
// Our left navigation menu wasn't displaying everything it should've been, in certain
// situations. Took a look at the code that generates it and found this:

// Old Code
// ========
	private function _leftNavBuild() {
		$i = (int) 0;	// offset default items
		$j = (int) 0;	// offset default items
		$k = (int) 0;	// offset default items
		// count the items in the array and add the $i offset
		$groupCount = (int) (count($this->leftNavItem["$this->groupItems"]) + $i);
		// return the offset $i to zero for /build
		$resetCount = (int) ($groupCount - $groupCount);
		// get the page name and extension
		$fileName = (string) substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], '/') + 1);
		$ext = (string) substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"],'.') + 1);
		// get extension length
		$extInt = (int) strlen($ext) + 1;
		// return page name minus extension
		$fileNameNX = (string) substr($fileName, 0, -$extInt);
		// make an array to figure out which are headlines or links
		$navArray = array();
		
		for($i; $i < $groupCount; $i++) {
			// count the items in the array
			if($this->leftNavItem["$this->groupItems"][$i]["item"]) {
				$itemCount++;
				if($this->leftNavItem["$this->groupItems"][$i]["slug"] != NULL) {
					$navArray["navItem"][$i] = (int) 1; //(string) "slug";
				} else {
					$navArray["navItem"][$i] = (int) 0; //(string) "header";
				}
			}
		}
		$this->leftNavBuild = (string) "\r\n\t\t\t\t\t\t<nav id=\"section-nav\">";
		for($j; $j < $groupCount; $j++) {
			$next = $navArray["navItem"][($j+1)];
			$prev = $navArray["navItem"][($j-1)];
			if($j == $resetCount && $navArray["navItem"][$j] == 0) {
				$leftNavHeaderStr = (string) "\r\n\t\t\t\t\t\t\t<h2>%s</h2>";
				$leftNavHeaderCompiled = sprintf($leftNavHeaderStr, $this->leftNavItem["$this->groupItems"][$j]["item"]);
				$this->leftNavBuild .= (string) $leftNavHeaderCompiled;			
			}
			if($j != $resetCount && $navArray["navItem"][$j] == 0) {
					$leftNavHeaderStr = (string) "\r\n\t\t\t\t\t\t\t<h3>%s</h3>";
					$leftNavHeaderCompiled = sprintf($leftNavHeaderStr, $this->leftNavItem["$this->groupItems"][$j]["item"]);
					$this->leftNavBuild .= (string) $leftNavHeaderCompiled;
			}
			if($j != $resetCount && $navArray["navItem"][$j] == 1) {
				if($prev == 0 && $next == 1) {
					$this->leftNavBuild .= (string) "\r\n\t\t\t\t\t\t\t<ul class=\"tabs\">";
					if($this->leftNavItem["$this->groupItems"][$j]["slug"] == $fileName) {
						$leftNavItemStr = "\r\n\t\t\t\t\t\t\t\t<li><a class=\"active\" href=\"%s\">%s</a></li>";	
					} else {
						$leftNavItemStr = "\r\n\t\t\t\t\t\t\t\t<li><a href=\"%s\">%s</a></li>";
					}
					$leftNavItemCompiled = sprintf($leftNavItemStr, $this->leftNavItem["$this->groupItems"][$j]["slug"], $this->leftNavItem["$this->groupItems"][$j]["item"]);
					$this->leftNavBuild .= (string) $leftNavItemCompiled;
				} else if($prev == 1 && $next == 1) {
					if($this->leftNavItem["$this->groupItems"][$j]["slug"] == $fileName) {
						$leftNavItemStr = "\r\n\t\t\t\t\t\t\t\t<li><a class=\"active\" href=\"%s\">%s</a></li>";	
					} else {
						$leftNavItemStr = "\r\n\t\t\t\t\t\t\t\t<li><a href=\"%s\">%s</a></li>";
					}		
					$leftNavItemCompiled = sprintf($leftNavItemStr, $this->leftNavItem["$this->groupItems"][$j]["slug"], $this->leftNavItem["$this->groupItems"][$j]["item"]);
					$this->leftNavBuild .= (string) $leftNavItemCompiled;
				} else if($prev == 1 && $next == 0) {
					if($this->leftNavItem["$this->groupItems"][$j]["slug"] == $fileName) {
						$leftNavItemStr = "\r\n\t\t\t\t\t\t\t\t<li><a class=\"active\" href=\"%s\">%s</a></li>";	
					} else {
						$leftNavItemStr = "\r\n\t\t\t\t\t\t\t\t<li><a href=\"%s\">%s</a></li>";
					}
					$leftNavItemCompiled = sprintf($leftNavItemStr, $this->leftNavItem["$this->groupItems"][$j]["slug"], $this->leftNavItem["$this->groupItems"][$j]["item"]);
					$this->leftNavBuild .= (string) $leftNavItemCompiled;
					$this->leftNavBuild .= (string) "\r\n\t\t\t\t\t\t\t</ul>";
				}
			}
		}
		$this->leftNavBuild .= (string) "\r\n\t\t\t\t\t\t</nav>";
		return $this->leftNavBuild;
	}	

// Refactored
// ==========

private function _itemLink($text, $href) {
		if ($href == pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME)) {
			return "<a href=\"$href\" class=\"active\">$text</a>";
		}
		return "<a href=\"$href\">$text</a>";
	}

	private function _leftNavBuild() {
		$navGroup = $this->leftNavItem["$this->groupItems"];
		ob_start();
		echo "<nav id=\"section-nav\">\n";
		$i = 0;
		foreach ($navGroup as $arr) {

			$text = $arr['item'];
			$href = $arr['slug'];

			// First item is the category name
			if ($i == 0) {
				echo "<h2>$text</h2>\n";
			}

			// If href is blank and we're on item 2, it's a subhead
			elseif ($href == "" && $i == 1) {
				echo "<h3>$text</h3>\n<ul class=\"tabs\">\n";
			} 

			// If href isn't blank and we're on item 2, start the nav list
			elseif ($href != "" && $i == 1) {
				echo "<ul class=\"tabs\"><li>".$this->_itemLink($text, $href)."</li>\n";
			}

			// End the latest list and spit out a subhead
			elseif ($href == "" && $i > 1) {
				echo "</ul>\n<h3>$text</h3>\n<ul class=\"tabs\">\n";
			} 

			// Last nav item
			elseif ($i == count($navGroup)-1) {
				echo "<li>".$this->_itemLink($text, $href)."</li>\n</ul>\n";
			} 

			// All other items
			else {
				echo "<li>".$this->_itemLink($text, $href)."</li>\n";
			}

			$i++;

		}
		echo "</nav>";
		$nav = ob_get_clean();
		return $nav;
	}
