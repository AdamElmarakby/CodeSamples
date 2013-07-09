<?php

/*
This is a script I wrote to read an html book list from a url and insert the info into an MySQL database.  The booklist was updated regularly, so this file was meant to run as a cron job.  The code is very specific to the layout that the booklist is presented on.
*/

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

//The url of the list to be scraped
$ExtractedFromList = "http://us1.campaign-archive2.com/home/?u=ed09bb8384b4b40c482babf93&id=6ec63da636";

//Connect to MySQL
$con = mysql_connect('localhost', 'book_storage', '********');
mysql_select_db('book_storage', $con);

//Current time and date for table storage purposes
$time = time();
$date = date("Y-m-d");

//A function for cURL requests
function scrapeurl($URL, $header = false) {
	$cURL = curl_init();
	curl_setopt($cURL, CURLOPT_URL, trim($URL));
	curl_setopt($cURL, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1");
	curl_setopt($cURL, CURLOPT_RETURNTRANSFER, TRUE);
	if ($header) { curl_setopt($cURL, CURLOPT_HEADER, TRUE); }
	$output = curl_exec($cURL);
	return $output;
}

//A function to cut down Book Listing HTML
function trimbookinfo($html) {
	return str_replace("View this genre on eReaderIQ.com", "", str_replace("&nbsp;", "", ereg_replace("alt=\"(.*)\"", "", ereg_replace("style=\"(.*)\" h", " h", strip_tags($html, "<img><a>")))));
}

//Get the list
$listHTML = scrapeurl($ExtractedFromList);

//Cut out the list
$break = explode("<div class=\"display_archive\">", $listHTML);
$break2 = explode("</div>", $break[1]);
$list = $break2[0];
unset($break);
unset($break2);

//Cut out each entry and put in an array
$entryURLS = array();
$entries = explode("<li class=\"campaign\">", $list);
foreach ($entries as $entry) {
	$break = explode("href=\"", $entry);
	$break2 = explode("\"", $break[1]);
	$url = 	$break2[0];
	array_push($entryURLS, $url);
}

//Get each entry's list URL and put in array
$booksLists = array();
foreach ($entryURLS as $entryURL) {
	$headers = scrapeurl($entryURL, true);
	$break = explode("Location:", $headers);
	$break2 = explode("\n", $break[1]);
	$url = 	$break2[0];
	array_push($booksLists, $url);
}


//Scrape books and put them in MySQL if new
foreach ($booksLists as $booksList) {
	$catcontents = array();
	$data = scrapeurl($booksList);
	$break = explode('<div style="color: #000000;font-family: Trebuchet MS;font-size: 16px;line-height: 100%;text-align: left;padding-top: 30px; padding-bottom: 7px;"><b>', $data);
	unset($break[0]);
	array_pop($break);
	foreach ($break as $cat) {
		$breakcat = explode("</b>", $cat);
		$stripped = ereg_replace("[^A-Za-z0-9\& ]", "", html_entity_decode(strip_tags($breakcat[0])));
		unset($breakcat[0]);
		foreach ($breakcat as $book) {
			$catcontents[$stripped] .= trimbookinfo($book);
		}
	}
	array_pop($catcontents);
	foreach ($catcontents as $cattitle => $books) {
		$imagebreak = explode("<img src=\"http://e", $books);
		unset($imagebreak[0]);
		foreach ($imagebreak as $individual) {
			$imageurlbreak = explode("\"  h", $individual);
			$imageURL = "http://e".str_replace("  h", "", $imageurlbreak[0]);
			$itemurlbreak = explode("ref=\"", $imageurlbreak[1]);
			$itemurlbreak2 = explode("\">", $itemurlbreak[1]);
			$itemURL = $itemurlbreak2[0];
			$ASIN = substr($itemURL, 33, 10);
			if (count($itemurlbreak) > 2) { $concat = $itemurlbreak2[1].$itemurlbreak2[2]; $bybreak = explode("By:",$concat); }
			else { $bybreak = explode("By:",$itemurlbreak2[1]); }
			$title = strip_tags($bybreak[0]);
			$bybreak2 = explode("<", $bybreak[1]);
			if (strstr($bybreak2[0], "Available")) { $bybreak3 = explode("Available", $bybreak2[0]); $by = $bybreak3[0]; }
			else { $by = $bybreak2[0]; }
			$ratingsbreak = explode("src=\"", $bybreak2[1]);
			$ratingsbreak2 = explode("\" > (", $ratingsbreak[1]);
			$ratingurl = $ratingsbreak2[0];
			$ratingsbreak3 = explode(")Available", $ratingsbreak2[1]);
			$rating = $ratingsbreak3[0];
			if (!mysql_num_rows(mysql_query("SELECT ASIN FROM listings WHERE ASIN = '$ASIN'"))) { mysql_query("INSERT INTO `listings` ( `ASIN`, `EnteredOn`, `Status`, `Title`, `ItemURL`, `ImageURL`, `By`, `RatingImageLink`, `NumberOfRatings`, `Category`, `KindlePrice`, `DateLastChecked`, `ExtractedFromList`) VALUES ( '" . $ASIN . "', '". $time . "', 'active', '" . mysql_real_escape_string($title) . "', '" . $itemURL . "', '" . $imageURL . "', '" . mysql_real_escape_string($by) . "', '" . $ratingurl . "', '" . $rating . "', '" . $cattitle . "', '0.00', '" . $date . "', '" . $booksList . "')") or die(mysql_error()); }
		}
	}
}


?>