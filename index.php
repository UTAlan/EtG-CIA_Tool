<?php
require_once("config.php");

// Get polls
$crucible_results = $db->query("SELECT p.id_topic, m.subject, p.expire_time FROM smf_polls p LEFT JOIN smf_topics t ON t.id_topic = p.id_topic LEFT JOIN smf_messages m ON m.id_msg = t.id_first_msg WHERE p.id_topic IN ('58707', '58708', '58709', '58734')");
$polls_crucible = [];
while($row = $crucible_results->fetch_assoc()) {
	$polls_crucible[$row['id_topic']] = $row;
}

$result_forge = $db->query("SELECT p.id_topic, m.subject, p.expire_time FROM smf_polls p LEFT JOIN smf_topics t ON t.id_topic = p.id_topic LEFT JOIN smf_messages m ON m.id_msg = t.id_first_msg WHERE p.id_topic IN ('6846', '6847', '6848')");
$polls_forge = [];
while($row = $result_forge->fetch_assoc()) {
	$polls_forge[] = $row;
}

require_once("header.php");
?>

		<script>
		function promote(type, id) {
			if(id != '') {
				id = '?id_topic=' + id;
			}
			window.location = type + '_candidates.php' + id;
		}
		function soon() {
			alert("Coming Soon!");
		}
		</script>
		<ul>
			<li class="opa">
				<h2>Crucible Candidates</h2>
				<br />
				<input type="button" value="Promote to Crucible" onclick="promote('crucible', '');" />
			</li>
			<li class="opa">
				<h2>Crucible</h2>
				<br />
				<?php 
				foreach($polls_crucible as $poll) {
					echo $poll['subject'];
					echo '<br /><br />';
					echo '<span class="text">';
					
					if(time() < $poll['expire_time']) {
						$now = new DateTime();
						$expire_time = new DateTime();
						$expire_time->setTimestamp($poll['expire_time']);
						$interval = $now->diff($expire_time);
						echo 'Expires in ' . $interval->format('%a days, %h hours, %i minutes, %s seconds.');
					} else {
						echo '<input type="button" value="Promote/Archive Crucible Cards" onclick="promote(\'forge\', ' . $poll["id_topic"] . ');" />';
					}
					echo '</span><br /><br />';
				}
				?>
			</li>
			<!--
			<li class="opa">
				<h2>Forge</h2>
				<br />
				<?php 
				foreach($polls_forge as $poll) {
					echo $poll['subject'];
					echo '<br /><br />';
					echo '<span class="text">';
					
					if(time() < $poll['expire_time']) {
						$now = new DateTime();
						$expire_time = new DateTime();
						$expire_time->setTimestamp($poll['expire_time']);
						$interval = $now->diff($expire_time);
						echo 'Expires in ' . $interval->format('%a days, %h hours, %i minutes, %s seconds.');
					} else {
						echo '<input type="button" value="Promote/Archive Forge Cards" onclick="soon();" />';
					}
					echo '</span><br /><br />';
				}
				?>
			</li>
			<li class="opa">
				<h2>False Gods</h2>
				<br />
				<input type="button" value="Process False God Poll" onclick="soon();" />
			</li>
			-->
		</ul>
	</body>
</html>