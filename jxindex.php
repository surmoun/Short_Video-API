<?php require 'API.php';
 
echo getUrl();
 
    function getUrl()
    {
        $data = \API::findURL($_GET['url']);
                return  $data;
    }
    
    ?>
