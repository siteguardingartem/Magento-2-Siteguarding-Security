<?php
/**
 * SiteGuarding tools installer for customer's panel
 *
 * https://www.siteguarding.com
 * Do not distribute or share.
 * 
 * ver.: 1.1
 * Date: 27 Nov 2018
 */
$allowed_IPs = array(
    '185.72.157.169',
    '185.72.157.170',
    '185.72.157.171',
    '185.72.157.172'
);

define('VERSION', '1.1');

define('SITEGUARDING_SERVER', 'http://www.siteguarding.com/ext/panel_api/index.php');

$private_pgp_key = '-----BEGIN PRIVATE KEY-----
MIIBVwIBADANBgkqhkiG9w0BAQEFAASCAUEwggE9AgEAAkEApvw/ix3k2/D/yMlh
u9LhnpP6pna/91J+V4j0HeAiCmQu8wqnaQtXBUILUYk6jqu+KemuMNzocfA7rxEW
PWTCrQIDAQABAkEAhJu7prHlxlh7+KscZzlQHUvs+HdDeZhUZxWGr5cH0XF3eNoc
8tRF9kVoIwcAOcpM8s1ngkv83wQ9okD9tYxwjQIhANKzekmRpdp0dOxw+IctkWuG
h0hA5I5vUcbsM9Q86tzbAiEAyuLAtG17ucDJlj64eltAcyp2mSdS9xzG1h8zxSyf
MRcCIQCHtHUUoSwzMUKFbpWDawP4PyMulC0g1+3RsxwGnF2gdQIhAMkICf4+Bby3
JIg1OcIzrRbwWnfDGVg2MWd1n2yenFadAiEAzlDVVGN4Fn/0VM0pWD71hKw9TK3X
bS4xpkyQlDKC96c=
-----END PRIVATE KEY-----';

$scan_path = dirname(__FILE__);
if (!defined('DIRSEP'))
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') define('DIRSEP', '\\');
    else define('DIRSEP', '/');
}
define('WEBSITE_ROOT', dirname(__FILE__).DIRSEP);



/**
 * Start
 */
$task_id = '';
if (isset($_REQUEST['task_id'])) $task_id = trim($_REQUEST['task_id']);
if ($task_id == '') die('siteguarding_tools.php is ok');

// Check if request came from allowed IP address
$is_allowed_session = false;
foreach ($allowed_IPs as $ip)
{
    if ($_SERVER["REMOTE_ADDR"] != trim($ip))
    {
        $is_allowed_session = true;
        break;
    }
}

if ($is_allowed_session === false)
{
    // Check session with PGP way
    $task_pgp = '';
    if (isset($_REQUEST['task_pgp'])) $task_pgp = trim($_REQUEST['task_pgp']);
    if ($task_pgp == '') die('task_pgp error');
    
    $task_pgp = trim(PGP_decrypt($task_pgp));
    if ($task_pgp != $task_id) die('task_pgp wrong value');
}


// Ping action
if ($task_id == 'ping')
{
    $a = array('status' => 'PING_OK', 'ver' => VERSION);
    $login = WEBSITE_ROOT.'webanalyze'.DIRSEP.'website-security-conf.php';
    if (file_exists($login))
    {
        $a['login'] = Read_File($login);
    }
    
    die(json_encode($a));
}


// Connect to SiteGuarding.com server
$link = SITEGUARDING_SERVER.'?action=siteguarding_tools&task_id='.$task_id;
$task_json = trim(GetRemote_file_contents($link));
if ($task_json == '') die('Empty task_json');
$task_json = (array)json_decode($task_json, true);
if ( is_array($task_json) === false || $task_json === false) die('False decode task_json');

foreach ($task_json as $task_code => $task_data)
{
    switch ($task_code)
    {
        case 'savefile':
            Task_savefile($task_data);
            break;
            
        case 'showfile':
            Task_showfile($task_data);
            break;
            
        case 'includefile':
            Task_includefile($task_data);
            break;
    }
}

