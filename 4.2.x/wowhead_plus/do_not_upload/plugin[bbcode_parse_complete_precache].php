<?php

$text  = "[item][/item]\r\n";
$text .= "[item]<u>17182</u>[/item]\r\n";
$text .= "[item]<b>19019:Thunderfury</b>[/item]\r\n";
$text .= "[item]<u>souldrinker</u>[/item]\r\n";
$text .= "[item]http://mop.wowhead.com/item=87032[/item]";

// bbcode pattern matching
$item_bbcode_0 = '/\[item\](<[^>]+>)?%s(<[^>]+>)?\[\/item\]/i';
$item_bbcode_1 = '((\d+)?\:?((?:\w|\s|\,|\'|\(|\))+)?|(http\:\/\/(\w+)\.wowhead\.com\/item\=\d+))';
if (preg_match_all(sprintf($item_bbcode_0, $item_bbcode_1), $text, $items, PREG_SET_ORDER)) {
	// loop through each [item]
	foreach ($items AS $item) {
		// skip malformed [item] bbcode
		if (empty($item[2])) {
			$text = preg_replace(sprintf($item_bbcode_0, ''), '', $text);
			continue;
		}

		// select from the database cache
		$item_hash = md5(strtolower($item[2]));
		$cache = $this->registry->db->query_first(sprintf(
			'SELECT * FROM `'.TABLE_PREFIX.'wowhead_cache` WHERE hash=\'%s\'', $item_hash
		));
		
		// check for a cache hit
		if (is_array($cache) && !empty($cache)) {
			// parse bbcode into valid url
			$text = preg_replace(sprintf($item_bbcode_0, preg_quote($item[2], '/')), 
				sprintf(
					'<a href="http://' . (!empty($item[6]) ? $item[6] : 'www') . 
					'.wowhead.com/item=%d" class="q%d">$1%s$2</a>', 
					$cache['itemid'], $cache['quality'], $cache['name']
				), $text
			);
		} else {
			// request item information
			if (($xml = @file_get_contents(
				(!empty($item[5]) ? "{$item[5]}&xml" : 
				sprintf('http://%s.wowhead.com/item=%s&xml', 
				(!empty($item[6]) ? $item[6] : 'www'),
				urlencode(!empty($item[3])?$item[3]:$item[4]))), false, 
				stream_context_create(array('http' => array('header' => 'Host: ' . 
				(!empty($item[6]) ? $item[6] : 'www') . '.wowhead.com'))))) !== false && 
				strpos($http_response_header[0], '200') !== false
			) {
				// attempt to parse xml
				if (($xml = simplexml_load_string($xml)) !== false && !isset($xml->error)) {
					$data = array(
						'name'    => (string)$xml->item->name,
						'itemid'  => (int)$xml->item['id'],
						'quality' => (string)$xml->item->quality['id']
					);
					
					// parse bbcode into valid url
					$text = preg_replace(sprintf($item_bbcode_0, preg_quote($item[2], '/')), 
						sprintf(
							'<a href="http://' . (!empty($item[6]) ? $item[6] : 'www') . 
							'.wowhead.com/item=%d" class="q%d">$1%s$2</a>', 
							$data['itemid'], $data['quality'], $data['name']
						), $text
					);
					
					// insert into the database cache
					$this->registry->db->query_write(sprintf(
						'INSERT INTO `'.TABLE_PREFIX.'wowhead_cache` ' . 
						'(`hash`,`name`,`itemid`,`quality`,`created`) ' . 
						'VALUES (\'%s\',\'%s\',\'%d\',\'%d\',UNIX_TIMESTAMP())', 
						$item_hash, $this->registry->db->escape_string($data['name']), 
						$data['itemid'], $data['quality'])
					);
				}
			}
		}
	}
}

echo $text;

?>