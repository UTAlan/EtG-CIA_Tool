<?php
require_once("config.php");

if(empty($_GET['id_topic'])) {
	header("Location: index.php");
	die();
}

// If user refreshed, don't process again
if(!$debug && $_SESSION['armory_candidates_promote_done']) {
	require_once("header.php");
	echo '<ul><li class="opa">Oops. You\'ve promoted too recently. Try again later. <a class="normal" href="index.php">Click here to return home</a></li></ul>';
	die();
}
$_SESSION['armory_candidates_promote_done'] = true;

// Reusable arrays
$poll_ids_old = array(6846=>1644, 6847=>1646, 6848=>1645);
$topic_ids_arr = $remove_html = array();
$add_html = '';

// Get candidates
$armory_candidates = getCandidates(false, $_GET['id_topic'], $_POST);

// Loop through candidates
foreach($armory_candidates["cards"] as $card) {
	$card_name = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $card["name"]));
	if(empty($_POST['action_' . $card_name])) {
		continue;
	}

	// Get info for moving threads
	if(!empty($card["topic_id"])) {
		$topic_ids_arr[] = $card["topic_id"];
	} else {
		continue;
	}

	// Remove from Polls
	$add_html .= $card["full"] . " ";
	$db->query("DELETE FROM smf_poll_choices WHERE id_poll = " . $poll_ids_old[$_GET['id_topic']] . " AND label = '" . mysql_real_escape_string($card["name"]) . "'");

	$remove_html[] = $card["full"];
}

// Remove from first post of Forge thread
$get_body_result = $db->query("SELECT id_msg, body FROM smf_messages WHERE id_topic = {$_GET['id_topic']} ORDER BY poster_time ASC");
$get_body = $get_body_result->fetch_assoc();
$body = $get_body["body"];
foreach($remove_html as $html) {
	$body = str_replace($html, "", $body);
}
$body = trim($body);
$body = mysql_real_escape_string($body);
$db->query("UPDATE smf_messages SET body = '$body' WHERE id_msg = {$get_body['id_msg']}");

// Move threads to Armory Subforum
$topics_ids = implode(',', $topic_ids_arr);
$db->query("UPDATE smf_topics SET id_board = 595 WHERE id_topic IN ($topics_ids)");

// Add to first post of Armory thread
$promoted_html = '';
if(!empty($add_html)) {
	$topics = array("crea" => 6850, "perm" => 25825, "spell" => 25824);
	$promoted_html .= $add_html;
	$add_html = '<br /><br />[hr]<br /><br />' . $add_html;
	$body = mysql_real_escape_string($add_html);
	$db->query("UPDATE smf_messages m LEFT JOIN smf_topics t ON t.id_first_msg = m.id_msg SET body = CONCAT(body, '$body') WHERE t.id_topic = {$topics[$category]}");
}

if(!empty($promoted_html)) {
	$promoted_html = "The following card(s) will be moved to the [url=http://elementscommunity.org/forum/level-3-armory/][b]Armory[/b][/url]!\n\n" . $promoted_html . "\n\nCongratulations!\n\nThe following card(s) received the least amount of votes and so will be placed in the [url=http://elementscommunity.org/forum/forge-archive/][b]Archive[/b][/url] for posteriority.\n[spoiler=Archived Cards][/spoiler]";
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