exit;





/**
 * functions
 */

function Task_savefile($task_data)
{
    if (count($task_data))
    {
        foreach ($task_data as $data_row)
        {
            $filename = $data_row['file'];
            
            if ($filename == 'create_folder') 
            {
                $folder = WEBSITE_ROOT.$data_row['content'];
                if (!file_exists($folder)) mkdir($folder);
                continue;
            }
            
            $content = base64_decode($data_row['content']);
            
            if ($content !== false) 
            {
                Save_File(WEBSITE_ROOT.$filename, $content);
            }
        }
    }
}


function Task_showfile($task_data)
{
    $a = array();
    if (count($task_data))
    {
        foreach ($task_data as $data_row)
        {
            $filename = $data_row['file'];
            if (isset($data_row['size']))
            {
                // Show by size
                if (filesize(WEBSITE_ROOT.$filename) == $data_row['size']) continue;
            }
            
            $a[$filename] = base64_encode(Read_File(WEBSITE_ROOT.$filename));
        }
    }
    
    if (count($a))
    {
        echo json_encode($a);
    }
}



function Task_includefile($task_data)
{
    $folder_webanalyze = WEBSITE_ROOT.'webanalyze';
    if (!file_exists($folder_webanalyze)) mkdir($folder_webanalyze);
    $include_file = $folder_webanalyze.DIRSEP.'tools_include_'.rand(0, 1000).'_'.rand(0, 1000).'.tmpcode';
    Save_File($include_file, $task_data['code']);
    include($include_file);
    unlink($include_file);
}


function Save_File($file, $content)
{
    $fp = fopen($file, 'w');
    fwrite($fp, $content);
    fclose($fp);
}

function Read_File($file)
{
    $contents = '';
    
    if (file_exists($file))
    {
        $fp = fopen($file, "r");
        $contents = fread($fp, filesize($file));
        fclose($fp); 
    }
    
    return $contents;
}


function PGP_decrypt($data, $key){
	
	$data = base64_decode($data);
	openssl_private_decrypt($data, $result, $key);
	if ($result) return $result;
	return false;
}


function GetRemote_file_contents($url, $post_data = array(), $parse = false)
{
    if (extension_loaded('curl')) 
    {
        $ch = curl_init();
        
        $postvars = '';
        foreach($post_data as $key => $value) 
        {
            $postvars .= $key . "=" . $value . "&";
        }
        
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:38.0) Gecko/20100101 Firefox/38.0");
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3600000);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if (count($post_data) > 0)
        {
            curl_setopt($ch,CURLOPT_POST, 1);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $postvars);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 sec
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 10000); // 10 sec
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $output = trim(curl_exec($ch));
        curl_close($ch);
        
        if ($output === false || trim($output) == '')  return false;
        
        if ($parse === true) $output = (array)json_decode($output, true);
        
        return $output;
    }
    else return false;
}

function CreateRemote_file_contents($url, $dst)
{
    $a = CreateRemote_file_contents_ext($url, $dst);
    
    if ($a === false || $a == 0) 
    {
        if (stripos($url, "http://") !== false)
        {
            $url = str_replace("http://", "https://", $url);
            $a = CreateRemote_file_contents_ext($url, $dst);
        }
    }
    
    return $a;
}

function CreateRemote_file_contents_ext($url, $dst)
{
    if (extension_loaded('curl')) 
    {
        $dst = fopen($dst, 'w');
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:38.0) Gecko/20100101 Firefox/38.0");
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3600000);
        curl_setopt($ch, CURLOPT_FILE, $dst);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // 30 sec
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 30000); // 30 sec
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $a = curl_exec($ch);
        if ($a === false)  return false;
        
        $info = curl_getinfo($ch);
        
        curl_close($ch);
        fflush($dst);
        fclose($dst);
        
        return $info['size_download'];
    }
    else return false;
}

?>