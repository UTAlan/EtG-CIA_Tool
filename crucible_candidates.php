<?php
require_once("config.php");

// Get Candidates Messages
$cards_candidates = getCrucibleCandidates();

// Make sure next page isn't refreshed
unset($_SESSION['crucible_candidates_promote_done']);

require_once("header.php");
?>

		<script>
		function promote() {
			var done = false;
			$(".category").each(function(){
				if(!done && $(this).val() == '') {
					alert("Please select a category for all cards.");
					done = true;
				}
			});
			if(!done && confirm('Are you sure you want to promote these cards? This cannot be undone.')) {
				$("#promote_form").submit();
			}
		}
		</script>

		<form name="promote_form" id="promote_form" action="crucible_candidates_promote.php" method="post">
		<ul>
			<li class="opa" style="text-align:center;">
				<input type="button" value="Promote" style="font-size:2em" onclick="promote();" />
				<br /><br />
				<span class="text">This will move the card threads into the Crucible, add them to the poll, and remove the links and images from the Link Crucible Candidates thread.</span>
			</li>
			<?php 
			foreach($cards_candidates as $id_member=>$cards) {
				echo '<li class="opa">';
				$user_result = $db->query("SELECT member_name FROM smf_members WHERE id_member = $id_member");
				$user = $user_result->fetch_assoc();
				echo '<a class="normal" href="' . $cards["member"] . '">' . $user['member_name'] . '</a><br /><br />';
				echo '<table><tr>';
				foreach($cards["cards"] as $id=>$card) {
					echo "<td><a class='normal' href='" . $card["href"] . "'><img width='150px' src='" . $card["img"] . "' /></a><br /><br />";
					echo "<select name='card_" . $id_member . "_" . $id . "' class='category'>";
					echo "<option value='' selected='selected'>SELECT CATEGORY</option>";
					echo "<option value='M'>Material (Ea/Ai/Wa/Fi)</option>";
					echo "<option value='C'>Cardinal (En/Gr/Ae/Ti)</option>";
					echo "<option value='S'>Spiritual (Lf/De/Lt/Da)</option>";
					echo "<option value='O'>Other &amp; Non-Elemental</option>";
					echo "<option value='X'>DON'T PROMOTE</option>";
					echo "</select></td> ";
				}
				echo '</tr></table>';
				echo '<br />';
				echo '</li>';
			}
			?>
		</ul>
		</form>
	</body>
</html>