<?php

function myTokenizer($string){
    $raw = $string;
    $raws = array_reverse(explode(".",$string));
    $raws = array_splice($raws, 1);
    $res = '';
    foreach ($raws as $raw) {
        $res .= $raw.' ';

        // $ngram6= Ngrams($raw,6);
        // foreach ($ngram6 as $ngram) {
        //     $res .= ' '.$ngram;
        // }
        
        // $ngram5= Ngrams($raw,5);
        // foreach ($ngram5 as $ngram) {
        //     $res .= ' '.$ngram;
        // }

        $ngram4= Ngrams($raw,4);
        foreach ($ngram4 as $ngram) {
            $res .= ' '.$ngram;
        }

        $ngram3 = Ngrams($raw,3);
        foreach ($ngram3 as $ngram) {
            $res .= ' '.$ngram;
        }
        // $res .= ' '.$raw[0].$raw[0];
        $res .= ' : ';
    }

    return $res;
}



function Ngrams($word,$n=3){
    $len=strlen($word);
    $ngram=array();
    for($i=0;$i+$n<=$len;$i++){
        $string="";
        for($j=0;$j<$n;$j++){ 
            $string.=$word[$j+$i]; 
        }
        $ngram[$i]=$string;
    }
        return $ngram;
}

?>