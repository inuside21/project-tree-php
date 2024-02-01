<?php

    include("config.php");

    // Database
    // =========================
    //$connection = mysqli_connect("localhost","root","","test");   

    // SET
    $_GET = array_merge($_GET, $_POST);

    /*
    // data - get class
    {
        $dataClass = file_get_contents("../data/class.json");
        $dataClass = json_decode($dataClass);
    }

    // data - package
    {
        $dataPackageList = array();
        $dataPackageListId = array();
        $stmt = $connection->prepare("SELECT * FROM package_tbl");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($obj = $result->fetch_object()) 
        {
            $dataPackageList[] = $obj;
            $dataPackageListId[$obj->id] = $obj;
        }
    }
    */


    // Mode
    // =========================
    if (!isset($_GET['mode']))
    {
        JSONSet2("error", "API Failed", "no method parameter");
    }

    // user - login
    if ($_GET['mode'] == "userlogin")
    {
        //
        $userData = new stdClass();
        $userTokenNew = GUID();

        // check
        {
            if (!isset($_GET['tUname']) || !ValidText($_GET['tUname'], 4, 15))
            {
                JSONSet2("error", "Login Failed", "Invalid username/password 1");
            }

            if (!isset($_GET['tPword']) || !ValidText($_GET['tPword'], 4, 15))
            {
                JSONSet2("error", "Login Failed", "Invalid username/password 2");
            }
        }

        // check
        {
            $stmt = $connection->prepare("SELECT * FROM user_tbl WHERE binary user_uname = ? and binary user_pword = ?");
            $stmt->bind_param("ss", $_GET['tUname'], $_GET['tPword']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                while ($obj = $result->fetch_object()) 
                {
                    //
                    $userData = $obj;
                }
            }
            else
            {
                JSONSet2("error", "Login Failed", "Invalid username/password 3");
            }
        }
        
        //
        $stmt = $connection->prepare("  UPDATE user_tbl SET
                                            user_token = ?
                                        WHERE 
                                            id = ?
        ");
        $stmt->bind_param("ss", $userTokenNew, $userData->id);
        $stmt->execute();

        //
        JSONSet2("ok", "", "", $userTokenNew);
    }

    // user - register
    if ($_GET['mode'] == "userregister")
    {
        //
        $userData = new stdClass();
        $newRef = GUID();

        // check
        {
            if (!isset($_GET['tUname']) || !ValidText($_GET['tUname']))
            {
                JSONSet2("error", "Register Failed", "Invalid entry of length / symbols 1");
            }

            if (!isset($_GET['tPword']) || !ValidText($_GET['tPword']))
            {
                JSONSet2("error", "Register Failed", "Invalid entry of length / symbols 2");
            }

            if (!isset($_GET['tFname']) || !ValidText2($_GET['tFname']))
            {
                JSONSet2("error", "Register Failed", "Invalid entry of length / symbols 3");
            }

            if (!isset($_GET['tEmail']) || !ValidText($_GET['tEmail']))
            {
                JSONSet2("error", "Register Failed", "Invalid entry of length / symbols 4");
            }

            if (!isset($_GET['tContact']) || !ValidText($_GET['tContact']))
            {
                JSONSet2("error", "Register Failed", "Invalid entry of length / symbols 5");
            }
        }

        // check DB
        {
            // exist?
            $stmt = $connection->prepare("SELECT * FROM user_tbl WHERE binary user_uname = ?");
            $stmt->bind_param("s", $_GET['uuname']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($obj = $result->fetch_object()) 
            {
                JSONSet2("error", "Register Failed", "Username already taken");
            }
        }

        //
        {
            $stmt = $connection->prepare("INSERT INTO user_tbl
                                            (
                                                user_uname,
                                                user_pword,
                                                user_fname,
                                                user_phone,
                                                user_email
                                            )
                                        VALUES
                                            (
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?
                                            )
            ");
            $stmt->bind_param("sssss", $_GET['tUname'], $_GET['tPword'], $_GET['tFname'], $_GET['tContact'], $_GET['tEmail']);
            $stmt->execute();
            $getId = $connection->insert_id;
        }

        //
        JSONSet2("ok", "Register Complete", "Please login to continue");
    }

    // user - view
    if ($_GET['mode'] == "userview")
    {
        //
        $userData = new stdClass();

        // check
        {
            if (!isset($_GET['tToken']) || !ValidText($_GET['tToken']))
            {
                JSONSet2("error", "View Failed", "Invalid entry of length / symbols 1");
            }
        }

        // check DB
        {
            // exist?
            $stmt = $connection->prepare("SELECT * FROM user_tbl WHERE binary user_token = ?");
            $stmt->bind_param("s", $_GET['tToken']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                while ($obj = $result->fetch_object()) 
                {
                    $obj->userpic = "https://martorenzo.click/project/tree/server/image/" . $obj->user_image . ".png";
                    $userData = $obj;
                }
            }
            else
            {   
                //
                JSONSet2("error", "View Failed", "Relogin to continue");
            }
            
            // update
            $stmt = $connection->prepare("UPDATE user_tbl SET ctr = ctr + 1 WHERE id = ?");
            $stmt->bind_param("i", $userData->id);
            $stmt->execute();
        }

        //
        JSONSet2("ok", "", "", $userData);
    }

    // insight - view
    if ($_GET['mode'] == "insightview")
    {
        //
        $insightData = new stdClass();

        // check DB
        {
            // log
            $stmt = $connection->prepare("SELECT * FROM dev_log ORDER BY id DESC LIMIT 4");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                while ($obj = $result->fetch_object()) 
                {
                    $insightData->report[] = $obj;
                }
            }
            else
            {   
                $noData = new stdClass();
                $noData->id = "1";
                $noData->dev_date = "";
                $noData->dev_message = "No logs found";
                $insightData->report[] = $noData;
            }

            // tree
            $stmt = $connection->prepare("SELECT * FROM dev_tbl");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                while ($obj = $result->fetch_object()) 
                {
                    $obj->coordinate = new stdClass();
                    $obj->coordinate->latitude = (float)$obj->dev_treegmaplat;
                    $obj->coordinate->longitude = (float)$obj->dev_treegmaplong;
                    $insightData->tree[] = $obj;
                }
            }
        }

        //
        JSONSet2("ok", "", "", $insightData);
    }

    // tree - view
    if ($_GET['mode'] == "treeview")
    {
        //
        $treeData = new stdClass();

        // check
        {
            if (!isset($_GET['treeId']))
            {
                JSONSet2("error", "View Failed", "Invalid ID");
            }
        }

        // check DB
        {
            // tree
            $stmt = $connection->prepare("SELECT * FROM dev_tbl where id = ?");
            $stmt->bind_param("i", $_GET['treeId']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                while ($obj = $result->fetch_object()) 
                {
                    $obj->coordinate = new stdClass();
                    $obj->coordinate->latitude = (float)$obj->dev_treegmaplat;
                    $obj->coordinate->longitude = (float)$obj->dev_treegmaplong;
                    $treeData = $obj;
                }
            }
        }

        //
        JSONSet2("ok", "", "s", $treeData);
    }

    // fire - view
    if ($_GET['mode'] == "fireview")
    {
        //
        $insightData = new stdClass();

        // check DB
        {
            // log
            $stmt = $connection->prepare("SELECT * FROM dev_log WHERE dev_message LIKE '%Fire%' ORDER BY id DESC LIMIT 4");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                while ($obj = $result->fetch_object()) 
                {
                    $insightData->report[] = $obj;
                }
            }
            else
            {   
                $noData = new stdClass();
                $noData->id = "1";
                $noData->dev_date = "";
                $noData->dev_message = "No logs found";
                $insightData->report[] = $noData;
            }

            // tree
            $stmt = $connection->prepare("SELECT * FROM dev_tbl");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                while ($obj = $result->fetch_object()) 
                {
                    $obj->coordinate = new stdClass();
                    $obj->coordinate->latitude = (float)$obj->dev_treegmaplat;
                    $obj->coordinate->longitude = (float)$obj->dev_treegmaplong;
                    $insightData->tree[] = $obj;
                }
            }
        }

        // user
        {
            if (isset($_GET['tToken']) && isset($_GET['tLogFire']))
            {
                $stmt = $connection->prepare("  UPDATE user_tbl SET
                                                    user_logfire = 0
                                                WHERE
                                                    user_token = ?
                ");
                $stmt->bind_param("s", $_GET['tToken']);
                $stmt->execute();
            }
        }

        //
        JSONSet2("ok", "", "", $insightData);
    }

    // fall - view
    if ($_GET['mode'] == "fallview")
    {
        //
        $insightData = new stdClass();

        // check DB
        {
            // log
            $stmt = $connection->prepare("SELECT * FROM dev_log WHERE dev_message LIKE '%Fall%' ORDER BY id DESC LIMIT 4");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                while ($obj = $result->fetch_object()) 
                {
                    $insightData->report[] = $obj;
                }
            }
            else
            {   
                $noData = new stdClass();
                $noData->id = "1";
                $noData->dev_date = "";
                $noData->dev_message = "No logs found";
                $insightData->report[] = $noData;
            }

            // tree
            $stmt = $connection->prepare("SELECT * FROM dev_tbl");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                while ($obj = $result->fetch_object()) 
                {
                    $obj->coordinate = new stdClass();
                    $obj->coordinate->latitude = (float)$obj->dev_treegmaplat;
                    $obj->coordinate->longitude = (float)$obj->dev_treegmaplong;
                    $insightData->tree[] = $obj;
                }
            }
        }

        // user
        {
            if (isset($_GET['tToken']) && isset($_GET['tLogFall']))
            {
                $stmt = $connection->prepare("  UPDATE user_tbl SET
                                                    user_logfall = 0
                                                WHERE
                                                    user_token = ?
                ");
                $stmt->bind_param("s", $_GET['tToken']);
                $stmt->execute();
            }
        }

        //
        JSONSet2("ok", "", "", $insightData);
    }

    // history - view
    if ($_GET['mode'] == "historyview")
    {
        //
        $insightData = new stdClass();

        // check DB
        {
            // log
            $stmt = $connection->prepare("SELECT * FROM dev_log ORDER BY id DESC LIMIT 100");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                while ($obj = $result->fetch_object()) 
                {
                    $insightData->report[] = $obj;
                }
            }
            else
            {   
                $noData = new stdClass();
                $noData->id = "1";
                $noData->dev_date = "";
                $noData->dev_message = "No logs found";
                $insightData->report[] = $noData;
            }

            // tree
            $stmt = $connection->prepare("SELECT * FROM dev_tbl");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                while ($obj = $result->fetch_object()) 
                {
                    $obj->coordinate = new stdClass();
                    $obj->coordinate->latitude = (float)$obj->dev_treegmaplat;
                    $obj->coordinate->longitude = (float)$obj->dev_treegmaplong;
                    $insightData->tree[] = $obj;
                }
            }
        }

        // user
        {
            if (isset($_GET['tToken']) && isset($_GET['tLogMain']))
            {
                $stmt = $connection->prepare("  UPDATE user_tbl SET
                                                    user_logmain = 0
                                                WHERE
                                                    user_token = ?
                ");
                $stmt->bind_param("s", $_GET['tToken']);
                $stmt->execute();
            }
        }

        //
        JSONSet2("ok", "", "", $insightData);
    }

    // maintenance - view
    if ($_GET['mode'] == "maintenanceview")
    {
        //
        $treeData = new stdClass();

        // check DB
        {
            // tree
            $stmt = $connection->prepare("SELECT * FROM dev_tbl");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                while ($obj = $result->fetch_object()) 
                {
                    $obj->coordinate = new stdClass();
                    $obj->coordinate->latitude = (float)$obj->dev_treegmaplat;
                    $obj->coordinate->longitude = (float)$obj->dev_treegmaplong;
                    $obj->batteryicon = "https://martorenzo.click/project/tree/server/image/bat" . $obj->dev_battery . ".png";
                    $treeData = $obj;
                }
            }
        }

        //
        JSONSet2("ok", "", "", $treeData);
    }






    // ards - dev set
    if ($_GET['mode'] == "devset")
    {
        //
        $treeData = new stdClass();

        // check
        {
            if (!isset($_GET['devgyx']))
            {
                JSONSet2("error", "Update Failed", "Invalid 1");
            }

            if (!isset($_GET['devgyy']))
            {
                JSONSet2("error", "Update Failed", "Invalid 2");
            }

            if (!isset($_GET['devgyz']))
            {
                JSONSet2("error", "Update Failed", "Invalid 3");
            }

            if (!isset($_GET['devlat']))
            {
                JSONSet2("error", "Update Failed", "Invalid 4");
            }

            if (!isset($_GET['devlong']))
            {
                JSONSet2("error", "Update Failed", "Invalid 5");
            }

            if (!isset($_GET['devfire']))
            {
                JSONSet2("error", "Update Failed", "Invalid 6");
            }

            if (!isset($_GET['devbat']))
            {
                JSONSet2("error", "Update Failed", "Invalid 7");
            }

            if (!isset($_GET['devid']))
            {
                JSONSet2("error", "Update Failed", "Invalid 8");
            }
        }

        // check DB
        {
            // tree
            $stmt = $connection->prepare("SELECT * FROM dev_tbl where id = ?");
            $stmt->bind_param("i", $_GET['devid']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                while ($obj = $result->fetch_object()) 
                {
                    $treeData = $obj;
                }
            }
        }

        // 
        {
            $stmt = $connection->prepare("  UPDATE dev_tbl SET
                                                dev_gy_x = ?,
                                                dev_gy_y = ?,
                                                dev_gy_z = ?,
                                                dev_treegmaplat = ?,
                                                dev_treegmaplong = ?,
                                                dev_fire = ?,
                                                dev_battery = ?
                                            WHERE
                                                id = ?
            ");
            $stmt->bind_param("dddssiii", $_GET['devgyx'], $_GET['devgyy'], $_GET['devgyz'], $_GET['devlat'], $_GET['devlong'], $_GET['devfire'], $_GET['devbat'], $_GET['devid']);
            $stmt->execute();
        }

        // log
        {
            //
            $isFall = false;

            if ((float)$_GET['devgyx'] >= 5 || (float)$_GET['devgyx'] <= -5)
            {
                $isFall = true;
            }

            if ((float)$_GET['devgyy'] >= 5 || (float)$_GET['devgyy'] <= -5)
            {
                $isFall = true;
            }

            if ((float)$_GET['devgyz'] >= 15 || (float)$_GET['devgyz'] <= 5)
            {
                $isFall = true;
            }

            // fall
            if ($isFall)
            {
                //
                $message = "Fall has been detected " . $treeData->dev_name;
                $stmt = $connection->prepare("  INSERT INTO dev_log
                                                    (
                                                        dev_id,
                                                        dev_date,
                                                        dev_message
                                                    )
                                                VALUES
                                                    (
                                                        ?,
                                                        ?,
                                                        ?
                                                    )
                ");
                $stmt->bind_param("iss", $treeData->id, $dateResult, $message);
                $stmt->execute();

                // log
                $stmt = $connection->prepare("  UPDATE user_tbl SET
                                                    user_logfall = user_logfall + 1,
                                                    user_logmain = user_logmain + 1
                ");
                $stmt->execute();
            }

            // fire
            if ((int)$_GET['devfire'] < 500)
            {
                //
                $message = "Fire has been detected " . $treeData->dev_name;
                $stmt = $connection->prepare("  INSERT INTO dev_log
                                                    (
                                                        dev_id,
                                                        dev_date,
                                                        dev_message
                                                    )
                                                VALUES
                                                    (
                                                        ?,
                                                        ?,
                                                        ?
                                                    )
                ");
                $stmt->bind_param("iss", $treeData->id, $dateResult, $message);
                $stmt->execute();

                //
                $stmt = $connection->prepare("  UPDATE user_tbl SET
                                                    user_logfire = user_logfire + 1,
                                                    user_logmain = user_logmain + 1
                ");
                $stmt->execute();
            }
        }

        echo "OK1";
    }




    // Function
    // =========================



    // Outer Function
    // =========================
    function JSONGet()
    {
        /*
        // get json
        $json = file_get_contents('php://input');
        $data = json_decode($json);

        // sanitize?
        {
            sanitize_array($data);
        }
       

        return $data;
         */
    }

    function JSONSet2($resStatus = "", $resTitle = "", $resMsg = "", $resData = "")
    {
        /*
            status:
                ok      - success
                error   - error

            title:
                return any notif title

            message:
                return any notif msg
            
            data:
                return any result
        */
        echo json_encode(array("status" => $resStatus, "title" => $resTitle, "message" => $resMsg, "data" => $resData));
        exit();
    }

    function JSONSetUnity($resStatus = "", $resTitle = "", $resMsg = "", $resData = "")
    {
        /*
            status:
                ok      - success
                error   - error

            title:
                return any notif title

            message:
                return any notif msg
            
            data:
                return any result
        */

        echo $resStatus . "#" . $resTitle . "#" . $resMsg. "#" . json_encode($resData);
        exit();
    }

    // IDs
    function GUID()
    {
        if (function_exists('com_create_guid') === true)
        {
            return trim(com_create_guid(), '{}');
        }

        return sprintf('%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535));
    }

    // Spaces?
    function ValidText($text, $minText = 4, $maxText = 500)
    {
        $isValid = true;

        // spaces
        if (preg_match('/[\s]+/', $text)) 
        {
            $isValid = false;
        }

        // all space
        if (ctype_space($text))
        {
            $isValid = false;
        }   

        // min
        if (strlen($text) < $minText)
        {
            $isValid = false;
        }

        // max
        if (strlen($text) > $maxText)
        {
            $isValid = false;
        }

        /*
        // character?
        if (strpos($text, '#') !== false || strpos($text, ',') !== false || strpos($text, '|') !== false || strpos($text, '~') !== false || strpos($text, '!') !== false || strpos($text, '+') !== false || strpos($text, '/') !== false || strpos($text, '\\') !== false || strpos($text, '*') !== false || strpos($text, '&') !== false || strpos($text, '%') !== false || strpos($text, '^') !== false) 
        {
            $isValid = false;
        }
        */

        return $isValid;
    }

    // Names?
    function ValidText2($text, $minText = 4, $maxText = 500)
    {
        $isValid = true;

        // all space
        if (ctype_space($text))
        {
            $isValid = false;
        }   

        // min
        if (strlen($text) < $minText)
        {
            $isValid = false;
        }

        // max
        if (strlen($text) > $maxText)
        {
            $isValid = false;
        }

        /*
        // character?
        if (strpos($text, '#') !== false || strpos($text, ',') !== false || strpos($text, '|') !== false || strpos($text, '~') !== false || strpos($text, '!') !== false || strpos($text, '+') !== false || strpos($text, '/') !== false || strpos($text, '\\') !== false || strpos($text, '*') !== false || strpos($text, '&') !== false || strpos($text, '%') !== false || strpos($text, '^') !== false) 
        {
            $isValid = false;
        }
        */

        return $isValid;
    }
?>