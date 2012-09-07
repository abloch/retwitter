<?php
$newsdb = sqlite_open('./news.db',0777,$msg);
if (!$newsdb) die($msg);

require_once('config.php');
require_once('twitteroauth/twitteroauth.php');

function is_new($db,$id)
{
	$sql = "select count(id) from news where id=$id";
	$dbres = sqlite_query($db,$sql);
	$num=sqlite_fetch_single($dbres);
	if ($num == 0) return TRUE;
	else return FALSE;
}

function splitURL($text)
{
	$parts= preg_split("/[\s,]+/",$text);
	for ($i=0 ; $i <= count($parts) ; $i++)
	{
		if (substr($parts[$i],0,7)=="http://")
		{
			$url=$parts[$i];
			unset ($parts[$i]);
		}
		$realText=implode($parts,' ');
	}
	$ret=array("text"=>"$realText","url"=>$url);
	return $ret;
}

function store($db,$t)
{
	$sp=splitURL($t->text);
	$text=sqlite_escape_string($sp["text"]);
	$url=$sp["url"];
	$date=strtotime($t->created_at);
	$user=$t->user;
	$user_id=$user->id_str;
	$sql="insert into news (id,date,user_id,text,url) values ('$t->id_str','$date','$user_id','$text','$url')";
	$ret=sqlite_exec($db,$sql,$message);
	if (!$ret) print "$message\n";
	return $ret;
}

function retweet($connection,$t)
{
	return $connection->post('statuses/retweet/'.$t->id_str);
}

$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
$res=$connection->get('statuses/friends_timeline');
# print_r($newsdb,FALSE);die();
foreach($res as $t)
{
	if (is_new($newsdb,$t->id_str))
	{
		if (retweet($connection,$t))
			store($newsdb,$t);
		//echo $t->text;
	}
#	break;
}
