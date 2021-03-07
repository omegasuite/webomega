<?php

    /**
     * Visitors module for phpWebSite
     *
     * @author rck <http://www.kiesler.at/>
     */


    /* Make sure the user is a deity before running this script */
    
    if(!$_SESSION["OBJ_user"]->isDeity()) {
        header("location:index.php");
        exit();
    }


    $status = 1;

?>