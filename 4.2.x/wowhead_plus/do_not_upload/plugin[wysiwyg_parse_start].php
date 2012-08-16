<?php

$parsed  = '<a href="http://www.wowhead.com/item=17182" class="q5"><u>Sulfuras, Hand of Ragnaros</u></a>';
$parsed .= '<a href="http://www.wowhead.com/item=19019" class="q5"><b>Thunderfury, Blessed Blade of the Windseeker</b></a>';
$parsed .= '<a href="http://www.wowhead.com/item=78479" class="q4"><u>Souldrinker</u></a>';
$parsed .= '<a href="http://mop.wowhead.com/item=87032" class="q4"><u>Gara\'kal, Fist of the Spiritbinder<u></a>';

$item_urlcode_0 = '/\<a href\="(http\:\/\/(\w+)\.wowhead\.com\/item\=%s)" class\="q\d"\>(<[^>]+>)?%s(<[^>]+>)?\<\/a\>/i';
$item_urlcode_1 = '((?:\w|\s|\,|\'|\(|\))+)';
if (preg_match_all(sprintf($item_urlcode_0, '(\d+)', $item_urlcode_1), $parsed, $items, PREG_SET_ORDER)) {
	// loop through each [item]
	foreach ($items AS $item) {
		// mop.wowhead.com
		if ($item[2] == 'mop') {
			$parsed = preg_replace(sprintf($item_urlcode_0, "({$item[3]})", $item_urlcode_1), '[item]$4$1$6[/item]', $parsed);
		} else {
			$parsed = preg_replace(sprintf($item_urlcode_0, "({$item[3]})", $item_urlcode_1), '[item]$4$3:$5$6[/item]', $parsed);
		}
	}
}

echo $parsed;

$vbulletin->db->query_write('
CREATE TABLE IF NOT EXISTS `'.TABLE_PREFIX.'wowhead_cache` (
  `hash` char(32) NOT NULL,
  `name` varchar(50) NOT NULL,
  `itemid` mediumint(6) unsigned NOT NULL,
  `quality` tinyint(1) unsigned NOT NULL,
  `created` int(10) unsigned NOT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
');

$vbulletin->db->query_write('DROP TABLE `'.TABLE_PREFIX.'wowhead_cache`');

?>