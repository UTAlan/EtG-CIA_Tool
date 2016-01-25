<?php
require_once("config.php");

if(empty($_GET['id_topic'])) {
	header("Location: index.php");
	die();
}

// Get Candidates Messages
$forge_candidates = getCandidates(false, $_GET['id_topic']);
$num_forge_candidates = count($forge_candidates["cards"]);

// Make sure next page isn't refreshed
unset($_SESSION['forge_candidates_promote_done']);

require_once("header.php");
?>

		<script>
		$(function() {
			$(".action").change(function() {
				var name = $(this).attr("name").substring(7);
				if($(this).val() != "promote") {
					$("#category_" + name).hide();
					$("#category_" + name).removeClass("category");
				} else {
					$("#category_" + name).show();
					$("#category_" + name).addClass("category");
				}
			});
		});

		function promote() {
			var done = false;
			$(".category").each(function(){
				if(!done && $(this).val() == '') {
					alert("Please select a category for all cards being promoted.");
					done = true;
				}
			});
			if(!done && confirm('Are you sure you want to promote these cards? This cannot be undone.')) {
				$("#promote_form").submit();
			}
		}
		</script>

		<form name="promote_form" id="promote_form" action="forge_candidates_promote.php?id_topic=<?php echo $_GET['id_topic']; ?>" method="post">
		<ul>
			<li class="opa" style="text-align:center;">
				<input type="button" value="Promote" style="font-size:2em" onclick="promote();" />
				<br /><br />
				<span class="text">
					This will move the specified card threads into the Forge and add them to the poll.<br /><br />
					<table class="text">
						<tr>
							<td>Cards in Poll</td>
							<td<?php if($num_forge_candidates <= 2) { echo ' class="highlight"'; } ?>>0-2</td>
							<td<?php if($num_forge_candidates >= 3 && $num_forge_candidates <= 6) { echo ' class="highlight"'; } ?>>3-6</td>
							<td<?php if($num_forge_candidates >= 7 && $num_forge_candidates <= 12) { echo ' class="highlight"'; } ?>>7-12</td>
							<td<?php if($num_forge_candidates >= 13 && $num_forge_candidates <= 17) { echo ' class="highlight"'; } ?>>13-17</td>
							<td<?php if($num_forge_candidates >= 18 && $num_forge_candidates <= 25) { echo ' class="highlight"'; } ?>>18-25</td>
							<td<?php if($num_forge_candidates >= 26 && $num_forge_candidates <= 32) { echo ' class="highlight"'; } ?>>26-32</td>
							<td<?php if($num_forge_candidates >= 33) { echo ' class="highlight"'; } ?>>33+</td>
						</tr>
						<tr>
							<td>Cards Promoted</td>
							<td<?php if($num_forge_candidates <= 2) { echo ' class="highlight"'; } ?>>0</td>
							<td<?php if($num_forge_candidates >= 3 && $num_forge_candidates <= 6) { echo ' class="highlight"'; } ?>>1</td>
							<td<?php if($num_forge_candidates >= 7 && $num_forge_candidates <= 12) { echo ' class="highlight"'; } ?>>2</td>
							<td<?php if($num_forge_candidates >= 13 && $num_forge_candidates <= 17) { echo ' class="highlight"'; } ?>>3</td>
							<td<?php if($num_forge_candidates >= 18 && $num_forge_candidates <= 25) { echo ' class="highlight"'; } ?>>4</td>
							<td<?php if($num_forge_candidates >= 26 && $num_forge_candidates <= 32) { echo ' class="highlight"'; } ?>>5</td>
							<td<?php if($num_forge_candidates >= 33) { echo ' class="highlight"'; } ?>>6</td>
						</tr><!--
						<tr>
							<td>Cards Retired</td>
							<td<?php if($num_forge_candidates <= 2) { echo ' class="highlight"'; } ?>>0</td>
							<td<?php if($num_forge_candidates >= 3 && $num_forge_candidates <= 6) { echo ' class="highlight"'; } ?>>0</td>
							<td<?php if($num_forge_candidates >= 7 && $num_forge_candidates <= 12) { echo ' class="highlight"'; } ?>>0</td>
							<td<?php if($num_forge_candidates >= 13 && $num_forge_candidates <= 17) { echo ' class="highlight"'; } ?>>0</td>
							<td<?php if($num_forge_candidates >= 18 && $num_forge_candidates <= 25) { echo ' class="highlight"'; } ?>>0</td>
							<td<?php if($num_forge_candidates >= 26 && $num_forge_candidates <= 32) { echo ' class="highlight"'; } ?>>0</td>
							<td<?php if($num_forge_candidates >= 33) { echo ' class="highlight"'; } ?>>1/3 (11+)</td>
						</tr>-->
					</table>
				</span>
			</li>
			<?php 
			$last_votes = -1;
			$index = $column = $promoted = $retired = 0;
			//debug_echo($forge_candidates);
			foreach($forge_candidates["cards"] as $card_full_name=>$card) {
				$card_name = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $card_full_name));
				if($card["votes"] != $last_votes) {
					$last_votes = $card["votes"];
					if($index > 0) {
						echo '</tr></table></li>';
					}
					echo '<li class="opa">' . $card["votes"] . ' Vote' . ($card["votes"] != 1 ? 's' : '') . '<br /><br />';
					$column = 0;
				}

				if($column % 4 == 0) {
					if($column != 0) {
						echo '</tr><tr>';
					} else {
						echo "<table><tr>";
					}
				}

				echo "<td><a class='normal' href='" . $card["href"] . "'><img width='150px' src='" . $card["img"] . "' alt='" . $card_full_name . "' /></a><br /><br />";
				echo "<select name='action_" . $card_name . "' id='action_" . $card_name . "' class='action'>";
				echo "<option value=''>Do Nothing</option>";
				echo "<option value='promote'";
				if($promoted < $forge_candidates["promote_num"]) {
					echo " selected='selected'";
				}
				echo ">Promote</option>";/*
				echo "<option value='retire'";
				if($retired++ < $forge_candidates["retire_num"]) {
					echo " selected='selected'";
				}
				echo ">Retire</option>";*/
				echo "</select><br />";
				echo "<select name='category_" . $card_name . "' id='category_" . $card_name . "' class='" . ($promoted < $forge_candidates["promote_num"] ? "category" : "' style='display:none;") . "'>";
				echo "<option value=''>-Select Category-</option>";
				echo "<option value='crea'>Creature</option>";
				echo "<option value='perm'>Permanent</option>";
				echo "<option value='spell'>Spell</option>";
				echo "</select>";
				echo "</td>";

				$promoted++;
				$index++;
				$column++;
			}
			echo '</tr></table>';
			?>
		</ul>
		</form>
	</body>
</html>