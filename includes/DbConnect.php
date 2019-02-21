<?php

class DbConnect
{

    private $con;


    public function connect()
    {
        include_once dirname(__FILE__) . '/Constants.php';

        $this->con = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);

        if (mysqli_connect_errno()) {
            echo "Failed to connect";
            return null;
        }

        return $this->con;
    }

}