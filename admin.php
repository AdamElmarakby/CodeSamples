<?php
/*
This is a script from the bitcoin project I mentioned to you on the phone.  It's an admin page where two things happen.  Projects that have reached their time limit will either be funded or refunded to the pledgers (think KickStarter).  Also, members with an account on the site can withdraw to their personal bitcoin accounts.  The bitcoin server is interacted with via RPC.
*/
error_reporting(0);
require('database.php');
require('inc/jsonRPCClient.php');
$time = time();
if($_GET["id"] != "") {
	mysql_query("UPDATE `projects` SET `goaldate`='0' WHERE `id`='" . $_GET["id"] . "'");
	$pledges = mysql_query("SELECT * FROM `Pledges` WHERE `projectid`='" . $proj->id . "'");
	while ($pledge = mysql_fetch_object($pledges)) {
			$username = mysql_fetch_assoc(mysql_query("SELECT `username`,`depositaddress` FROM `members` WHERE `id`='" . $pledge->pledgerid . "'"));
			mysql_query("UPDATE `members` SET `btc`=(btc + " . $pledge->amount .") WHERE `id`='" . $pledge->pledgerid . "'");
	}
	header("Location: admin.php"); exit();
}
if($_GET["w"] != "") {
	mysql_query("UPDATE `Withdrawals` SET `sent`='1' WHERE `id`='" . $_GET["w"] . "'");
	header("Location: admin.php"); exit();
}

echo "<style type='text/css'>
* { font-size:16px;font-family:Arial }
</style>";

if ($_GET["id"]=="" && $_GET["w"]=="") {

//The only bit of bitcoin code
$bitcoin = new jsonRPCClient(RPC_CONNECTION_STRING);
$bal = $bitcoin->getbalance("Bitcoinstarter");
echo "<b>Bitcoinstarter Server Balance:</b> $bal BtC<br />
<hr /><br />";

//Get all projects that aren't finished
$getproj = mysql_query("SELECT `id`,`btc`,`goaldate`,`userid`,`goal`,`name` FROM `projects` WHERE `goaldate`<>'0'");
while ($proj = mysql_fetch_object($getproj)) {
	//If the goaldate has passed and enough BtC have been received
	if ($proj->goaldate < $time && $proj->btc >= $proj->goal) {
		$receiving = mysql_result(mysql_query("SELECT `bitreceive` FROM `members` WHERE `id`='" . $proj->userid . "'"), 0);
		echo '<b>REACHED GOAL:</b> <a href="project.php?id='.$proj->id.'">'.$proj->name.'</a> completed on: '. date("Y-m-d", $proj->goaldate) .'<br />
		Goal: '.$proj->goal.' | Received: '.$proj->btc.'<br />
		Amount to Send: <span style="color:#276114;font-weight:bold">'. ($proj->btc * 0.9).'</span> to <span style="color:#f00">'.$receiving.'</span><br />
		<a href="admin.php?id='.$proj->id.'">Mark as Complete</a><br /><br />
		';
	}
	//If the goaldate has passed and not enough BtC have been recieved
	if ($proj->goaldate < $time && $proj->btc < $proj->goal) {
		//Get all pledges
		echo '<b>DID NOT REACH GOAL:</b> <a href="project.php?id='.$proj->id.'">'.$proj->name.'</a> ended on: '. date("Y-m-d", $proj->goaldate) .'<br />';
		$pledges = mysql_query("SELECT * FROM `Pledges` WHERE `projectid`='" . $proj->id . "'");
		echo" Pledges needing refunds: <br />";
		while ($pledge = mysql_fetch_object($pledges)) {
			$username = mysql_fetch_assoc(mysql_query("SELECT `username`,`depositaddress` FROM `members` WHERE `id`='" . $pledge->pledgerid . "'"));
			echo 'Send <span style="color:#276114;font-weight:bold">'.$pledge->amount.'</span> to '.$username["username"].' (<span style="color:#f00">'.$username["depositaddress"].'</span>)<br />
			';
		}
		echo '<a href="admin.php?id='.$proj->id.'">Mark as Complete</a><br /><br />
		';
	}
}
echo "<b>Approved Withdrawals</b><br />";
	$withdrawals = mysql_query("SELECT * FROM `withdrawals` WHERE `sent`='0'");
	while ($w = mysql_fetch_object($withdrawals)) {
		$btcuser = mysql_fetch_assoc(mysql_query("SELECT * FROM members WHERE id='" . $w->user . "'"));
		if ($w->amount <= $btcuser["btc"]) {
			echo "Send <span style='color:#276114;font-weight:bold'>".$w->amount."</span> to ".$btcuser["username"]." (<span style='color:#f00'>".$btcuser["bitreceive"]."</span>) <a href='admin.php?w=".$w->id."'>Mark as Completed</a><br />
			";
		}	
	}

}

?>