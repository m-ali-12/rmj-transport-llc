<?php 
	      $dbhost = 'MAKE IT ACCORDING TO YOURSELF';
         $dbuser = 'MAKE IT ACCORDING TO YOURSELF';
         $dbpass = 'MAKE IT ACCORDING TO YOURSELF';
         $dbname = 'MAKE IT ACCORDING TO YOURSELF';
         $conn   = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
         

         //for special chacter charset function//
         //mysql_set_charset($conn,"utf8")

         mysqli_set_charset($conn,"utf8");

        
        //for special chacter charset function//
        // $conn->set_charset("utf8");

         //for special chacter charset function//
         //$conn->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND,"SET NAMES 'utf8'");

         if(!$conn) {
            die('Could not connect: ' . mysqli_error());
         }

?>