<?
$start = microtime(true);
include_once ('/private/libs/vendor/autoload.php'); // won't include it again in the following examples
use NlpTools\Classifiers\MultinomialNBClassifier;
use NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;
use NlpTools\FeatureFactories\DataAsFeatures;
use NlpTools\Documents\TokensDocument;

include 'domain-tokenizer.php';
include '/private/config.php';



// $model = apc_fetch('model');
// if(empty($model)){
//     $model = unserialize(file_get_contents("models/model"));
//     apc_add('model', $model);
// }


function spamProbability($url){
	$domain = parse_url($url, PHP_URL_HOST);
	$domain=str_replace('www.', '', $domain);

		$rootDir =  $_SERVER['DOCUMENT_ROOT'];
	$model = unserialize(file_get_contents($rootDir."/private/classifier/domain/models/model"));
    $ff = new DataAsFeatures(); 
    $cls = new MultinomialNBClassifier($ff,$model);
    $tok = new WhitespaceAndPunctuationTokenizer();
    $bad = exp($cls->getScore('b',new TokensDocument($tok->tokenize(myTokenizer($domain)))));
    $good = exp($cls->getScore('g',new TokensDocument($tok->tokenize(myTokenizer($domain)))));

    return $bad/($bad+$good);
}

if(DEBUG_ME && isset($_GET['url'])){
	$domain = $_GET['url'];
	printf("Spam Probability: %.0f $domain", spamProbability($domain)*100); 
	echo '<br>Time: '.((microtime(true) - $start)*1000).'ms';
}
?>