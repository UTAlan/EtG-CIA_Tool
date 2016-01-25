<?php
require_once("config.php");

if(empty($_GET['id_topic'])) {
	header("Location: index.php");
	die();
}

// If user refreshed, don't process again
if(!$debug && $_SESSION['forge_candidates_promote_done']) {
	require_once("header.php");
	echo '<ul><li class="opa">Oops. You\'ve promoted too recently. Try again later. <a class="normal" href="index.php">Click here to return home</a></li></ul>';
	die();
}
$_SESSION['forge_candidates_promote_done'] = true;

// Reusable arrays
$poll_ids = array("crea"=>1644, "perm"=>1646, "spell"=>1645);
$poll_ids_old = array(58707=>1641, 58708=>1640, 58709=>1643, 58734=>1642);
$topic_ids_arr = $remove_html = $poll_choice_ids = array();
$add_html = array("crea"=>"", "perm"=>"", "spell"=>"");

// Get candidates
$forge_candidates = getCandidates(false, $_GET['id_topic'], $_POST);

// Get next id for each poll choice
$crea_choice_result = $db->query("SELECT MAX(id_choice) as id_choice FROM smf_poll_choices WHERE id_poll = " . $poll_ids["crea"]);
$crea_choice_arr = $crea_choice_result->fetch_assoc();
$poll_choice_ids["crea"] = $crea_choice_arr["id_choice"] + 1;

$perm_choice_result = $db->query("SELECT MAX(id_choice) as id_choice FROM smf_poll_choices WHERE id_poll = " . $poll_ids["perm"]);
$perm_choice_arr = $perm_choice_result->fetch_assoc();
$poll_choice_ids["perm"] = $perm_choice_arr["id_choice"] + 1;

$spell_choice_result = $db->query("SELECT MAX(id_choice) as id_choice FROM smf_poll_choices WHERE id_poll = " . $poll_ids["spell"]);
$spell_choice_arr = $spell_choice_result->fetch_assoc();
$poll_choice_ids["spell"] = $spell_choice_arr["id_choice"] + 1;

// Loop through candidates
foreach($forge_candidates["cards"] as $card) {
	if(empty($card['category'])) {
		continue;
	}

	// Get info for moving threads
	if(!empty($card["topic_id"])) {
		$topic_ids_arr[] = $card["topic_id"];
	} else {
		continue;
	}

	// Add to & Remove from Polls
	$id_poll = $poll_ids[$card["category"]];
	$id_choice = $poll_choice_ids[$card["category"]];
	$poll_choice_ids[$card["category"]]++;
	$add_html[$card["category"]] .= $card["full"] . " ";

	$db->query("INSERT INTO smf_poll_choices SET id_poll = " . $id_poll . ", id_choice = " . $id_choice . ", label = '" . mysql_real_escape_string($card["name"]) . " *', votes = 0");
	$db->query("DELETE FROM smf_poll_choices WHERE id_poll = " . $poll_ids_old[$_GET['id_topic']] . " AND label = '" . mysql_real_escape_string($card["name"]) . "'");

	$remove_html[] = $card["full"];
}

// Remove from first post of Crucible thread
$get_body_result = $db->query("SELECT id_msg, body FROM smf_messages WHERE id_topic = {$_GET['id_topic']} ORDER BY poster_time ASC");
$get_body = $get_body_result->fetch_assoc();
$body = $get_body["body"];
foreach($remove_html as $html) {
	$body = str_replace($html, "", $body);
}
$body = trim($body);
$body = mysql_real_escape_string($body);
$db->query("UPDATE smf_messages SET body = '$body' WHERE id_msg = {$get_body['id_msg']}");

// Move threads to Forge Subforum
$topics_ids = implode(',', $topic_ids_arr);
$db->query("UPDATE smf_topics SET id_board = 130 WHERE id_topic IN ($topics_ids)");

// Add to first post of Forge thread
$promoted_html = '';
foreach($add_html as $category=>$html) {
	if(!empty($html)) {
		$topics = array("crea" => 6846, "perm" => 6847, "spell" => 6848);
		$promoted_html .= $html;
		$html = '<br /><br />[hr]<br /><br />' . $html;
		$body = mysql_real_escape_string($html);
		$db->query("UPDATE smf_messages m LEFT JOIN smf_topics t ON t.id_first_msg = m.id_msg SET body = CONCAT(body, '$body') WHERE t.id_topic = {$topics[$category]}");
	}
}
if(!empty($promoted_html)) {
	$promoted_html = "The following card(s) will be moved to the [url=http://elementscommunity.org/forum/level-2-forge/][b]Forge[/b][/url]!\n\n" . $promoted_html . "\n\nCongratulations!\n\nThe following card(s) received the least amount of votes and so will be placed in the [url=http://elementscommunity.org/forum/crucible-archive/][b]Archive[/b][/url] for posteriority.\n[spoiler=Archived Cards][/spoiler]";
}

require_once("header.php");
?>

		<ul>
			<li class="opa">
				<h2>Success!</h2>
				<?php if(!empty($promoted_html)) { ?>
				<p>
					Promoted BBCode:<br />
					<textarea readonly="readonly" onclick="this.focus(); this.select()" rows="10" cols="100"><?php echo $promoted_html; ?></textarea>
				</p>
				<?php } ?>
			</li>
			<li class="link">
				<a href="index.php">Click to return home</a>
			</li>
		</ul>
	</body>
</html>