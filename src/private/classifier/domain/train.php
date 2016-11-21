<?php
include '../../config.php';
include_once ('../../libs/vendor/autoload.php'); // won't include it again in the following examples
include 'domain-tokenizer.php';
 
use NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;
use NlpTools\Models\FeatureBasedNB;
use NlpTools\Documents\TrainingSet;
use NlpTools\Documents\TokensDocument;
use NlpTools\FeatureFactories\DataAsFeatures;
use NlpTools\Classifiers\MultinomialNBClassifier;

function examples_array($category){
    $file = fopen("examples/examples-$category.txt", "r");
    $examples = [];
    $i = 800;
    while(!feof($file) && $i >0){
        $i --;
        $line = fgets($file);
        $line = myTokenizer($line);
        $example = array($category[0],$line);
        array_push($examples,$example);
    }
    fclose($file);
    return $examples;
}

$good = examples_array("good");
$bad = examples_array("bad");


$all = array_merge($bad,$good);
shuffle($all);
shuffle($all);
shuffle($all);
shuffle($all);

$count = count($all);
$splitIndex = intval($count * 0.5);


// ---------- Data ----------------
// data is taken from http://archive.ics.uci.edu/ml/datasets/SMS+Spam+Collection
// we use a part for training
$training = array_slice($all, 0 , $splitIndex);
$testing = array_slice($all, $splitIndex);

$all = null;

echo "good: ".count($good).' bad: '.count($bad)." split index: $splitIndex<br>";
echo "training: ".count($training).' testing: '.count($testing)."<br>";

// var_dump($training);

$tset = new TrainingSet(); // will hold the training documents
$tok = new WhitespaceAndPunctuationTokenizer(); // will split into tokens
$ff = new DataAsFeatures(); // see features in documentation
 
// ---------- Training ----------------
foreach ($training as $d)
{
    $tset->addDocument(
        $d[0], // class
        new TokensDocument(
            $tok->tokenize($d[1]) // The actual document
        )
    );
}
 
$model = new FeatureBasedNB(); // train a Naive Bayes model
$model->train($ff,$tset);

// file_put_contents("models/ctx",base64_encode(serialize($ctx)));
$modelData = serialize($model);
file_put_contents("models/model",$modelData);

$ctx = serialize($ff);
file_put_contents("models/ctx",$ctx);

echo "<br>ModelSize: ".(strlen($modelData)/1000)."kb";

// ---------- Classification ----------------
$cls = new MultinomialNBClassifier($ff,$model);
$correct = 0;
$countGood = 0;
$countBad = 0;
$correctGood = 0;
$correctBad = 0;
foreach ($testing as $d)
{
    // predict if it is spam or ham
    $prediction = $cls->classify(
        array('g','b'), // all possible classes
        new TokensDocument(
            $tok->tokenize($d[1]) // The document
        )
    );
    $score = $prediction[1];
    $prediction = $prediction[0];
    if ($prediction==$d[0])
        $correct ++;

    if ($d[0]=='g'){
        $countGood ++;
        if ($prediction==$d[0])
            $correctGood ++;
    }

    if ($d[0]=='b'){
        $countBad ++;
        if ($prediction==$d[0])
            $correctBad ++;
    }
}
 

echo "<br><br>Results:<br>";
printf("Accuracy: %.2f\n", 100*$correct / count($testing));
printf("Accuracy Good: %.2f\n", 100*$correctGood / $countGood);
printf("Accuracy Bad: %.2f\n", 100*$correctBad / $countBad);
echo "<br><br>";


function testDomain($domain){
    global $cls;
    global $tok;
    $bad = exp($cls->getScore('b',new TokensDocument($tok->tokenize(myTokenizer($domain)))));
    $good = exp($cls->getScore('g',new TokensDocument($tok->tokenize(myTokenizer($domain)))));

    $bad = $bad/($bad+$good);

    printf("<br>Spam Probability: %.0f $domain", 100*$bad);
}


testDomain("de.xhamster.com");
testDomain("sunporno.com");
testDomain("x-hamster.com");
testDomain("assfuck.com");
testDomain("xhamster.com");
testDomain("ujizz.com");
testDomain("whore.com");
testDomain("asshole.com");
testDomain("youporn.com");
testDomain("zfuck.com");
testDomain("fuck.com");
testDomain("assfetish.com");
testDomain("bitch.com");
testDomain("teens.com");
testDomain("fuckteens.com");
testDomain("sex.ch");
testDomain("pornpics.com");
echo "<br><br>";
testDomain("facebook.com");
testDomain("google.com");
testDomain("google.de");
testDomain("github.com");
testDomain("youtube.com");
testDomain("youtu.be");
testDomain("inquisitr.com");
testDomain("robinlinus.com");
testDomain("robinlinus.github.io");
testDomain("uberlego.github.io");
testDomain("capira.com");
testDomain("capira.de");
testDomain("harmlos.de");
testDomain("spiegel.de");
testDomain("hallo.de");
testDomain("amazon.de");
testDomain("smithsonianmag.com");
testDomain("karpathy.github.io");
testDomain("blueprintjs.com");
testDomain("stackoverflow.com");
testDomain("esa.int");
testDomain("ethz.ch");
testDomain("ruthlessray.wordpress.com");
testDomain("imgur.com");
testDomain("i.imgur.com");

?>