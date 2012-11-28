<?php

//
// requires lib/riak-client.php
// get it from https://github.com/basho/riak-php-client
//

define('VERSION', '0.1');            // Riak Admin version

//define('HOST', '88.198.156.124');   // your RIAK server IP
define('HOST', '127.0.0.1');   // your RIAK server IP
define('PORT', 8098);                // your RIAK server PORT
define('VERBOSE', true);

define('DISPLAY_KEYS', 50);          // number of keys to display on a page

error_reporting(1);

/*
 *******************************************************************************
 ***                     NO CHANGES FROM HERE ON                             ***
/*******************************************************************************
*/

$start_page = microtime();
require_once ("lib/riak-client.php");
$backend = new riakAdminBackend($_GET['bucketName']);

$riak =& $backend->riak;
//var_dump($backend->riak);
/*
// init the RIAK connection
$riak = new RiakClient(HOST, PORT);
if (!$riak->isAlive()){
    die ("I couldn't ping the server. Check the HOST AND PORT settings...");
}
*/
// init the $bucket && $key
/*
if (isset($_GET['bucketName'])){
    $bucket = new RiakBucket($riak, $_GET['bucketName']);
    $key = new RiakObject($riak, $bucket, $_GET['key']);
}
*/
switch($_GET['cmd'])	{
	case 'deleteKey':
		$backend->deleteKey($_GET['key']);
	break;
	case 'createBucket':
		$backend->createBucket();
	break;
	case 'delBucket':
		$backend->deleteBucket();
	break;
	case 'updateKey':
			$backend->updateKey($_GET['key'], array_combine($_POST['key'], $_POST['value']));
		echo '<div class="msg">Value updated in RIAK.</div>';
	break;
}
$bucket =& $backend->buckets[$_GET['bucketName']];
/*
// delete a key
if (($_GET['cmd'] == "deleteKey") && ($_GET['bucketName']) && ($_GET['key'])){
    $key->delete();
    $_GET['key']='';
}
*/
/*
// create a bucket with key=>value : "created"=>1
if (($_GET['cmd'] == 'createBucket') && ($_POST['bucketName'])) {
    $data=array("created"=>1);
    $bucket = new RiakBucket ($riak, $_POST['bucketName']);
    $x = $bucket->newObject("", $data);
    $x->store();
}
*/
/*
// delete a bucket and all keys from it
if (($_GET['cmd'] == 'delBucket') && ($_GET['bucketName'])) {
    $keys = $bucket->getKeys();
    for ($i=0; $i<count($keys); $i++) {
        $key = new RiakObject($riak, $bucket, $keys[$i]);
        $key->delete();
    }
    // i don't need to delete the bucket, since it will be removed automatically when no keys are in it
}
*/
// update the KEY with new $data
/*if (($_GET['cmd'] == 'updateKey') && (isset($_POST['key'][0])) && (isset($_POST['value'][0]))) {
    $arrVal = $_POST['value'];
    $arrKey = $_POST['key'];
    
    foreach ($arrKey AS $index => $keyTmp) {
        if ($arrVal[$index]) {
            $value = $arrVal[$index];
            $data[$keyTmp] = $value;
        }
    }
    
    $obj = $bucket->newObject($_GET['key'], $data);
    $obj->store();
    
    echo '<div class="msg">Value updated in RIAK.</div>';
}*/


class riakAdminBackend	{
	
	public $riak;
	public $activeBucketObj = null;
	private $activeBucket = "";
	
	// init the RIAK connection
	function __construct($bucketName=null)	{
		$this->riak = new RiakClient(HOST, PORT);
		if ($this->riak->isAlive() == false){
			die ("I couldn't ping the server. Check the HOST AND PORT settings...");
		}
		
		// init the $bucket && $key
		if ($bucketName != null){
			$this->pickBucket($bucketName);
		}
	}
	
	function pickBucket($bucketName, $ghost=false)	{ //if ghost flag is true then make the bucket with some random number for short term use
		$ghost = ($ghost == true) ? rand() : "";
		$this->buckets[$bucketName.$ghost] = $this->riak->bucket($bucketName);
		$this->switchBucket($bucketName.$ghost);
		return $this->bucket[$bucketName.$ghost];
	}
	
	function switchBucket($bucketName)	{
		if($bucketName == $this->activeBucket || $this->bucketInPool($bucketName) == true)	{
			$this->activeBucket = $bucketName;
			return $this->bucket[$bucketName];
		}
		return $this->pickBucket($bucketName, false);
	}
	
