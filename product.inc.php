<?php
// Various product lookups for the homeworks site.

// Retrieve a list of products from the db based on a discipline and a slug.
// Used to generate subject landing pages like the one found [here](http://www.hmheducation.com/homeworks/science.php?q=9-12/chemistry)
class ProductList
{
	// Put this in at some point to help debug one of PHP's not-at-all annoying
	// silent crashes. Orthogonal to the actual functionality of the class.
	public function __call($name, $args) {
		echo "Missing method called : $name, ".implode(', ', $args);
	}

	private $db;

	function __construct($currentDiscipline, $currentSlug, $db, $callPage)
	{
		$this->currentDiscipline = $currentDiscipline;
		$this->currentSlug = $currentSlug;
		$this->db = $db;
		$this->callPage = $callPage;
	}

	public function display()
	{
		$db = $this->db;
		$stmt = $db->prepare("SELECT * FROM hw_products 
							  WHERE slug = '{$this->currentSlug}' 
							  AND discipline = '{$this->callPage}'");
		$stmt->execute();
		$result = $stmt->fetchAll();
		return $result;
	}
}

// Retrieve the info we need to generate a product detail view.
class ProductDetail
{
	public function __call($name, $args) {
		echo "Missing method called : $name, ".implode(', ', $args);
	}

	private $db;
	private $isbn;

	function __construct($db, $isbn, $slug)
	{
		$this->db = $db;
		$this->isbn = $isbn;
		$this->slug = $slug;
	}

	public function details() {
		$db = $this->db;
		if ($this->isbn == "details") {
			$stmt = $db->prepare("SELECT * FROM hw_products 
								  WHERE isbn == '{$this->isbn}'
								  AND slug == '{$this->slug}'");
		} else {
			$stmt = $db->prepare("SELECT * FROM hw_products WHERE isbn == '{$this->isbn}'");
		}
		$stmt->execute();
		$result = $stmt->fetchObject();
		// Add path and ext to product image
		foreach (array(".jpg", ".png", ".gif") as $ext) {
			// Check from docroot
			$filename = __MY_LOCAL_IMAGES__.$result->image.$ext;
			if (file_exists($filename)) {
				// Prepend http hostname
				$result->image = __MY_IMAGES__.$result->image.$ext;
				break;
			}
		}
		return $result;
	}
}


class FeaturedProducts
{

	private $db;

	function __construct($db)
	{
		$this->db = $db;
	}

	public function getDetails() {
		$db = $this->db;
		$rowNumber = 1;
		$stmt = $db->prepare('SELECT * FROM hw_featured WHERE display = 1');
		$stmt->execute();
		$result = $stmt->fetchAll();
		return $result;
	}
}

// Retreive a product based on its ISBN.
class ISBNLookup
{

	function __construct($isbns, $db)
	{
		$this->db = $db;
		$this->isbns = $isbns;
	}

	public function getDetails() {
		$db = $this->db;
		foreach ($this->isbns as $isbn) {
			$stmt = $db->prepare("SELECT * FROM hw_products WHERE isbn = {$isbn}");
			$stmt->execute();
			$result[$isbn] = $stmt->fetch(PDO::FETCH_ASSOC);
		}

		return $result;
	}
}

// Retreive products by name, optionally limiting the results.
class NameLookup 
{

	function __construct($searchString, $column, $db, $limit)
	{
		$this->db = $db;
		$this->col = $column;
		$this->str = $searchString;
		if (is_int($limit)) {
			$this->limit = $limit;
		} else {
			$this->limit = FALSE;
		}
	}

	public function getDetails() {
		$db = $this->db;
		if ($this->limit) {
			$stmt = $db->prepare('SELECT * FROM hw_products 
							      WHERE "'.$this->col.'" LIKE "'.$this->str.'" LIMIT '.$this->limit);

			$stmt->execute();
		} else {
			$stmt = $db->prepare('SELECT * FROM hw_products 
							      WHERE "'.$this->col.'" LIKE "'.$this->str.'"');
			$stmt->execute();
		}
		$result = $stmt->fetchAll();
		return $result;
	}
}
?>