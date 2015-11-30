<?php 
function getCrucibleCandidates($post = array()) {
	$query_candidates = "SELECT * FROM smf_messages WHERE id_topic = '16154' ORDER BY poster_time ASC";
	$result_candidates = mysql_query($query_candidates);
	$cards_candidates = array();
	$skip = 0;
	while($row = mysql_fetch_assoc($result_candidates)) {
		if($skip < 2) {
			$skip++;
		} else {
			$id_member = $row['id_member'];
			$cards_candidates[$id_member] = array();
			$cards_candidates[$id_member]["member"] = "../../forum/index.php?msg=".$row['id_msg'];
			$cards_candidates[$id_member]["id_msg"] = $row["id_msg"];
			$cards_candidates[$id_member]["cards"] = array();
			$cards_arr = explode('[url=', $row['body']);
			$index = 0;
			foreach($cards_arr as $text) {
				if(($end_pos = strpos($text, '[/img]')) !== false) {
					$url_pos = strpos($text, '[img');
					$img_pos = strpos($text, ']http');
					if($index <= 3) { // Only get first 4 cards per person
						$c = array();
						$text_end = strpos($text, "[/url]");
						$c["full"] = "[url=" . substr($text, 0, $text_end + 6);
						$c["href"] = substr($text, 0, $url_pos-1); // Link to card
						$c["img"] = substr($text, $img_pos+1, $end_pos-$img_pos-1); // Link to image
						if(!empty($post) && !empty($post["card_" . $id_member . "_" . $index])) {
							$c["category"] = $post["card_" . $id_member . "_" . $index]; // Category (on promote page)
						}

						// Get html of card thread
						$dom = getHtml($c["href"]);
						
						// Find topic id
						foreach($dom->getElementsByTagName('input') as $input) {
							if($input->getAttribute("name") == "topic") {
								$c["topic_id"] = $input->getAttribute("value");
								break;
							}
						}
						// Find un-upped card name
						foreach($dom->getElementsByTagName("div") as $div) {
							if($div->getAttribute("class") == "navigate_section") {
								foreach($div->getElementsByTagName('li') as $li) {  
									if($li->getAttribute("class") == "last") {
										for($i = 0; $i < $li->childNodes->length; $i++) {
											if($li->childNodes->item($i)->tagName == "a") {
												$name = explode(" | ", $li->childNodes->item($i)->textContent);
												$c["name"] = $name[0];
											}
										}
										break;
									}
								}
								break;
							}
						}

						$cards_candidates[$id_member]["cards"][$index++] = $c;
					}
				}
			}
		}
	}

	return $cards_candidates;
}

function getHtml($url) {
	$url = str_replace("&#039;", "'", $url);
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$html = curl_exec($ch);
	curl_close($ch);
	$dom = new DOMDocument();
	@$dom->loadHTML($html);
	return $dom;
}

function checkAccess($access_groups = array()) {
	$admins = array(1, 2, 76);

	if (isset($_COOKIE['SMFCookie811'])) {
		$arr = stripslashes(urldecode($_COOKIE['SMFCookie811']));
		$arr = unserialize($arr);
		$userid = (int)$arr[0];
		$passwrd = $arr[1];
		$query = "SELECT id_group,passwd,password_salt,additional_groups FROM smf_members WHERE id_member=$userid";
		$user = mysql_query($query);
		if ($user and mysql_num_rows($user) > 0) {
			$user = mysql_fetch_array($user);
			$smfpass = sha1($user['passwd'].$user['password_salt']);
			if ($smfpass == $passwrd) {
				$user_groups = explode(',', $user['additional_groups']);
				array_push($user_groups, $user['id_group']);
				foreach($admins as $admin_group_id) {
					if(in_array($admin_group_id, $user_groups)) {
						return 2;
					}
				}
				foreach($access_groups as $access_group_id) {
					if(in_array($access_group_id, $user_groups)) {
						return 1;
					}
				}
			}
		}
	}
	
	return 0;
}