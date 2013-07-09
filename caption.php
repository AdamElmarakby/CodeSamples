<?php
/*
This is a page I coded to add a "meme like" caption to pictures on a site with PHP.  It stores the edit in a MySQL database.  I added this code to a precoded content management system so the include files are not written by me.
*/
require "include/config.php";
require "include/functions/import.php";
require "include/functions/ImageTools.class.php";
$UID = intval(cleanit($_SESSION['USERID']));
$query = "INSERT INTO `edited_posts` (`PID`, `USERID`, `ext`, `time_added`, `date_added`, `views`, `rating`, `filename`) VALUES ('', '" . $_SESSION['USERID'] . "', '" . $_POST['ext'] . "', '" . time() . "', '" . date("Y-m-d") . "', '0', '', '" . ereg_replace("[^0-9]", "", $_POST["id"]) . "')";
$result = $conn->execute($query);
function imagettfstroketext(&$image, $size, $angle, $x, $y, &$textcolor, &$strokecolor, $fontfile, $text, $px) {
 //This function taken from http://www.johnciacia.com/2010/01/04/using-php-and-gd-to-add-border-to-text/
    for($c1 = ($x-abs($px)); $c1 <= ($x+abs($px)); $c1++)
        for($c2 = ($y-abs($px)); $c2 <= ($y+abs($px)); $c2++)
            $bg = imagettftext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);
 
   return imagettftext($image, $size, $angle, $x, $y, $textcolor, $fontfile, $text);
}
switch ($_POST["ext"]) {
	case ".jpg":
	$img = imagecreatefromjpeg("pics/" . $_POST["id"] . '-o.jpg');
	$output = 'imagejpeg($img, "pics/' . $id .'-o.jpg");';
	$fext = '.jpg';
	break;
	case ".bmp":
	$img = imagecreatefromwbmp("pics/" . $_POST["id"] . '-o.bmp');
	$output = 'imagewbmp($img, "pics/' . $id .'-o.bmp");';
	$fext = '.bmp';
	break;
	case ".gif":
	$img = imagecreatefromgif("pics/" . $_POST["id"] . '-o.gif');
	$output = 'imagegif($img, "pics/' . $id .'-o.gif");';
	$fext = '.gif';
	break;
	case ".png":
	$img = imagecreatefrompng("pics/" . $_POST["id"] . '-o.png');
	$output = 'imagejpeg($img, "pics/' . $id .'-o.png");';
	$fext = '.png';
	break;

}
$white = imagecolorallocate($img, 255, 255, 255);
$black = imagecolorallocate($img, 0, 0, 0);
$impact = "fonts/impact.ttf";
if ($_POST["fontsize"] < 48) {
	$px = 2;
}
elseif ($_POST["fontsize"] >= 48 && $_POST["fontsize"] < 72) {
	$px = 3;
}
elseif ($_POST["fontsize"] >= 72 && $_POST["fontsize"] < 250) {
	$px = 4;
}
elseif ($_POST["fontsize"] >= 250) {
	$px = 5;
}
imagettfstroketext($img, $_POST["fontsize"] -$px -$px -$px - $px, 0, $_POST["x"] + $px, $_POST["y"] + $_POST["fontsize"] + $px, $white, $black, $impact, stripslashes($_POST["caption"]), $px);
header("Location:http://www.sillytext.com/photo?id=" . ereg_replace("[^0-9]", $POST["id"]));

?>
