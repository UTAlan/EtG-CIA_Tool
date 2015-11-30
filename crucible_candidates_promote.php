<?php
require_once("config.php");

$queries = array();

// If user refreshed, don't process again
if(!$debug && $_SESSION['crucible_candidates_promote_done']) {
	require_once("header.php");
	echo '<ul><li class="opa">Oops. You\'ve promoted too recently. Try again later. <a class="normal" href="index.php">Click here to return home</a></li></ul>';
	die();
}
$_SESSION['crucible_candidates_promote_done'] = true;

$cards_candidates = getCrucibleCandidates($_POST);
$topic_ids = $categories = array();
$add_html = array("M"=>"<br />[hr]<br /><br />", "C"=>"<br />[hr]<br /><br />", "S"=>"<br />[hr]<br /><br />", "O"=>"<br />[hr]<br /><br />");

$mat_choice_query = "SELECT MAX(id_choice) as id_choice FROM smf_poll_choices WHERE id_poll = 1641";
$mat_choice_result = mysql_query($mat_choice_query);
$mat_choice_arr = mysql_fetch_assoc($mat_choice_result);
$mat_choice = $mat_choice_arr["id_choice"] + 1;

$car_choice_query = "SELECT MAX(id_choice) as id_choice FROM smf_poll_choices WHERE id_poll = 1640";
$car_choice_result = mysql_query($car_choice_query);
$car_choice_arr = mysql_fetch_assoc($car_choice_result);
$car_choice = $car_choice_arr["id_choice"] + 1;

$spi_choice_query = "SELECT MAX(id_choice) as id_choice FROM smf_poll_choices WHERE id_poll = 1643";
$spi_choice_result = mysql_query($spi_choice_query);
$spi_choice_arr = mysql_fetch_assoc($spi_choice_result);
$spi_choice = $spi_choice_arr["id_choice"] + 1;

$oth_choice_query = "SELECT MAX(id_choice) as id_choice FROM smf_poll_choices WHERE id_poll = 1642";
$oth_choice_result = mysql_query($oth_choice_query);
$oth_choice_arr = mysql_fetch_assoc($oth_choice_result);
$oth_choice = $oth_choice_arr["id_choice"] + 1;

foreach($cards_candidates as $id_member=>$cards) {
	$remove_html = array();
	foreach($cards["cards"] as $card) {
		// Get info for moving threads
		if(!empty($card["topic_id"])) {
			$topic_ids[] = $card["topic_id"];
		} else {
			continue;
		}
		if(empty($category[$card["category"]])) {
			$category[$card["category"]] = array();
		}
		$category[$card["category"]][] = $card["name"];

		// Add to Polls
		switch($card["category"]) {
			case "M":
				$id_poll = 1641;
				$id_choice = $mat_choice;
				$mat_choice++;
				$add_html["M"] .= $card["full"] . " ";
				break;
			case "C":
				$id_poll = 1640;
				$id_choice = $car_choice;
				$car_choice++;
				$add_html["C"] .= $card["full"] . " ";
				break;
			case "S":
				$id_poll = 1643;
				$id_choice = $spi_choice;
				$spi_choice++;
				$add_html["S"] .= $card["full"] . " ";
				break;
			default:
				$id_poll = 1642;
				$id_choice = $oth_choice;
				$oth_choice++;
				$add_html["O"] .= $card["full"] . " ";
		}
		$poll_choice_query = "INSERT INTO smf_poll_choices SET id_poll = " . $id_poll . ", id_choice = " . $id_choice . ", label = '" . mysql_real_escape_string($card["name"]) . "', votes = 0";
		$queries[] = $poll_choice_query . "<br />" . $card["topic_id"];
		mysql_query($poll_choice_query);

		$remove_html[] = $card["full"];
	}

	// Remove from "Link Crucible Candidates" thread
	$get_body_query = "SELECT body FROM smf_messages WHERE id_msg = " . $cards["id_msg"];
	$get_body_result = mysql_query($get_body_query);
	$get_body = mysql_fetch_assoc($get_body_result);
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
		$update_body_query = "UPDATE smf_messages SET body = '" . $body . "' WHERE id_msg = " . $cards["id_msg"];
		$queries[] = $update_body_query;
		mysql_query($update_body_query);
	} else {
		// No more cards in post, send to recycle bin
		$trash_query = "INSERT INTO smf_topics SET id_board = 488, id_first_msg = " . $cards["id_msg"] . ", id_last_msg = " . $cards["id_msg"] . ", id_member_started = " . $id_member . ", id_member_updated = " . $id_member . ", id_previous_topic = " . $cards["topic_id"] . ", approved = 1";
		$queries[] = $trash_query;
		mysql_query($trash_query);
		$trash_id = mysql_insert_id();
		$move_query = "UPDATE smf_messages SET id_topic = " . $trash_id . ", id_board = 488, icon = 'recycled' WHERE id_msg = " . $cards["id_msg"];
		$queries[] = $move_query;
		mysql_query($move_query);
	}
}

// Move threads to Crucible Subforum
$promotion_query = "UPDATE smf_topics SET id_board = 129 WHERE id_topic IN (" . implode(',', $topic_ids) . ")";
$queries[] = $promotion_query;
mysql_query($promotion_query);

// Add to first post of thread
foreach($add_html as $category=>$html) {
	$topics = array("M" => 58707, "C" => 58708, "S" => 58709, "O" => 58734);
	$body = mysql_real_escape_string($html);
	$html_query = "UPDATE smf_messages m LEFT JOIN smf_topics t ON t.id_first_msg = m.id_msg SET body = CONCAT(body, '" . $body . "') WHERE t.id_topic = " . $topics[$category];
	$queries[] = $html_query;
	mysql_query($html_query);
}

require_once("header.php");
?>

		<ul>
			<li class="link">
				<a href="index.php">Promoted!<br /><br />Click to return home</a>
			</li>
		</ul>
		<!-- <pre><?php print_r($queries); //print_r($cards_candidates); ?></pre> -->
	</body>
</html>