	function createBucket($bucketName=null, $key, $value)	{
		$bucketName = ($bucketName != null) ? $bucketName : $this->activeBucket;
		if($bucketName == $this->activeBucket || $this->bucketInPool($bucketName) == false) {
			$this->buckets[$bucketName]->newObject("created")->setData(1)->store();
			return true;
		}
		return false;
	}
	
	function bucketInPool($bucketName)	{
		if(array_key_exists($bucketName, $this->buckets) == true)	{
			return true;
		}
		return false;
	}
	
	function deleteKey($key, $bucketName=null, $fast=false)	{
		$bucketName = ($bucketName != null) ? $bucketName : $this->activeBucket;
		if($bucketName == $this->activeBucket || $this->bucketInPool($bucketName) == true && ($fast == true || $this->keyVaild($key) == true))	{
			$this->buckets[$bucketName]->get($key)->delete();
			return true;
		}
		return false;
	}
	
	function deleteBucket($bucketName=null)	{
		$bucketName = ($bucketName != null) ? $bucketName : $this->activeBucket;
		if($this->bucketInPool($bucketName) == true)	{
			$keys = $this->buckets[$bucketName]->getKeys();
			foreach($keys as $dat)	{
				$this->deleteKey($dat);
			}
			// i don't need to delete the bucket, since it will be removed automatically when no keys are in it
		}
	}
	
	function updateKey($key, $value, $bucketName=null)	{
		$bucketName = ($bucketName != null) ? $bucketName : $this->activeBucket;
		if($bucketName==$this->activeBucket || $this->switchBucket($bucketName) == true)	{
			$this->buckets[$bucketName]->newObject($key)->setData($value)->store();
		}
	}
	
	function keyVaild($key)	{
		if(is_string($key) == true)	{
			return true;
		}
		return false;
	}
}

