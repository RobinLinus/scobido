<?php
define("MAX_IMG_CAND", 30);
define("ARTICLE_BONUS", 15);
define("PARAG_BONUS", 5);
define("FIGURE_BONUS", 7);
define("LINK_BONUS", 2);
define("MAX_FILE_SIZE", 2000000);
define("MIN_FILE_SIZE", 5800);
define("MIN_IMG_FILE_SIZE", 25000);
define("MIN_MIN_FILE_SIZE", 1500);
define("RANK_IMG_MIN", 2000);
define("MAX_URL_SIZE", 256);

define("MAX_TITLE_SIZE", 100);
define("DEFAULT_TITLE", "No Title.");

function _sortByRank($a, $b) {
    if ($a['1'] == $b['1']) return 0;
    return $a['1'] < $b['1'] ? 1 : -1;
}

function sortByRank(&$arr){	
	usort($arr, "_sortByRank");
}


?>