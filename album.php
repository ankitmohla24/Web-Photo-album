<?php
// display all errors on the browser
error_reporting(E_ALL);
ini_set('display_errors','On');
// if there are many files in your Dropbox it can take some time, so disable the max. execution time
set_time_limit(0);
require_once("DropboxClient.php");
// you have to create an app at https://www.dropbox.com/developers/apps and enter details below:
$dropbox = new DropboxClient(array(
	'app_key' => "*************",      // Put your Dropbox API key here
	'app_secret' => "*************",   // Put your Dropbox API secret here
	'app_full_access' => false,
),'en');
// first try to load existing access token
$access_token = load_token("access");
if(!empty($access_token)) {
	$dropbox->SetAccessToken($access_token);
	echo "loaded access token:";
	print_r($access_token);
}
elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
{	// then load our previosly created request token
	$request_token = load_token($_GET['oauth_token']);
	if(empty($request_token)) die('Request token not found!');
	// get & store access token, the request token is not needed anymore
	$access_token = $dropbox->GetAccessToken($request_token);	
	store_token($access_token, "access");
	delete_token($_GET['oauth_token']);
}
// checks if access token is required
if(!$dropbox->IsAuthorized())
{	// redirect user to dropbox auth page
	$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
	$request_token = $dropbox->GetRequestToken();
	store_token($request_token, $request_token['t']);
	die("Authentication required. <a href='$auth_url'>Click here.</a>");
}
function store_token($token, $name)
{	if(!file_put_contents("tokens/$name.token", serialize($token)))
		die('<br />Could not store token! <b>Make sure that the directory `tokens` exists and is writable!</b>');
}
function load_token($name)
{	if(!file_exists("tokens/$name.token")) return null;
	return @unserialize(@file_get_contents("tokens/$name.token"));
}
function delete_token($name)
{	@unlink("tokens/$name.token");
}
function enable_implicit_flush()
{	@apache_setenv('no-gzip', 1);
	@ini_set('zlib.output_compression', 0);
	@ini_set('implicit_flush', 1);
	for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
	ob_implicit_flush(1);
	echo "<!-- ".str_repeat(' ', 2000)." -->";
}

if (isset($_GET['Delete'])){
$files = $dropbox->GetFiles("",false);
$p=$_GET['Delete'];
foreach ($files as $value)
{
	$file = basename($value->path);
	if($value->path==$p) {
	$dropbox->Delete($value->path);
	}
}
}
$files = $dropbox->GetFiles("",false);
echo"<form action='album.php' method='post' enctype='multipart/form-data'>";
echo"Select image to upload: <input type='file' name='photo' id='photo'/>";
echo"<input type='submit' name='submit' value='Submit' />";
echo"</form>";

if(isset($_FILES["photo"]["name"]))
{
	$tmp = explode('.',$_FILES['photo']['name']);
	$file_ext = end($tmp);
    $expensions= array("jpeg","jpg");
    if(in_array($file_ext,$expensions)=== false){
         echo"extension not allowed, please choose a JPEG file.";
      }
	else{
	$f = $_FILES["photo"]["name"];
	echo "<pre>";
	echo "\r\n\r\n<b>Uploading $f:</b>\r\n";
	$meta = $dropbox->UploadFile($_FILES["photo"]["tmp_name"], $f);
	echo "\r\n done!";
	echo "</pre>";
	$files = $dropbox->GetFiles("",false);
    }
}
	

$files = $dropbox->GetFiles("",false);

echo "<form name='myform' action='album.php' method='GET'>";
echo "\r\n\r\n<b>Files:</b>\r\n";
echo "<table border='1'>";
echo "<tr>";
echo "<th>image link</th>";
echo "<th>delete</th>";
echo "</tr>";
foreach ($files as $value) {
	echo "<tr>";
    echo "<td><a href='album.php?link=$value->path'>$value->path</td>";
    echo "<td><button value='$value->path' name='Delete' type='submit'>Delete</button></td>";
	echo "</tr>";
}
echo "</table>";
echo "</form>";
echo "<br/><br/><div id='imagedisp' style='width:200px;height:200px;border:1px dotted black;text-align:center'>";
echo "<img id='Image' style='width:199px;height:199px;' />";
echo "</div>";

if (isset($_GET['link'])){
$files = $dropbox->GetFiles("",false);
$p=$_GET['link'];
foreach ($files as $value)
{
	$file = basename($value->path);
	if($value->path==$p) {
	echo "<script type='text/javascript'>document.getElementById('Image').src = '".$dropbox->GetLink($value,false)."';</script>";
	$dropbox->DownloadFile($value, $file);
	}
}
}
?>