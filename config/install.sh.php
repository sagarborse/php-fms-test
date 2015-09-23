<?php
require('constant.php');
$configPath = dirname(__FILE__);
$Path = realpath($configPath.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'web');
set_include_path(get_include_path() . PATH_SEPARATOR . $Path);
/* Autoloading Class */
function classLoader($className) 
{
    global $Path;
    $fileName = $Path.DIRECTORY_SEPARATOR. 'controllers' .DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php';
    if (strpos($className,'Interface') !== false) 
    {
        $fileName = $Path.DIRECTORY_SEPARATOR. 'interfaces' .DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php';
    }

    if(file_exists($fileName)) 
    {
        require_once($fileName);
    } 
    else 
    {
        throw new \Exception('Cannot Load class '.$fileName.'. File not present');
    }
}
spl_autoload_register('classLoader'); 

/* Database Connection to be setup */
try 
{
    $pdo = new PDO('mysql:dbname=' . DBNAME . ';host=' . DBHOST, DBUSERNAME, DBPASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} 
catch (PDOException $e) 
{
    throw new \Exception('Connection Error' . $e->getMessage());
}
