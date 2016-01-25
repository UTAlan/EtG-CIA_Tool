<?php
require_once("config.php");

// If user refreshed, don't process again
if(!$debug && $_SESSION['crucible_candidates_promote_done']) {
	require_once("header.php");
	echo '<ul><li class="opa">Oops. You\'ve promoted too recently. Try again later. <a class="normal" href="index.php">Click here to return home</a></li></ul>';
	die();
}
$_SESSION['crucible_candidates_promote_done'] = true;

define("MAT_POLL_ID", 1641);
define("CAR_POLL_ID", 1640);
define("SPI_POLL_ID", 1643);
define("OTH_POLL_ID", 1642);

$crucible_candidates = getCrucibleCandidates($_POST);
$topic_ids_arr = array(); //$category = array();
$add_html = array("M"=>"<br />[hr]<br /><br />", "C"=>"<br />[hr]<br /><br />", "S"=>"<br />[hr]<br /><br />", "O"=>"<br />[hr]<br /><br />");

$mat_choice_result = $db->query("SELECT MAX(id_choice) as id_choice FROM smf_poll_choices WHERE id_poll = " . MAT_POLL_ID);
$mat_choice_arr = $mat_choice_result->fetch_assoc();
$mat_choice = $mat_choice_arr["id_choice"] + 1;

$car_choice_result = $db->query("SELECT MAX(id_choice) as id_choice FROM smf_poll_choices WHERE id_poll = " . CAR_POLL_ID);
$car_choice_arr = $car_choice_result->fetch_assoc();
$car_choice = $car_choice_arr["id_choice"] + 1;

$spi_choice_result = $db->query("SELECT MAX(id_choice) as id_choice FROM smf_poll_choices WHERE id_poll = " . SPI_POLL_ID);
$spi_choice_arr = $spi_choice_result->fetch_assoc();
$spi_choice = $spi_choice_arr["id_choice"] + 1;

$oth_choice_result = $db->query("SELECT MAX(id_choice) as id_choice FROM smf_poll_choices WHERE id_poll = " . OTH_POLL_ID);
$oth_choice_arr = $oth_choice_result->fetch_assoc();
$oth_choice = $oth_choice_arr["id_choice"] + 1;

foreach($crucible_candidates as $id_member=>$cards) {
	$remove_html = array();
	foreach($cards["cards"] as $card) {
		// Get info for moving threads
		if(!empty($card["topic_id"])) {
			$topic_ids_arr[] = $card["topic_id"];
		} else {
			continue;
		}
		//if(empty($category[$card["category"]])) {
		//	$category[$card["category"]] = array();
		//}
		//$category[$card["category"]][] = $card["name"];

		// Add to Polls
		$id_poll = 0;
		switch($card["category"]) {
			case "M":
				$id_poll = MAT_POLL_ID;
				$id_choice = $mat_choice;
				$mat_choice++;
				$add_html["M"] .= $card["full"] . " ";
				break;
			case "C":
				$id_poll = CAR_POLL_ID;
				$id_choice = $car_choice;
				$car_choice++;
				$add_html["C"] .= $card["full"] . " ";
				break;
			case "S":
				$id_poll = SPI_POLL_ID;
				$id_choice = $spi_choice;
				$spi_choice++;
				$add_html["S"] .= $card["full"] . " ";
				break;
			case "O":
				$id_poll = OTH_POLL_ID;
				$id_choice = $oth_choice;
				$oth_choice++;
				$add_html["O"] .= $card["full"] . " ";
				break;
			default:
		}
		if($id_poll) {
			$db->query("INSERT INTO smf_poll_choices SET id_poll = " . $id_poll . ", id_choice = " . $id_choice . ", label = '" . mysql_real_escape_string($card["name"]) . "', votes = 0");
			$remove_html[] = $card["full"];
		}
	}

	// Remove from "Link Crucible Candidates" thread
	$get_body_result = $db->query("SELECT body FROM smf_messages WHERE id_msg = {$cards['id_msg']}");
	$get_body = $get_body_result->fetch_assoc();
	$body = $get_body["body"];
	foreach($remove_html as $html) {
		$body = str_replace($html, "", $body);
	}
	$body = trim($body);
	if(strpos($body, "[url=") !== false) {
		while(strpos($body, "<br />") === 0) {
			$body = substr($body, 6);
		}
		while(strpos($body, "[hr]") === 0) {
			$body = substr($body, 4);
		}
		while(strpos($body, "<br />") === 0) {
			$body = substr($body, 6);
		}
		$body = mysql_real_escape_string($body);
		$db->query("UPDATE smf_messages SET body = '$body' WHERE id_msg = {$cards['id_msg']}");
	} else {
		// No more cards in post, send to recycle bin
		$db->query("INSERT INTO smf_topics SET id_board = 488, id_first_msg = {$cards['id_msg']}, id_last_msg = {$cards['id_msg']}, id_member_started = $id_member, id_member_updated = $id_member, id_previous_topic = {$cards['topic_id']}, approved = 1");
		$trash_id = mysql_insert_id();
		$db->query("UPDATE smf_messages SET id_topic = $trash_id, id_board = 488, icon = 'recycled' WHERE id_msg = {$cards['id_msg']}");
	}
}

// Move threads to Crucible Subforum
$topics_ids = implode(',', $topic_ids_arr);
$db->query("UPDATE smf_topics SET id_board = 129 WHERE id_topic IN ($topics_ids)");

// Add to first post of thread
$topics = array("M" => 58707, "C" => 58708, "S" => 58709, "O" => 58734);
foreach($add_html as $category=>$html) {
	$body = mysql_real_escape_string($html);
	$db->query("UPDATE smf_messages m LEFT JOIN smf_topics t ON t.id_first_msg = m.id_msg SET body = CONCAT(body, '$body') WHERE t.id_topic = {$topics[$category]}");
}

require_once("header.php");
?>

		<ul>
			<li class="link">
				<a href="index.php">Promoted!<br /><br />Click to return home</a>
			</li>
		</ul>
	</body>
</html>