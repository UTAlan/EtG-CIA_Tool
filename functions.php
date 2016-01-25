<?php 
function getCrucibleCandidates($post = array()) {
	global $db;
	$candidates_results = $db->query("SELECT * FROM smf_messages WHERE id_topic = '16154' ORDER BY poster_time ASC");
	$cards_candidates = array();
	$skip = 0;
	while($row = $candidates_results->fetch_assoc()) {
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

						// Get card info from thread
						$dom = getHtml($c["href"]);
						$c["topic_id"] = getTopicId($dom);
						$c["name"] = getUnuppedCardName($dom);
						
						$cards_candidates[$id_member]["cards"][$index++] = $c;
					}
				}
			}
		}
	}

	return $cards_candidates;
}

function getForgeCandidates($id_topic, $post = array()) {
	global $db;
	$forge_candidates = array("promote_num"=>-1, "cards"=>array());
	$promote_num_arr = array('33'=>'6', '26'=>'5', '18'=>'4', '13'=>'3', '7'=>'2', '3'=>'1', '0'=>'0'); 

	// Get results of poll
	$poll_choices_result = $db->query("SELECT pc.* FROM smf_poll_choices pc LEFT JOIN smf_polls p ON p.id_poll = pc.id_poll WHERE p.id_topic = '$id_topic' ORDER BY votes DESC");
	$poll_choices_num = $poll_choices_result->num_rows;
	foreach($promote_num_arr as $k=>$v) {
		if($poll_choices_num >= $k) {
			$forge_candidates["promote_num"] = $v;
			break;
		}
	}

	// Save # of votes for each card
	while($row = $poll_choices_result->fetch_assoc()) {
		$forge_candidates["cards"][ucwords($row["label"])] = array("votes"=>$row["votes"]);
	}

	// Get content of first post
	$poll_thread_results = $db->query("SELECT * FROM smf_messages WHERE id_topic = '$id_topic' ORDER BY poster_time ASC");
	$poll_thread_arr = $poll_thread_results->fetch_assoc();
	$body_arr = explode("Click on a card image to go to discussion page.", $poll_thread_arr["body"]);
	$body = $body_arr[count($body_arr)-1];
	$body = str_replace("<br />", "", $body);
	$body = str_replace("[hr]", "", $body);
	$cards_arr = explode("[url=", $body);

	// Loop through each card candidate
	foreach($cards_arr as $text) {
		// Get HTML of linked page
		if(($end_pos = strpos($text, '[/img]')) !== false) {
			$url_pos = strpos($text, '[img');
			$img_pos = strpos($text, ']http');
			
			$c = array();
			$text_end = strpos($text, "[/url]");
			$c["full"] = "[url=" . substr($text, 0, $text_end + 6);
			$c["href"] = substr($text, 0, $url_pos-1); // Link to card
			$c["img"] = substr($text, $img_pos+1, $end_pos-$img_pos-1); // Link to image

			// Get card info from thread
			$dom = getHtml($c["href"]);
			$c["topic_id"] = getTopicId($dom);
			$c["name"] = getUnuppedCardName($dom);

			$card_name = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $c["name"]));
			if(!empty($post) && !empty($post["category_" . $card_name])) {
				$c["category"] = $post["category_" . $card_name]; // Category (on promote page)
			}
			
			if(!empty($c["name"])) {
				$c["votes"] = $forge_candidates["cards"][$c["name"]]["votes"];
				$forge_candidates["cards"][$c["name"]] = $c;
			}
		}
	}

	// Return array
	return $forge_candidates;
}

function getHtml($url) {
	$url = str_replace("&#039;", "'", $url);
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$html = curl_exec($ch);
	curl_close($ch);
	$dom = new DOMDocument();
	@$dom->loadHTML($html);
	return $dom;
}

function getTopicId($dom) {
	$topic_id = 0;
	foreach($dom->getElementsByTagName('input') as $input) {
		if($input->getAttribute("name") == "topic") {
			$topic_id = $input->getAttribute("value");
			break;
		}
	}
	return $topic_id;
}

function getUnuppedCardName($dom) {
	$card_name = '';
	foreach($dom->getElementsByTagName("div") as $div) {
		if($div->getAttribute("class") == "navigate_section") {
			foreach($div->getElementsByTagName('li') as $li) {  
				if($li->getAttribute("class") == "last") {
					for($i = 0; $i < $li->childNodes->length; $i++) {
						if($li->childNodes->item($i)->tagName == "a") {
							$textContent = $li->childNodes->item($i)->textContent;
							if(strpos($textContent, " | ") !== false) {
								$name = explode(" | ", $textContent);
							} else if(strpos($textContent, " l ") !== false) {
								$name = explode(" l ", $textContent);
							} else {
								$name = array($textContent);
							}
							$card_name = $name[0];
						}
					}
					break;
				}
			}
			break;
		}
	}
	return ucwords($card_name);
}

function checkAccess($access_groups = array()) {
	global $db;
	$admins = array(1, 2, 76);

	if (isset($_COOKIE['SMFCookie811'])) {
		$arr = stripslashes(urldecode($_COOKIE['SMFCookie811']));
		$arr = unserialize($arr);
		$user_id = (int)$arr[0];
		$passwrd = $arr[1];
		$user_result = $db->query("SELECT id_group, passwd, password_salt, additional_groups FROM smf_members WHERE id_member = $user_id");
		if ($user_result and $user_result->num_rows > 0) {
			$user = $user_result->fetch_assoc();
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

function debug_echo($str) {
	echo '<pre>' . print_r($str, true) . '</pre>';
}