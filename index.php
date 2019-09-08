<?php
//Dictionary file which should contain one word per line
$dictionary="words.txt";

//Read line of file and strips CR and LF
function getLineOfFile($l) {
    global $dictionary;
    $linecount = 0;
    $word="";
    $handle = fopen($dictionary, "r");
    while(!feof($handle)){
        $line = fgets($handle);
        $linecount++;
        if ($linecount == $l )
            $word=$line;
    }
    $word=str_replace("\r","",$word);
    $word=str_replace("\n","",$word);
    return $word;
}

//Random symbol used for words spacer
function pickSpacer() {
    $set = "^!#%-_+.1234567890";
    return $set[array_rand(str_split($set))];
}

//Counts how many lines are in file and picks one random
function pickWord(){
    global $dictionary;
    $linecount = 0;
    $handle = fopen($dictionary, "r");
    while(!feof($handle)){
        $line = fgets($handle);
        $linecount++;
    }
    fclose($handle);
    $l=random_int(1,$linecount);
    $pass=ucfirst(getLineOfFile($l));
    return $pass;
}

//Cook the password. Minimum total length and words count as parameters.
function passCook($len=12,$words=2) {
    $wordpass = "";
    $wordcount=0;
    while ((strlen($wordpass)<$len)or($wordcount<$words)) {
        if (strlen($wordpass)>0)
            $wordpass = $wordpass.pickSpacer();
        $wordpass = $wordpass.pickWord();
        $wordcount++;
    }
    return $wordpass;
}

//Bonus function to calculate APR1 for Apache authentication
function crypt_apr1_md5($plainpasswd) {
    $salt = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);
    $len = strlen($plainpasswd);
    $text = $plainpasswd.'$apr1$'.$salt;
    $bin = pack("H32", md5($plainpasswd.$salt.$plainpasswd));
    for($i = $len; $i > 0; $i -= 16) { $text .= substr($bin, 0, min(16, $i)); }
    for($i = $len; $i > 0; $i >>= 1) { $text .= ($i & 1) ? chr(0) : $plainpasswd{0}; }
    $bin = pack("H32", md5($text));
    for($i = 0; $i < 1000; $i++)
    {
        $new = ($i & 1) ? $plainpasswd : $bin;
        if ($i % 3) $new .= $salt;
        if ($i % 7) $new .= $plainpasswd;
        $new .= ($i & 1) ? $bin : $plainpasswd;
        $bin = pack("H32", md5($new));
    }
    $tmp="";
    for ($i = 0; $i < 5; $i++)
    {
        $k = $i + 6;
        $j = $i + 12;
        if ($j == 16) $j = 5;
        $tmp = $bin[$i].$bin[$k].$bin[$j].$tmp;
    }
    $tmp = chr(0).chr(0).$bin[11].$tmp;
    $tmp = strtr(strrev(substr(base64_encode($tmp), 2)),
    "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
    "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz");
 
    return "$"."apr1"."$".$salt."$".$tmp;
}

$pass = passCook();
$data["password"] = $pass;
$data["sha1"] = sha1($pass);
$data["htpasswd"] = crypt_apr1_md5($pass);

//Output the result in JSON format
//header('Content-Type: application/json');
echo json_encode($data);

?>
