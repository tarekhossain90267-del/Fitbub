<?php
session_start();        // start session
session_unset();        // remove all session variables
session_destroy();      // destroy the session

// redirect back to login page
header("Location: login.php");
exit();
?>