?>
<html>
<head>
    <title>RiakAdmin v<?php echo VERSION .' @ ' . HOST;?></title>
    <style type="text/css">
        body { background-color: #fff; color: #666; font-family: sans-serif, Arial; font-size: 14px; margin: 0px; margin-top: 10px;}
        h3 {text-decoration: underline;}
        .page {width: 1200px; margin-left: auto; margin-right: auto; text-align: center;}
        .left { background-color: #f8f8f8; width: 300px; padding: 7px; display: table-cell; text-align: left; border: 1px solid #666; border-right: 0px; vertical-align: top;}
        .right { background-color: #fff; width: 900px; color: #666; display: table-cell; border: 1px solid #666; text-align: left; margin: 5px; vertical-align: top;}
        .bucketName {font-weight: none;}
        .bucketNameSelected {font-weight: bold;}
        .bucketActions { font-weight: bold; font-size: 10px; text-decoration: none;}
        .content {margin: 10px;}
        .td_left { background-color: #f8f8f8; border: 1px dashed; border-right: 0px; display: table-cell; width:250px; padding: 7px; vertical-align: middle;}
        .td_right { border: 1px dashed; border-left: 0px; display: table-cell; width: 600px; padding: 7px; vertical-align: middle;}
        .msg { border: 1px dashed; text-align: center; margin-left: auto; margin-right: auto; margin: 10px; font-weight: bold; background-color: #f0f0f0; padding: 7px;}
        .msgSmall { font-size: 12px; margin-left: auto; margin-right: auto; text-align: justify; padding: 5px; }
    </style>
<?php
    if ($_GET['cmd'] == 'delBucket') {
        echo '    <meta http-equiv="refresh" content="10; url=?">';
    }
?>
</head>
<body>
<div class="page">
<?php
    if ($_GET['cmd'] == 'delBucket') {
        echo '<div class="msg">Please wait... the delete is not instant as you could have thousands of keys in this bucket... if the bucket is still present, issue this command again!</div>';
    }
?>
    <div class="left"><?php echo left_menu();?></div>
    <div class="right"><?php echo right_content();?></div>
</div>
</body>
</html>

<?php
// left menu: create new bucket + show list of current ones

function left_menu() {
    global $riak, $_GET;
    // screate a new bucket
    $ret = '
    <center><h3>RiakAdmin v'.VERSION.' @ '.HOST.'</h3>
    <form name="createBucket" method="GET" action="?cmd=createBucket">
        <input type="text" name="bucketName" value="Create a new bucket" onClick="this.value=\'\';">
        <input type="submit" name="ok" value="Create">
        <input type="hidden" name="cmd" value="createBucket"/>
    </form></center>
    <div class="msgSmall">When creating a new bucket, a key named "created" with value "1" will be set in that bucket.</div>
    <hr>';

    // bucket list
    $buckets = $riak->buckets();
    if (count($buckets) == 0){
        $ret .= '<b>No buckets found. Create one?</b>';
    }
    else {
        $ret .= 'List of current buckets:
            <ul type="square">';
        for ($i=0; $i<count($buckets);$i++){
            if ( $buckets[$i]->getName() == $_GET['bucketName'] ){
                $ret .= '<li class="bucketNameSelected"><a href="?cmd=useBucket&bucketName='.$buckets[$i]->getName().'">'.$buckets[$i]->getName().'</a>';
            }
            else {
                $ret .= '<li class="bucketName"><a href="?cmd=useBucket&bucketName='.$buckets[$i]->getName().'">'.$buckets[$i]->getName().'</a>';
            }
            $ret .= ' <a href="?cmd=delBucket&bucketName='.$buckets[$i]->getName().'" class="bucketActions">[ Delete bucket ]</a></li>';
        }
        $ret .= '
            </ul>';
    }
    
    return $ret;
}

// right menu: list all keys from a bucket + create/delete/modify
function right_content() {
    global $riak, $bucket, $key, $_GET, $_POST;

    $ret = '';
    // if i have a bucket selected, but no KEY, I'll display all keys from it
    if ((isset($bucket) && (!isset($_GET['key'])))){
        $keys=$bucket->getKeys();

        // pagination ???
        
        $ret .= '
        <div class="content">
            <h3>Selected BUCKET: "'.$_GET['bucketName'].'"</h3>
            <div class="td_left" align="center"><b>KEY NAME</b></div>
            <div class="td_right" align="center"><b>ACTIONS</b></div>
        </div>';
        $total=0;
        for ($i=0; $i<count($keys); $i++){
            $total++;
            $ret .= '
            <div class="content">
                <div class="td_left"><b>' . $keys[$i] . '</b></div>
                <div class="td_right">
                    <a href="?cmd=useBucket&bucketName=' . $_GET['bucketName'] .'&key=' . $keys[$i] . '">View/Modify</a> | 
                    <a href="?cmd=deleteKey&bucketName=' . $_GET['bucketName'] .'&key=' . $keys[$i] . '">Delete</a>
                </div>
            </div>';
        }
        if ($total==0){
            $ret = '
            <div class="msg">No keys found in this bucket.</div>';
        }
        $ret .= '</table>';
    }
    // else if I have a bucket selected and a KEY, I'll display the key properties
    elseif ((isset($bucket)) && (isset($_GET['key']))){
		$key = $bucket->get($_GET['key']);
        $ret .= '
        <form name="updateKey" method="POST" action="?cmd=updateKey&bucketName='.$_GET['bucketName'].'&key='.$_GET['key'].'">
        <div class="content">
            <h3>Selected KEY: "'.$_GET['key'].'"</h3>
            <div class="td_left" align="center"><b>FIELD</b></div>
            <div class="td_right" align="center"><b>VALUE</b></div>
        </div>';
        $total = 0;
        foreach ($key->reload()->getData() as $key=>$value){
            $total++;
            $ret .= '
            <div class="content">
                <div class="td_left"><input type="text" name="key[]" value="' . $key .'"></div>
                <div class="td_right"><textarea name="value[]" rows="3" cols="30">' . $value . "</textarea></div>
            </div>";
        }
        if ($total==0){
            $ret = '
            <div class="msg">For some reasons, this key could not be read.</div>';
        }
        $ret .= '
        <div style="text-align: center;">
            <input type="submit" name="ok" value="Save" align="center">
            <a href="#" onClick="document.getElementById(\'fieldList\').innerHTML=document.getElementById(\'fieldList\').innerHTML + \'<div class=content><div class=td_left><input type=text name=key[]></div><div class=td_right><textarea name=value[] rows=3 cols=30></textarea></div></div>\'">Add another key => value!</a>
        </div>
        <div id="fieldList"></div>
        </form>';
    }
    // first page
    else {
        $ret = '
        <div class="msg">Chose a bucket from the left panel, or create a new one...</div>';
    }
    $ret .= '</div>';
    return $ret;
}

$end_page = microtime();
$page_generation = $end_page - $start_page;
echo '<div class="msg">It took me ' . number_format($page_generation, 2) .' seconds to generate this page...</div>';
?>
<br><br>
