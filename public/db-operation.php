<?php
session_start();
include('../includes/crud.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES 'utf8'");
$auth_username = $db->escapeString($_SESSION["user"]);

include_once('../includes/custom-functions.php');
$fn = new custom_functions;
include_once('../includes/functions.php');
$function = new functions;
$permissions = $fn->get_permissions($_SESSION['id']);
$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}
function checkadmin($auth_username)
{
    $db = new Database();
    $db->connect();
    $db->sql("SELECT `username` FROM `admin` WHERE `username`='$auth_username' LIMIT 1");
    $res = $db->getResult();
    if (!empty($res)) {

        return true;
    } else {
        return false;
    }
}
if(ALLOW_MODIFICATION==0 && !defined (ALLOW_MODIFICATION)){
    echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
    return false;
}

if (isset($_POST['change_category'])) {
    if ($permissions['subcategories']['read'] == 1) {
        if ($_POST['category_id'] == '') {
            $sql = "SELECT * FROM subcategory";
        } else {
            $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
            $sql = "SELECT * FROM subcategory WHERE category_id=" . $category_id;
        }
    } else {
        echo "<option value=''>--Select Subcategory--</option>";
        return false;
    }

    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row) {
            echo "<option value=" . $row['id'] . ">" . $row['name'] . "</option>";
        }
    } else {
        echo "<option value=''>--No Sub Category is added--</option>";
    }
}

if (isset($_POST['category'])) {
    if ($permissions['subcategories']['read'] == 1) {
        if ($_POST['category_id'] == '') {
            $sql = "SELECT * FROM subcategory";
        } else {
            $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
            $sql = "SELECT * FROM subcategory WHERE category_id=" . $category_id;
        }

        $db->sql($sql);
        $res = $db->getResult();
        if (!empty($res)) {
            echo "<option value=''>All</option>";
            foreach ($res as $row) {
                echo "<option value=" . $row['id'] . ">" . $row['name'] . "</option>";
            }
        } else {
            echo "<option value=''>--No Sub Category is added--</option>";
        }
    } else {
        echo "<option value=''>All</option>";
    }
}

if (isset($_POST['find_subcategory'])) {
    $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
    $sql = "SELECT * FROM subcategory WHERE category_id=" . $category_id;
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row) {
            echo "<option value=" . $row['id'] . ">" . $row['name'] . "</option>";
        }
    } else {
        echo "<option value=''>--No Sub Category is added--</option>";
    }
}

if (isset($_POST['delete_variant'])) {
    $id = $db->escapeString($fn->xss_clean($_POST['id']));
    $sql = "DELETE FROM product_variant WHERE id=" . $id;
    $db->sql($sql);
}

if (isset($_POST['system_configurations'])) {
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update settings</label>';
        return false;
    }
    $date = $db->escapeString(date('Y-m-d'));
    $currency = empty($_POST['currency']) ? '₹' : $db->escapeString($fn->xss_clean($_POST['currency']));
    $sql = "UPDATE `settings` SET `value`='" . $currency . "' WHERE `variable`='currency'";
    $db->sql($sql);
    $message = "<div class='alert alert-success'> Settings updated successfully!</div>";
    $_POST['system_timezone_gmt'] = (trim($_POST['system_timezone_gmt']) == '00:00') ? "+" . trim($db->escapeString($fn->xss_clean($_POST['system_timezone_gmt']))) : $db->escapeString($fn->xss_clean($_POST['system_timezone_gmt']));

    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['current_version'])))) {
        $_POST['current_version'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['minimum_version_required'])))) {
        $_POST['minimum_version_required'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['delivery_charge'])))) {
        $_POST['delivery_charge'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['min-refer-earn-order-amount'])))) {
        $_POST['min-refer-earn-order-amount'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['min_amount'])))) {
        $_POST['min_amount'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['max-refer-earn-amount'])))) {
        $_POST['max-refer-earn-amount'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['minimum-withdrawal-amount'])))) {
        $_POST['minimum-withdrawal-amount'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['refer-earn-bonus'])))) {
        $_POST['refer-earn-bonus'] = 0;
    }
    if (preg_match("/[a-z]/i", $db->escapeString($fn->xss_clean($_POST['tax'])))) {
        $_POST['tax'] = 0;
    }
    $settings_value = json_encode($fn->xss_clean_array($_POST));
    $sql = "UPDATE settings SET value='" . $settings_value . "' WHERE variable='system_timezone'";
    $db->sql($sql);
    $res = $db->getResult();
    $sql_logo = "select value from `settings` where variable='Logo' OR variable='logo'";
    $db->sql($sql_logo);
    $res_logo = $db->getResult(); 
    $file_name = $_FILES['logo']['name'];
    
    if(!empty($_FILES["logo"]["tmp_name"]) && $_FILES["logo"]["size"] > 0){
        $tmp = explode('.', $file_name);
        $ext = end($tmp);
        $mimetype = mime_content_type($_FILES["logo"]["tmp_name"]);
        if (!in_array($mimetype, array('image/jpg','image/jpeg', 'image/gif', 'image/png'))) {
            echo " <span class='label label-danger'>Logo Image type must jpg, jpeg, gif, or png!</span>";
            return false;
            
        } else {
            $old_image = '../dist/img/' . $res_logo[0]['value'];
            if (file_exists($old_image)) {
                unlink($old_image);
            }
    
            $target_path = '../dist/img/';
            $filename = "logo." . strtolower($ext);
            $full_path = $target_path . '' . $filename;
            if (!move_uploaded_file($_FILES["logo"]["tmp_name"], $full_path)) {
                $message = "Image could not be uploaded<br/>";
            } else {
                //Update Logo - id = 5
                $sql = "UPDATE `settings` SET `value`='" . $filename . "' WHERE `variable` = 'logo'";
                $db->sql($sql);
            }
        }
    
    }

    echo "<p class='alert alert-success'>Settings Saved!</p>";
}
if (isset($_POST['payment_method_settings'])) {
    if(ALLOW_MODIFICATION==0 && !defined (ALLOW_MODIFICATION)){
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update settings</label>';
        return false;
    }
    $data = $fn->get_settings('payment_methods', true);
    if (empty($data)) {
        $json_data = json_encode($fn->xss_clean_array($_POST));
        $sql = "INSERT INTO `settings`(`variable`, `value`) VALUES ('payment_methods','$json_data')";
        $db->sql($sql);
        echo "<div class='alert alert-success'> Settings created successfully!</div>";
    } else {
        $json_data = json_encode($fn->xss_clean_array($_POST));
        $sql = "UPDATE `settings` SET `value`='$json_data' WHERE `variable`='payment_methods'";
        $db->sql($sql);
        echo "<div class='alert alert-success'> Settings updated successfully!</div>";
    }
}
if (isset($_POST['time_slot_config']) && $_POST['time_slot_config'] == 1) {
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update settings</label>';
        return false;
    }
    $_POST['allowed_days'] = empty($_POST['allowed_days']) ? 1 : $db->escapeString($fn->xss_clean($_POST['allowed_days']));
    if (!$time_slot_config) {
        $settings_value = json_encode($fn->xss_clean_array($_POST));
        $sql = "INSERT INTO settings (`variable`,`value`) VALUES ('time_slot_config','" . $settings_value . "')";
    } else {
        $settings_value = json_encode($fn->xss_clean_array($_POST));
        $sql = "UPDATE settings SET value='" . $settings_value . "' WHERE variable='time_slot_config'";
    }
    if ($db->sql($sql)) {
        echo "<p class='alert alert-success'>Saved Successfully!</p>";
    } else {
        echo "<p class='alert alert-danger'>Something went wrong please try again!</p>";
    }
}

if (isset($_POST['add_dr_gold']) && $_POST['add_dr_gold'] == 1) {
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update settings</label>';
        return false;
    }
    $sql = "select * from settings where variable = 'doctor_brown'";
    $db->sql($sql);
    $res = $db->getResult();
    if(empty($res)){
        $settings_value = json_encode($fn->xss_clean_array($_POST));
        $sql = "INSERT INTO settings (`variable`,`value`) VALUES ('doctor_brown','$settings_value ')";
        if ($db->sql($sql)) {
            $response['error'] = false;
            $response['message'] = "Your system is registered and activated successfully!";
        } else {
            $response['error'] = true;
            $response['message'] = "Something went wrong please try again!";
        }
    }else{
        $response['error'] = false;
        $response['message'] = "Your system is already activated!";
    }
    print_r(json_encode($response));
    
}

if (isset($_POST['front_end_settings']) && $_POST['front_end_settings'] == 1) {
    if ($permissions['settings']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update settings</label>';
        return false;
    }
    $res = $res_data = array();
    $favicon_old = "";
    $screenshots_old = "";
    $google_play_old ="";
    
    $sql = "select * from settings where variable = 'front_end_settings'";
    $db->sql($sql);
    $res = $db->getResult();
    $res_data = (!empty($res)) ? json_decode($res[0]['value'],true): array(); 
    $favicon_old = $res_data['favicon'];
    $screenshots_old = $res_data['screenshots'];
    $google_play_old = $res_data['google_play'];
    
    if (isset($_FILES['favicon']) && !empty($_FILES['favicon']) && $_FILES['favicon']['error'] == 0 && $_FILES['favicon']['size'] > 0) {
        $favicon = $db->escapeString($fn->xss_clean($_FILES['favicon']['name']));
        $extension = pathinfo($_FILES["favicon"]["name"])['extension'];
        $mimetype = mime_content_type($_FILES["favicon"]["tmp_name"]);
        if (!in_array($mimetype, array('image/jpg','image/jpeg', 'image/gif', 'image/png'))) {
            echo "<p class='alert alert-danger'>Image type must jpg, jpeg, gif, or png!</p>";
            return false;
        }
        
        $target_path = '../dist/img/';
        if(!empty($favicon_old) && $favicon_old != ''){
            unlink($target_path.$favicon_old);
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
      
        if (!move_uploaded_file($_FILES["favicon"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load favicon!</p>";
            return false;
        }
        $_POST['favicon'] = $filename;
    } else {
        $_POST['favicon'] = $favicon_old;
    }
    
    
    if (isset($_FILES['screenshots']) && !empty($_FILES['screenshots']) && $_FILES['screenshots']['error'] == 0 && $_FILES['screenshots']['size'] > 0) {
        $screenshots = $db->escapeString($fn->xss_clean($_FILES['screenshots']['name']));
        $extension = pathinfo($_FILES["screenshots"]["name"])['extension'];
        $mimetype = mime_content_type($_FILES["screenshots"]["tmp_name"]);
        if (!in_array($mimetype, array('image/jpg','image/jpeg', 'image/gif', 'image/png'))) {
            echo "<p class='alert alert-danger'>Image type must jpg, jpeg, gif, or png!</p>";
            return false;
        }
        
        $target_path = '../dist/img/';
        if(!empty($screenshots_old) && $screenshots_old != ''){
            unlink($target_path.$screenshots_old);
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
      
        if (!move_uploaded_file($_FILES["screenshots"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load App Screenshots!</p>";
            return false;
        }
        $_POST['screenshots'] = $filename;
    }else {
        $_POST['screenshots'] = $screenshots_old;
    }
    
    
    if (isset($_FILES['google_play']) && !empty($_FILES['google_play']) && $_FILES['google_play']['error'] == 0 && $_FILES['google_play']['size'] > 0) {
        $google_play = $db->escapeString($fn->xss_clean($_FILES['google_play']['name']));
        $extension = pathinfo($_FILES["google_play"]["name"])['extension'];
        $mimetype = mime_content_type($_FILES["google_play"]["tmp_name"]);
        if (!in_array($mimetype, array('image/jpg','image/jpeg', 'image/gif', 'image/png'))) {
            echo "<p class='alert alert-danger'>Image type must jpg, jpeg, gif, or png!</p>";
            return false;
        }
        
        $target_path = '../dist/img/';
        if(!empty($google_play_old) && $google_play_old != ''){
            unlink($target_path.$google_play_old);
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
      
        if (!move_uploaded_file($_FILES["google_play"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load Google Play Image!</p>";
            return false;
        }
        $_POST['google_play'] = $filename;
    }else {
        $_POST['google_play'] = $google_play_old;
    }
    
    if(empty($res)){
        $settings_value = json_encode($fn->xss_clean_array($_POST));
        $sql = "INSERT INTO settings (`variable`,`value`) VALUES ('front_end_settings','$settings_value ')";
        if ($db->sql($sql)) {
            echo "<p class='alert alert-success'>Saved Successfully!</p>";
        } else {
            echo "<p class='alert alert-danger'>Something went wrong please try again!</p>";
        }
    }else{
        // if(!empty($favicon_old) && $favicon_old != ''){
        //     $_POST['favicon'] = $favicon_old;
        // }
        // if(!empty($screenshots_old) && $screenshots_old != ''){
        //     $_POST['screenshots'] = $screenshots_old;
        // }
        // if(!empty($google_play_old) && $google_play_old != ''){
        //     $_POST['google_play'] = $google_play_old;
        // }
        $settings_value = json_encode($fn->xss_clean_array($_POST));
        $sql = "UPDATE settings SET value='" . $settings_value . "' WHERE variable='front_end_settings'";
        if ($db->sql($sql)) {
            echo "<p class='alert alert-success'>Saved Successfully!</p>";
        } else {
            echo "<p class='alert alert-danger'>Something went wrong please try again!</p>";
        }
    }
    
}



if (isset($_POST['add_delivery_boy']) && $_POST['add_delivery_boy'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['delivery_boys']['create'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to create delivery boy</label>';
        return false;
    }
    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    $mobile = $db->escapeString($fn->xss_clean($_POST['mobile']));
    $address = $db->escapeString($fn->xss_clean($_POST['address']));
    $bonus = $db->escapeString($fn->xss_clean($_POST['bonus']));
    $dob = $db->escapeString($fn->xss_clean($_POST['dob']));
    $bank_name = $db->escapeString($fn->xss_clean($_POST['bank_name'])); 
    $other_payment_info = ( isset($_POST['other_payment_info']) && !empty(trim($_POST['other_payment_info'])) ) ? $db->escapeString(trim($_POST['other_payment_info'])) : '';
    $account_number = $db->escapeString($fn->xss_clean($_POST['account_number']));
    $account_name = $db->escapeString($fn->xss_clean($_POST['account_name']));
    $ifsc_code = $db->escapeString($fn->xss_clean($_POST['ifsc_code']));
    $password = $db->escapeString($fn->xss_clean($_POST['password']));
    $password = md5($password);
    $sql = 'SELECT id FROM delivery_boys WHERE mobile=' . $mobile;
    $db->sql($sql);
    $res = $db->getResult();
    $count = $db->numRows($res);
    if ($count > 0) {
        echo '<label class="alert alert-danger">Mobile Number Already Exists!</label>';
        return false;
    }
    $target_path = '../upload/delivery-boy/';
    if($_FILES['driving_license']['error'] == 0 && $_FILES['driving_license']['size'] > 0){
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        $extension = pathinfo($_FILES["driving_license"]["name"])['extension'];
        
        $mimetype = mime_content_type($_FILES["driving_license"]["tmp_name"]);
        if (!in_array($mimetype, array('image/jpg','image/jpeg', 'image/gif', 'image/png'))) {
            echo " <span class='label label-danger'>Driving License image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $dr_filename = microtime(true).'.'. strtolower($extension);
        $dr_full_path = $target_path."".$dr_filename;
        if(!move_uploaded_file($_FILES["driving_license"]["tmp_name"], $dr_full_path)){
            echo "<p class='alert alert-danger'>Invalid directory to load image!</p>";
            return false;
        }
    }
    if($_FILES['national_identity_card']['error'] == 0 && $_FILES['national_identity_card']['size'] > 0){
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        $extension = pathinfo($_FILES["national_identity_card"]["name"])['extension'];
        
        $mimetype = mime_content_type($_FILES["national_identity_card"]["tmp_name"]);
        if (!in_array($mimetype, array('image/jpg','image/jpeg', 'image/gif', 'image/png'))) {
            echo " <span class='label label-danger'>National Identity Card image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $nic_filename = microtime(true).'.'. strtolower($extension);
        $nic_full_path = $target_path."".$nic_filename;
        if(!move_uploaded_file($_FILES["national_identity_card"]["tmp_name"], $nic_full_path)){
            echo "<p class='alert alert-danger'>Invalid directory to load image!</p>";
            return false;
        }
    }
    $sql = "INSERT INTO delivery_boys (`name`,`mobile`,`password`,`address`,`bonus`, `driving_license`, `national_identity_card`, `dob`, `bank_account_number`, `bank_name`, `account_name`, `ifsc_code`,`other_payment_information`) VALUES ('$name', '$mobile', '$password', '$address','$bonus','$dr_filename', '$nic_filename', '$dob','$account_number','$bank_name','$account_name','$ifsc_code','$other_payment_info')";
    if ($db->sql($sql)) {
        echo '<label class="alert alert-success">Delivery Boy Added Successfully!</label>';
    } else {
        echo '<label class="alert alert-danger">Some Error Occrred! please try again.</label>';
    }
}
if (isset($_POST['update_delivery_boy']) && $_POST['update_delivery_boy'] == 1) {

    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['delivery_boys']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update delivery boy</label>';
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['delivery_boy_id']));
    if ($id == 104) {
        echo '<label class="alert alert-danger">Sorry you can not update this delivery boy.</label>';
        return false;
    }
    $name = $db->escapeString($fn->xss_clean($_POST['update_name']));
    $password = !empty($_POST['update_password']) ? $db->escapeString($fn->xss_clean($_POST['update_password'])) : '';
    $update_other_payment_info = !empty($_POST['update_other_payment_info']) ? $db->escapeString($fn->xss_clean($_POST['update_other_payment_info'])) : '';
    $address = $db->escapeString($fn->xss_clean($_POST['update_address']));
    $bonus = $db->escapeString($fn->xss_clean($_POST['update_bonus']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));
    $update_dob = $db->escapeString($fn->xss_clean($_POST['update_dob']));
    $update_bank_name = $db->escapeString($fn->xss_clean($_POST['update_bank_name']));
    $update_account_number = $db->escapeString($fn->xss_clean($_POST['update_account_number']));
    $update_account_name = $db->escapeString($fn->xss_clean($_POST['update_account_name']));
    $update_ifsc_code = $db->escapeString($fn->xss_clean($_POST['update_ifsc_code']));
    $password = !empty($password) ? md5($password) : '';
    $dr_image = $nic_image = "";
    if ($_FILES['update_driving_license']['size'] != 0 && $_FILES['update_driving_license']['error'] == 0 && !empty($_FILES['update_driving_license']))
    {
        //image isn't empty and update the image
        $dr_image = $db->escapeString($fn->xss_clean($_POST['dr_image1']));
        $extension = pathinfo($_FILES["update_driving_license"]["name"])['extension'];
        $mimetype = mime_content_type($_FILES["update_driving_license"]["tmp_name"]);
        if (!in_array($mimetype, array('image/jpg','image/jpeg', 'image/gif', 'image/png'))) {
            echo " <span class='label label-danger'>Driving License image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
            
        }
        $target_path = '../upload/delivery-boy/';
        $dr_filename = microtime(true).'.'. strtolower($extension);
        $dr_full_path = $target_path."".$dr_filename;
        if(!move_uploaded_file($_FILES["update_driving_license"]["tmp_name"], $dr_full_path)){
            echo '<p class="alert alert-danger">Can not upload image.</p>';
            return false;
            exit();
        }
        if(!empty($dr_image)){
            unlink($target_path.$dr_image);
        }
        $sql = "UPDATE delivery_boys SET `driving_license`='".$dr_filename."' WHERE `id`=".$id;
        $db->sql($sql);
    } 
    if ($_FILES['update_national_identity_card']['size'] != 0 && $_FILES['update_national_identity_card']['error'] == 0 && !empty($_FILES['update_national_identity_card']))
    {
        //image isn't empty and update the image
        $nic_image = $db->escapeString($_POST['nic_image']);
        $extension = pathinfo($_FILES["update_national_identity_card"]["name"])['extension'];
        $mimetype = mime_content_type($_FILES["update_national_identity_card"]["tmp_name"]);
        if (!in_array($mimetype, array('image/jpg','image/jpeg', 'image/gif', 'image/png'))) {
            echo " <span class='label label-danger'>National Identity Card image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $target_path = '../upload/delivery-boy/';
        $nic_filename = microtime(true).'.'. strtolower($extension);
        $nic_full_path = $target_path."".$nic_filename;
        if(!move_uploaded_file($_FILES["update_national_identity_card"]["tmp_name"], $nic_full_path)){
            echo '<p class="alert alert-danger">Can not upload image.</p>';
            return false;
            exit();
        }
        if(!empty($nic_image)){
            unlink($target_path.$nic_image);
        }
        $sql = "UPDATE delivery_boys SET `national_identity_card`='".$nic_filename."' WHERE `id`=".$id;
        $db->sql($sql);
    } 
        
    if (!empty($password)) {
        $sql = "Update delivery_boys set `name`='" . $name . "',password='" . $password . "',`address`='" . $address . "',`bonus`='" . $bonus . "',`status`='" . $status . "',`dob`='$update_dob',`bank_account_number`='$update_account_number',`bank_name`='$update_bank_name',`account_name`='$update_account_name',`ifsc_code`='$update_ifsc_code',`other_payment_information`='$update_other_payment_info' where `id`=" . $id;
    } else {
        $sql = "Update delivery_boys set `name`='" . $name . "',`address`='" . $address . "',`bonus`='" . $bonus . "',`status`='" . $status . "',`dob`='$update_dob',`bank_account_number`='$update_account_number',`bank_name`='$update_bank_name',`account_name`='$update_account_name',`ifsc_code`='$update_ifsc_code',`other_payment_information`='$update_other_payment_info'  where `id`=" . $id;
    }
    if ($db->sql($sql)) {
        echo "<label class='alert alert-success'>Information Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}
if (isset($_GET['delete_delivery_boy']) && $_GET['delete_delivery_boy'] == 1) {
    if ($permissions['delivery_boys']['delete'] == 0) {
        echo 2;
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_GET['id']));
    $target_path = '../upload/delivery-boy/';
    $driving_license = $db->escapeString($fn->xss_clean($_GET['driving_license']));
    $national_identity_card = $db->escapeString($fn->xss_clean($_GET['national_identity_card']));
    if ($id == 104) {
        echo 3;
        return false;
    }
    $sql = "DELETE FROM `delivery_boys` WHERE id=" . $id;
    if ($db->sql($sql)) {
        // delete fund_transfers
        $sql = "DELETE FROM `fund_transfers` WHERE delivery_boy_id=" . $id;
        $db->sql($sql);
        // delete withdrawal requests
        $sql = "DELETE FROM `withdrawal_requests` WHERE `type_id`=" . $id . " AND `type`='delivery_boy'";
        $db->sql($sql);
        if(!empty($driving_license)){
            unlink($target_path.$driving_license);
        }
        if(!empty($national_identity_card)){
            unlink($target_path.$national_identity_card);
        }
        echo 0;
    } else {
        echo 1;
    }
}
if (isset($_POST['update_payment_request']) && $_POST['update_payment_request'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['payment']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update payment request.</label>";
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['payment_request_id']));
    $remarks = $db->escapeString($fn->xss_clean($_POST['update_remarks']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));
    $sql = "select status from payment_requests where id=" . $id;
    $db->sql($sql);
    $res = $db->getResult();
    if ($res[0]['status'] == 1) {
        echo "<label class='alert alert-danger'>Payment request already approved.</label>";
        return false;
    }
    if ($res[0]['status'] == 2) {
        echo "<label class='alert alert-danger'>Payment request already cancelled.</label>";
        return false;
    }
    if ($status == '2') {
        $sql = "SELECT user_id,amount_requested FROM payment_requests WHERE id=" . $id;
        $db->sql($sql);
        $res = $db->getResult();
        $user_id = $res[0]['user_id'];
        $amount = $res[0]['amount_requested'];

        $sql = "UPDATE users SET balance = balance + $amount WHERE id=" . $user_id;
        $db->sql($sql);
    }
    $sql = "Update payment_requests set `remarks`='" . $remarks . "',`status`='" . $status . "' where `id`=" . $id;
    if ($db->sql($sql)) {
        echo "<label class='alert alert-success'>Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}
if (isset($_POST['boy_id']) && isset($_POST['transfer_fund'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['payment']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update delivery boy.</label>";
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['boy_id']));
    $balance = $db->escapeString($fn->xss_clean($_POST['delivery_boy_balance']));
    if(!is_numeric($_POST['amount'])){
        
        echo "<label class='alert alert-danger'>Amount must be number.</label>";
        return false;
    }
    $amount = $db->escapeString($fn->xss_clean($_POST['amount']));
    
    $message = (!empty($_POST['message'])) ? $db->escapeString($fn->xss_clean($_POST['message'])) : 'Fund Transferred By Admin';
    $bal = $balance - $amount;
    $sql = "Update delivery_boys set `balance`='" . $bal . "' where `id`=" . $id;
    $db->sql($sql);
    $sql = "INSERT INTO `fund_transfers` (`delivery_boy_id`,`amount`,`opening_balance`,`closing_balance`,`status`,`message`) VALUES ('" . $id . "','" . $amount . "','" . $balance . "','" . $bal . "','SUCCESS','" . $message . "')";
    $db->sql($sql);
    echo "<p class='alert alert-success'>Amount Transferred Successfully!</p>";
}
if (isset($_POST['add_promo_code']) && $_POST['add_promo_code'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['promo_codes']['create'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to create promo code</label>';
        return false;
    }
    $promo_code = $db->escapeString($fn->xss_clean($_POST['promo_code']));
    $message = $db->escapeString($fn->xss_clean($_POST['message']));
    $start_date = $db->escapeString($fn->xss_clean($_POST['start_date']));
    $end_date = $db->escapeString($fn->xss_clean($_POST['end_date']));
    $no_of_users = $db->escapeString($fn->xss_clean($_POST['no_of_users']));
    $minimum_order_amount = $db->escapeString($fn->xss_clean($_POST['minimum_order_amount']));
    $discount = $db->escapeString($fn->xss_clean($_POST['discount']));
    $discount_type = $db->escapeString($fn->xss_clean($_POST['discount_type']));
    $max_discount_amount = $db->escapeString($fn->xss_clean($_POST['max_discount_amount']));
    $repeat_usage = $db->escapeString($fn->xss_clean($_POST['repeat_usage']));
    $no_of_repeat_usage = !empty($_POST['repeat_usage']) ? $db->escapeString($fn->xss_clean($_POST['no_of_repeat_usage'])) : 0;
    $status = $db->escapeString($fn->xss_clean($_POST['status']));

    $sql = "INSERT INTO promo_codes (promo_code,message,start_date,end_date,no_of_users,minimum_order_amount,discount,discount_type,max_discount_amount,repeat_usage,no_of_repeat_usage,status)
                        VALUES('$promo_code', '$message', '$start_date', '$end_date','$no_of_users','$minimum_order_amount','$discount','$discount_type','$max_discount_amount','$repeat_usage','$no_of_repeat_usage','$status')";
    if ($db->sql($sql)) {
        echo '<label class="alert alert-success">Promo Code Added Successfully!</label>';
    } else {
        echo '<label class="alert alert-danger">Some Error Occrred! please try again.</label>';
    }
}
if (isset($_POST['update_promo_code']) && $_POST['update_promo_code'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['promo_codes']['update'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to update promo code</label>';
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['promo_code_id']));
    $promo_code = $db->escapeString($fn->xss_clean($_POST['update_promo']));
    $message = $db->escapeString($fn->xss_clean($_POST['update_message']));
    $start_date = $db->escapeString($fn->xss_clean($_POST['update_start_date']));
    $end_date = $db->escapeString($fn->xss_clean($_POST['update_end_date']));
    $no_of_users = $db->escapeString($fn->xss_clean($_POST['update_no_of_users']));
    $minimum_order_amount = $db->escapeString($fn->xss_clean($_POST['update_minimum_order_amount']));
    $discount = $db->escapeString($fn->xss_clean($_POST['update_discount']));
    $discount_type = $db->escapeString($fn->xss_clean($_POST['update_discount_type']));
    $max_discount_amount = $db->escapeString($fn->xss_clean($_POST['update_max_discount_amount']));
    $repeat_usage = $db->escapeString($fn->xss_clean($_POST['update_repeat_usage']));
    $no_of_repeat_usage = $repeat_usage == 0 ? '0' : $db->escapeString($fn->xss_clean($_POST['update_no_of_repeat_usage']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));

    $sql = "Update promo_codes set `promo_code`='" . $promo_code . "',`message`='" . $message . "',`start_date`='" . $start_date . "',`end_date`='" . $end_date . "',`no_of_users`='" . $no_of_users . "',`minimum_order_amount`='" . $minimum_order_amount . "',`discount`='" . $discount . "',`discount_type`='" . $discount_type . "',`max_discount_amount`='" . $max_discount_amount . "',`repeat_usage`='" . $repeat_usage . "',`no_of_repeat_usage`='" . $no_of_repeat_usage . "',`status`='" . $status . "' where `id`=" . $id;

    if ($db->sql($sql)) {
        echo "<label class='alert alert-success'>Promo Code Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}
if (isset($_GET['delete_promo_code']) && $_GET['delete_promo_code'] == 1) {
    if ($permissions['promo_codes']['delete'] == 0) {
        echo 2;
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_GET['id']));
    $sql = "DELETE FROM `promo_codes` WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo 0;
    } else {
        echo 1;
    }
}
if (isset($_POST['add_time_slot']) && $_POST['add_time_slot'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['settings']['update'] == 0) {

        echo '<label class="alert alert-danger">You have no permission to add time slot</label>';
        return false;
    }
    $title = $db->escapeString($fn->xss_clean($_POST['title']));
    $from_time = $db->escapeString($fn->xss_clean($_POST['from_time']));
    $to_time = $db->escapeString($fn->xss_clean($_POST['to_time']));
    $last_order_time = $db->escapeString($fn->xss_clean($_POST['last_order_time']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));
    $sql = "INSERT INTO time_slots (title,from_time,to_time,last_order_time,status)
                        VALUES('$title', '$from_time', '$to_time', '$last_order_time','$status')";
    if ($db->sql($sql)) {
        echo '<label class="alert alert-success">Time Slot Added Successfully!</label>';
    } else {
        echo '<label class="alert alert-danger">Some Error Occrred! please try again.</label>';
    }
}
if (isset($_POST['update_time_slot']) && $_POST['update_time_slot'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['settings']['update'] == 0) {

        echo '<label class="alert alert-danger">You have no permission to update time slot</label>';
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['time_slot_id']));
    $title = $db->escapeString($fn->xss_clean($_POST['update_title']));
    $from_time = $db->escapeString($fn->xss_clean($_POST['update_from_time']));
    $to_time = $db->escapeString($fn->xss_clean($_POST['update_to_time']));
    $last_order_time = $db->escapeString($fn->xss_clean($_POST['update_last_order_time']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));
    $sql = "Update time_slots set `title`='" . $title . "',`from_time`='" . $from_time . "',`to_time`='" . $to_time . "',`last_order_time`='" . $last_order_time . "',`status`='" . $status . "' where `id`=" . $id;
    if ($db->sql($sql)) {
        echo "<label class='alert alert-success'>Time Slot Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}
if (isset($_GET['delete_time_slot']) && $_GET['delete_time_slot'] == 1) {
    if ($permissions['settings']['update'] == 0) {

        echo 2;
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_GET['id']));
    $sql = "DELETE FROM `time_slots` WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo 0;
    } else {
        echo 1;
    }
}
if (isset($_POST['update_return_request']) && $_POST['update_return_request'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['return_requests']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update return request.</label>";
        return false;
    }

    $id = $db->escapeString($fn->xss_clean($_POST['return_request_id']));
    $order_item_id = $db->escapeString($fn->xss_clean($_POST['order_item_id']));
    $order_id = $db->escapeString($fn->xss_clean($_POST['order_id']));
    $remarks = $db->escapeString($fn->xss_clean($_POST['update_remarks']));
    $return_status = $db->escapeString($fn->xss_clean($_POST['status']));
    $sql = "select status from return_requests where id=" . $id;
    $db->sql($sql);
    $res = $db->getResult();
    if ($res[0]['status'] == 1) {
        echo "<label class='alert alert-danger'>Return request already approved.</label>";
        return false;
    }
    if ($return_status == 1) {
        $sql = "SELECT user_id,status,sub_total FROM order_items WHERE id =" . $order_item_id;
        $db->sql($sql);
        $result = $db->getResult();
        $status = json_decode($result[0]['status']);
        $status[] = array('returned', date("d-m-Y h:i:sa"));
        $status = $db->escapeString(json_encode($status));
        $sql = "UPDATE order_items SET status = '" . $status . "', active_status = 'returned' WHERE id = " . $order_item_id;
        $db->sql($sql);

        /* check for other item status and summery of order */
        $sql = "SELECT id FROM order_items WHERE order_id=" . $order_id;
        $db->sql($sql);
        $total = $db->numRows();
        $sql = "SELECT id FROM `order_items` WHERE order_id=" . $order_id . " && (`active_status` LIKE '%cancelled%' OR `active_status` LIKE '%returned%' )";
        $db->sql($sql);
        $returned = $db->numRows();
        if ($returned == $total) {
            $sql = "SELECT status FROM orders WHERE id =" . $order_id;
            $db->sql($sql);
            $res = $db->getResult();
            $status_order = json_decode($res[0]['status']);
            $status_order[] = array('returned', date("d-m-Y h:i:sa"));
            $status_order = $db->escapeString(json_encode($status_order));
            $sql = "UPDATE orders SET status = '" . $status_order . "', active_status = 'returned' WHERE id = " . $order_item_id;
            $db->sql($sql);
        }

        $sql = 'SELECT oi.`product_variant_id`,oi.`quantity`,oi.`discounted_price`,oi.`price`,pv.`product_id`,pv.`type`,pv.`stock`,pv.`stock_unit_id`,pv.`measurement`,pv.`measurement_unit_id` FROM `order_items` oi join `product_variant` pv on pv.id = oi.product_variant_id WHERE oi.`id`=' . $order_item_id;

        $db->sql($sql);
        $res_oi = $db->getResult();
        if ($res_oi[0]['type'] == 'packet') {
            $sql = "UPDATE product_variant SET stock = stock + " . $res_oi[0]['quantity'] . " WHERE id='" . $res_oi[0]['product_variant_id'] . "'";
            $db->sql($sql);

            $sql = "select stock from product_variant where id=" . $res_oi[0]['product_variant_id'];
            $db->sql($sql);
            $res_stock = $db->getResult();
            if ($res_stock[0]['stock'] > 0) {
                $sql = "UPDATE product_variant set serve_for='Available' WHERE id='" . $res_oi[0]['product_variant_id'] . "'";
                $db->sql($sql);
            }
        } else {
            /* When product type is loose */
            if ($res_oi[0]['measurement_unit_id'] != $res_oi[0]['stock_unit_id']) {
                $stock = $fn->convert_to_parent($res_oi[0]['measurement'], $res_oi[0]['measurement_unit_id']);
                $stock = $stock * $res_oi[0]['quantity'];
                $sql = "UPDATE product_variant SET stock = stock + " . $stock . " WHERE product_id='" . $res_oi[0]['product_id'] . "'";
                $db->sql($sql);
            } else {
                $stock = $res_oi[0]['measurement'] * $res_oi[0]['quantity'];
                $sql = "UPDATE product_variant SET stock = stock + " . $stock . " WHERE product_id='" . $res_oi[0]['product_id'] . "'";
                $db->sql($sql);
            }
            $sql = "select stock from product_variant where product_id=" . $res_oi[0]['product_id'];
            $db->sql($sql);
            $res_stck = $db->getResult();
            if ($res_stck[0]['stock'] > 0) {
                $sql = "UPDATE product_variant set serve_for='Available' WHERE product_id='" . $res_oi[0]['product_id'] . "'";
                $db->sql($sql);
            }
        }
        /* update user's wallet */
        $total = $res_oi[0]['discounted_price'] == 0 ? $res_oi[0]['price'] * $res_oi[0]['quantity'] : $res_oi[0]['discounted_price'] * $res_oi[0]['quantity'];
        $sql = "select user_id from return_requests where id=" . $id;
        $db->sql($sql);
        $res_user = $db->getResult();
        $user_id = $res_user[0]['user_id'];
        $sql = "update users set balance = balance + $total where id=" . $user_id;
        $db->sql($sql);
        /* add wallet transaction */
        $sql = "insert into wallet_transactions (`order_id`,`user_id`,`type`,`amount`,`message`,`status`)values(" . $order_id . "," . $user_id . ",'credit'," . $total . ",'Balance credited on return request approved.',1)";
        $db->sql($sql);
    }
    $sql_query = "Update return_requests set `remarks`='" . $remarks . "',`status`='" . $return_status . "' where `id`=" . $id;
    if ($db->sql($sql_query)) {
        echo "<label class='alert alert-success'>Return request updated successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}
if (isset($_GET['delete_return_request']) && $_GET['delete_return_request'] == 1) {
    if ($permissions['return_requests']['delete'] == 0) {
        echo 2;
        return false;
    }
    $id = $db->escapeString($_GET['id']);
    $sql = "DELETE FROM `return_requests` WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo 0;
    } else {
        echo 1;
    }
}
if (isset($_POST['manage_customer_wallet']) && isset($_POST['user_id'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['customers']['read'] == 0) {
        echo '<label class="alert alert-danger">You have no permission to manage wallet balance</label>';
        return false;
    }

    $user_id = $db->escapeString($fn->xss_clean($_POST['user_id']));
    $amount = $db->escapeString($fn->xss_clean($_POST['amount']));
    $type = $db->escapeString($fn->xss_clean($_POST['type']));
    $message = !empty(trim($_POST['message'])) ? $db->escapeString(trim($fn->xss_clean($_POST['message']))) : 'Transaction by admin';

    $balance = $fn->get_wallet_balance($user_id);
    if ($type == 'debit' && $balance <= 0) {
        echo "<label class='alert alert-danger'>Balance should be greater than 0.</label>";
        return false;
    }
    if ($type == 'debit' && $amount > $balance) {
        echo "<label class='alert alert-danger'>Amount should not be greater than balance.</label>";
        return false;
    }
    $new_balance = $type == 'credit' ? $balance + $amount : $balance - $amount;
    $fn->update_wallet_balance($new_balance, $user_id);
    if ($fn->add_wallet_transaction($order_id = "",$user_id, $type, $amount, $message)) {
        echo "<label class='alert alert-success'>Balance Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}
if (isset($_POST['add_system_user']) && $_POST['add_system_user'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_SESSION['id'];
    $username = $db->escapeString($fn->xss_clean($_POST['username']));
    $email = $db->escapeString($fn->xss_clean($_POST['email']));
    if (empty($email)) {
        echo " <label class='alert alert-danger'>Email required!</label>";
        return false;
    }
    $valid_mail = "/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i";
    if (!preg_match($valid_mail, $email)) {
        echo " <label class='alert alert-danger'>Wrong email format!</label>";
        return false;
    }  
    
    $password = $db->escapeString($fn->xss_clean($_POST['password']));
    $password = md5($password);
    $role = $db->escapeString($fn->xss_clean($_POST['role']));


    $sql = "SELECT id FROM admin WHERE username='" . $username . "'";
    $db->sql($sql);
    $res = $db->getResult();
    $count = $db->numRows($res);
    if ($count > 0) {
        echo '<label class="alert alert-danger">Username Already Exists!</label>';
        return false;
    }

    $sql = "SELECT id FROM admin WHERE email='" . $email . "'";
    $db->sql($sql);
    $res = $db->getResult();
    $count = $db->numRows($res);
    if ($count > 0) {
        echo '<label class="alert alert-danger">Email Already Exists!</label>';
        return false;
    }
    $permissions['orders'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-order'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-order'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-order'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-order'])));

    $permissions['categories'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-category'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-category'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-category'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-category'])));

    $permissions['subcategories'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-subcategory'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-subcategory'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-subcategory'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-subcategory'])));

    $permissions['products'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-product'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-product'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-product'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-product'])));

    $permissions['products_order'] = array("read" => $db->escapeString($fn->xss_clean($_POST['is-read-products-order'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-products-order'])));

    $permissions['home_sliders'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-home-slider'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-home-slider'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-home-slider'])));

    $permissions['new_offers'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-new-offer'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-new-offer'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-new-offer'])));

    $permissions['promo_codes'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-promo'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-promo'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-promo'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-promo'])));

    $permissions['featured'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-featured'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-featured'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-featured'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-featured'])));

    $permissions['customers'] = array("read" => $db->escapeString($fn->xss_clean($_POST['is-read-customers'])));

    $permissions['payment'] = array("read" => $db->escapeString($fn->xss_clean($_POST['is-read-payment'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-payment'])));

    $permissions['return_requests'] = array("read" => $db->escapeString($fn->xss_clean($_POST['is-read-return'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-return'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-return'])));

    $permissions['delivery_boys'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-delivery'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-delivery'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-delivery'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-delivery'])));

    $permissions['notifications'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-notification'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-notification'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-notification'])));

    $permissions['transactions'] = array("read" => $db->escapeString($fn->xss_clean($_POST['is-read-transaction'])));

    $permissions['settings'] = array("read" => $db->escapeString($fn->xss_clean($_POST['is-read-settings'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-settings'])));

    $permissions['locations'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-location'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-location'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-location'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-location'])));

    $permissions['reports'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-report'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-report'])));

    $permissions['faqs'] = array("create" => $db->escapeString($fn->xss_clean($_POST['is-create-faq'])), "read" => $db->escapeString($fn->xss_clean($_POST['is-read-faq'])), "update" => $db->escapeString($fn->xss_clean($_POST['is-update-faq'])), "delete" => $db->escapeString($fn->xss_clean($_POST['is-delete-faq'])));

    $encoded_permissions = json_encode($permissions);
    $sql = "INSERT INTO admin (username,email,password,role,permissions,created_by)
                        VALUES('$username', '$email', '$password', '$role','$encoded_permissions','$id')";
    if ($db->sql($sql)) {
        echo '<label class="alert alert-success">' . $role . ' Added Successfully!</label>';
    } else {
        echo '<label class="alert alert-danger">Some Error Occrred! please try again.</label>';
    }
}
if (isset($_GET['delete_system_user']) && $_GET['delete_system_user'] == 1) {
    $id = $db->escapeString($_GET['id']);
    $sql = "DELETE FROM `admin` WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo 0;
    } else {
        echo 1;
    }
}
if (isset($_POST['update_system_user']) && $_POST['update_system_user'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['system_user_id']));
    $permissions['orders'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-order'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-order'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-order'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-order'])));

    $permissions['categories'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-category'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-category'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-category'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-category'])));

    $permissions['subcategories'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-subcategory'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-subcategory'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-subcategory'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-subcategory'])));

    $permissions['products'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-product'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-product'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-product'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-product'])));

    $permissions['products_order'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-products-order'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-products-order'])));

    $permissions['home_sliders'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-home-slider'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-home-slider'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-home-slider'])));

    $permissions['new_offers'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-new-offer'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-new-offer'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-new-offer'])));

    $permissions['promo_codes'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-promo'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-promo'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-promo'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-promo'])));

    $permissions['featured'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-featured'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-featured'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-featured'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-featured'])));

    $permissions['customers'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-customers'])));

    $permissions['payment'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-payment'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-payment'])));

    $permissions['return_requests'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-return'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-return'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-return'])));

    $permissions['delivery_boys'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-delivery'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-delivery'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-delivery'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-delivery'])));

    $permissions['notifications'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-notification'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-notification'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-notification'])));

    $permissions['transactions'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-transaction'])));

    $permissions['fund_transfer_delivery_boy'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-fund'])));

    $permissions['settings'] = array("read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-settings'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-settings'])));

    $permissions['locations'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-location'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-location'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-location'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-location'])));

    $permissions['reports'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-report'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-report'])));

    $permissions['faqs'] = array("create" => $db->escapeString($fn->xss_clean($_POST['permission-is-create-faq'])), "read" => $db->escapeString($fn->xss_clean($_POST['permission-is-read-faq'])), "update" => $db->escapeString($fn->xss_clean($_POST['permission-is-update-faq'])), "delete" => $db->escapeString($fn->xss_clean($_POST['permission-is-delete-faq'])));

    $permissions = json_encode($permissions);
    $sql = "UPDATE admin SET permissions='" . $permissions . "' WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo '<label class="alert alert-success">Updated Successfully!</label>';
    } else {
        echo '<label class="alert alert-danger">Some Error Occrred! please try again.</label>';
    }
}

if (isset($_POST['update_withdrawal_request']) && $_POST['update_withdrawal_request'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['return_requests']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update withdrawal request.</label>";
        return false;
    }

    $id = $db->escapeString($fn->xss_clean($_POST['withdrawal_request_id']));
    $type1 = $db->escapeString($fn->xss_clean($_POST['type']));
    $type = $type1 == 'Delivery Boy' ? 'delivery_boy' : 'user';
    $type_id = $db->escapeString($fn->xss_clean($_POST['type_id']));
    $amount = $db->escapeString($fn->xss_clean($_POST['amount']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));
    $sql = "select status from withdrawal_requests where id=" . $id;
    $db->sql($sql);
    $res = $db->getResult();
    if ($res[0]['status'] == 1) {
        echo "<label class='alert alert-danger'>Withdrawal request already approved.</label>";
        return false;
    }
    if ($res[0]['status'] == 2) {
        echo "<label class='alert alert-danger'>Withdrawal request already cancelled.</label>";
        return false;
    }
    if ($status == 2) {
        if ($type == 'user') {
            $balance = $fn->get_wallet_balance($type_id);
            $new_balance = $balance + $amount;
            $fn->update_wallet_balance($new_balance, $type_id);
            $fn->add_wallet_transaction($order_id = "", $type_id, 'credit', $amount, 'Balance credited on withdrawal request cancellation.');
        }
        if ($type == 'delivery_boy') {
            $balance = $fn->get_balance($type_id);
            $new_balance = $balance + $amount;
            $fn->update_delivery_boy_wallet_balance($new_balance, $type_id);
            $sql = "INSERT INTO `fund_transfers` (`delivery_boy_id`,`type`,`amount`,`opening_balance`,`closing_balance`,`status`,`message`) VALUES ('" . $type_id . "','credit','" . $amount . "','" . $balance . "','" . $new_balance . "','SUCCESS','Balance credited on withdrawal request cancellation.')";
            $db->sql($sql);
        }
    }
    $sql_query = "Update withdrawal_requests set `status`='" . $status . "' where `id`=" . $id;
    if ($db->sql($sql_query)) {
        echo "<label class='alert alert-success'>Withdrawal request updated successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}

if (isset($_GET['delete_withdrawal_request']) && $_GET['delete_withdrawal_request'] == 1) {
    if ($permissions['return_requests']['delete'] == 0) {
        echo 2;
        return false;
    }
    $id = $db->escapeString($_GET['id']);
    $sql = "DELETE FROM `withdrawal_requests` WHERE id=" . $id;
    if ($db->sql($sql)) {
        echo 0;
    } else {
        echo 1;
    }
}

// upload bulk product - upload products in bulk using  a CSV file
if (isset($_POST['bulk_upload']) && $_POST['bulk_upload'] == 1 && (isset($_POST['type']) && $_POST['type'] == 'products')) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['products']['create'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to upload products.</label>";
        return false;
    }
    $count = 0;
    $count1 = 0;
    $error = false;
    $filename = $_FILES["upload_file"]["tmp_name"];
    $mimetype = mime_content_type($_FILES["upload_file"]["tmp_name"]);
    if (!in_array($mimetype, array('text/plain'))) {
        $error = true;        
    }
    $allowed_status = array("received", "processed", "shipped");
    if ($_FILES["upload_file"]["size"] > 0  && $error == false) {
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // product name
                $emapData[1] = trim($db->escapeString($emapData[1])); // category id
                $emapData[2] = trim($db->escapeString($emapData[2])); // subcategory id
                $emapData[3] = trim($db->escapeString($emapData[3])); // indicator
                $emapData[4] = trim($db->escapeString($emapData[4])); // manufacturer
                $emapData[5] = trim($db->escapeString($emapData[5])); // made in
                $emapData[6] = trim($db->escapeString($emapData[6])); // return status
                $emapData[7] = trim($db->escapeString($emapData[7])); // cancel status
                $emapData[8] = trim($db->escapeString($emapData[8])); // till status
                $emapData[9] = trim($db->escapeString($emapData[9])); // description
                if (empty($emapData[0])) {
                    echo '<p class="alert alert-danger">Product Name  is empty at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[1])) {
                    echo '<p class="alert alert-danger">Category ID  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[2])) {
                    echo '<p class="alert alert-danger">Subcategory ID  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[6]) && $emapData[6] != 1) {
                    echo '<p class="alert alert-danger">Is Returnable is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[7]) && $emapData[7] != 1) {
                    echo '<p class="alert alert-danger">Is cancel-able is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[7]) && $emapData[7] == 1 && (empty($emapData[8]) || !in_array($emapData[8], $allowed_status))) {
                    echo '<p class="alert alert-danger">Till status is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[7]) && !(empty($emapData[8]))) {
                    echo '<p class="alert alert-danger">Till status is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[9])) {
                    echo '<p class="alert alert-danger">Description  is empty at row - ' . $count . '</div>';
                    return false;
                }
            }
            $count++;
        }
        fclose($file);
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count1 != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // product name
                $emapData[1] = trim($db->escapeString($emapData[1])); // category id
                $emapData[2] = trim($db->escapeString($emapData[2])); // subcategory id
                $emapData[3] = trim($db->escapeString($emapData[3])); // indicator
                $emapData[4] = trim($db->escapeString($emapData[4])); // manufacturer
                $emapData[5] = trim($db->escapeString($emapData[5])); // made in
                $emapData[6] = !empty($emapData[6])?trim($db->escapeString($emapData[6])):0; // return status
                $emapData[7] = !empty($emapData[7])?trim($db->escapeString($emapData[7])):0; // cancel status
                $emapData[8] = trim($db->escapeString($emapData[8])); // till status
                $emapData[9] = trim($db->escapeString($emapData[9])); // description
                $slug = $function->slugify($emapData[0]);
                $sql = "INSERT INTO products (`name`,`slug`,`category_id`,`subcategory_id`,`indicator`,`manufacturer`,`made_in`,`return_status`,`cancelable_status`,`till_status`,`description`) VALUES ('" . $emapData[0] . "','" . $slug . "','" . $emapData[1] . "','" . $emapData[2] . "','" . $emapData[3] . "','" . $emapData[4] . "','" . $emapData[5] . "','" . $emapData[6] . "','" . $emapData[7] . "','" . $emapData[8] . "','" . $emapData[9] . "')";
                $db->sql($sql);
            }

            $count1++;
        }
        fclose($file);
        echo "<p class='alert alert-success'>CSV file is successfully imported!</p><br>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p><br>";
    }
}
// upload bulk product - upload products in bulk using  a CSV file
if (isset($_POST['bulk_update']) && $_POST['bulk_update'] == 1 && (isset($_POST['type']) && $_POST['type'] == 'products')) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['products']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update products.</label>";
        return false;
    }
    $count = 0;
    $count1 = 0;
    $filename = $_FILES["upload_file"]["tmp_name"];
    $error = false;
    $mimetype = mime_content_type($_FILES["upload_file"]["tmp_name"]);
    if (!in_array($mimetype, array('text/plain'))) {
        $error = true;        
    }
    $allowed_status = array("received", "processed", "shipped");
    if ($_FILES["upload_file"]["size"] > 0  && $error == false) {
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // product ID
                $emapData[1] = trim($db->escapeString($emapData[1])); // product name
                $emapData[2] = trim($db->escapeString($emapData[2])); // category id
                $emapData[3] = trim($db->escapeString($emapData[3])); // subcategory id
                $emapData[4] = trim($db->escapeString($emapData[4])); // indicator
                $emapData[5] = trim($db->escapeString($emapData[5])); // manufacturer
                $emapData[6] = trim($db->escapeString($emapData[6])); // made in
                $emapData[7] = trim($db->escapeString($emapData[7])); // return status
                $emapData[8] = trim($db->escapeString($emapData[8])); // cancel status
                $emapData[9] = trim($db->escapeString($emapData[9])); // till status
                $emapData[10] = trim($db->escapeString($emapData[10])); // description
                // return false;
                if (empty($emapData[0])) {
                    echo '<p class="alert alert-danger">Product ID  is empty at row - ' . $count . '</div>';
                    return false;
                }
                $sql = "SELECT * FROM products WHERE id=".$emapData[0];
                $db->sql($sql);
                $result = $db->getResult();
                if (empty($result)) {
                    echo '<p class="alert alert-danger">Product ID  is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[7]) && $emapData[7] != 1) {
                    echo '<p class="alert alert-danger">Is Returnable is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[8]) && $emapData[8] != 1) {
                    echo '<p class="alert alert-danger">Is cancel-able is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[8]) && $emapData[8] == 1 && (empty($emapData[9]) || !in_array($emapData[9], $allowed_status))) {
                    echo '<p class="alert alert-danger">Till status is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[8]) && !(empty($emapData[9]))) {
                    echo '<p class="alert alert-danger">Till status is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[8]) && (empty($emapData[9]))) {
                    echo '<p class="alert alert-danger">Till status is invalid or empty at row - ' . $count . '</div>';
                    return false;
                }
            }
            $count++;
        }
        fclose($file);
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count1 != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // product ID
                $sql = "SELECT * FROM products WHERE id=".$emapData[0];
                $db->sql($sql);
                $result = $db->getResult();
                $emapData[1] = !empty($emapData[1])?trim($db->escapeString($emapData[1])):$result[0]['name']; // product name
                $emapData[2] = !empty($emapData[2])?trim($db->escapeString($emapData[2])):$result[0]['category_id']; // category id
                $emapData[3] = !empty($emapData[3])?trim($db->escapeString($emapData[3])):$result[0]['subcategory_id']; // subcategory id
                $emapData[4] = !empty($emapData[4])?trim($db->escapeString($emapData[4])):$result[0]['indicator']; // indicator
                $emapData[5] = !empty($emapData[5])?trim($db->escapeString($emapData[5])):$result[0]['manufacturer']; // manufacturer
                $emapData[6] = !empty($emapData[6])?trim($db->escapeString($emapData[6])):$result[0]['made_in']; // made in
                $emapData[7] = !empty($emapData[7])?trim($db->escapeString($emapData[7])):$result[0]['return_status']; // return status
                $emapData[8] = trim($db->escapeString($emapData[8])); // cancel status
                $emapData[9] = !empty($emapData[8])?trim($db->escapeString($emapData[9])):''; // till status
                $emapData[10] = !empty($emapData[10])?trim($db->escapeString($emapData[10])):$result[0]['description']; // description
                $slug = !empty($emapData[1])?$function->slugify($emapData[1]):$result[0]['slug'];
                $sql = "UPDATE products SET `name`='".$emapData[1]."',`slug`='".$slug."',`category_id`='".$emapData[2]."',`subcategory_id`='".$emapData[3]."',`indicator`='".$emapData[4]."',`manufacturer`='".$emapData[5]."',`made_in`='".$emapData[6]."',`return_status`='".$emapData[7]."',`cancelable_status`='".$emapData[8]."',`till_status`='".$emapData[9]."',`description`='".$emapData[10]."' WHERE id=".$emapData[0];
                $db->sql($sql);
            }

            $count1++;
        }
        fclose($file);
        echo "<p class='alert alert-success'>CSV file is successfully imported!</p><br>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p><br>";
    }
}
// upload bulk product variants- upload product variants in bulk using  a CSV file
if (isset($_POST['bulk_upload']) && $_POST['bulk_upload'] == 1 && (isset($_POST['type']) && $_POST['type'] == 'variants')) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['products']['create'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to upload products.</label>";
        return false;
    }
    $count = 0;
    $count1 = 0;
    $filename = $_FILES["upload_file"]["tmp_name"];
    $error = false;
    $mimetype = mime_content_type($_FILES["upload_file"]["tmp_name"]);
    if (!in_array($mimetype, array('text/plain'))) {
        $error = true;        
    }
    if ($_FILES["upload_file"]["size"] > 0  && $error == false) {
        $file = fopen($filename, "r");
        $emptydata = false;
        $invalid_price = false;
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // type
                $emapData[1] = trim($db->escapeString($emapData[1])); // measurement
                $emapData[2] = trim($db->escapeString($emapData[2])); // measurement unit id
                $emapData[3] = trim($db->escapeString($emapData[3])); // price
                $emapData[4] = trim($db->escapeString($emapData[4])); // discounted price
                $emapData[5] = trim($db->escapeString($emapData[5])); // serve for
                $emapData[6] = trim($db->escapeString($emapData[6])); // stock
                $emapData[7] = trim($db->escapeString($emapData[7])); // stock unit id
                $emapData[8] = trim($db->escapeString($emapData[8])); // product id
                
                if (empty($emapData[0]) || ($emapData[0] != 'packet' && $emapData[0] != 'loose')) {
                    $emptydata = true;
                    echo '<p class="alert alert-danger">Type  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[1])) {
                    $emptydata = true;
                    echo '<p class="alert alert-danger">Measurement  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                $sql = "SELECT id FROM unit";
                $db->sql($sql);
                $ids = $db->getResult();
                $invalid_measurement_unit=1;
                foreach($ids as $id){
                    if($emapData[2]==$id['id']){
                        $invalid_measurement_unit=0;
                    }
                }
                if(empty($emapData[2]) || $invalid_measurement_unit==1){
                    echo '<p class="alert alert-danger">Measurement Unit ID is empty or invalid at row - ' . $count . '</div>';
                    return false;
                } 
                if (empty($emapData[3]) || $emapData[3] <= $emapData[4]) {
                    $emptydata = true;
                    echo '<p class="alert alert-danger">Price is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[5]) || ($emapData[5] != 'Available' && $emapData[5] != 'Sold Out')) {
                    $emptydata = true;
                    echo '<p class="alert alert-danger">Serve For  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                $invalid_stock_unit=0;
                foreach($ids as $id){
                    if($emapData[7]==$id['id']){
                        $invalid_stock_unit=0;
                    }
                }
                if(empty($emapData[7]) || $invalid_stock_unit==1){
                    echo '<p class="alert alert-danger">Stock Unit ID is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[8])) {
                    $emptydata = true;
                    echo '<p class="alert alert-danger">Product ID is empty at row - ' . $count . '</div>';
                    return false;
                }
                $sql = "SELECT id FROM products WHERE id=".$emapData[8];
                $db->sql($sql);
                $result = $db->getResult();
                if (empty($result)) {
                    echo '<p class="alert alert-danger">Product ID  is invalid at row - ' . $count . '</div>';
                    return false;
                }
            }
            $count++;
        }
        fclose($file);
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count1 != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // type
                $emapData[1] = trim($db->escapeString($emapData[1])); // measurement
                $emapData[2] = trim($db->escapeString($emapData[2])); // measurement unit id
                $emapData[3] = trim($db->escapeString($emapData[3])); // price
                $emapData[4] = trim($db->escapeString($emapData[4])); // discounted price
                $emapData[5] = trim($db->escapeString($emapData[5])); // serve for
                $emapData[6] = trim($db->escapeString($emapData[6])); // stock
                $emapData[7] = trim($db->escapeString($emapData[7])); // stock unit id
                $emapData[8] = trim($db->escapeString($emapData[8])); // product id
                $sql = "INSERT INTO product_variant (`product_id`,`type`,`measurement`,`measurement_unit_id`,`price`,`discounted_price`,`serve_for`,`stock`,`stock_unit_id`) VALUES ('" . $emapData[8] . "','" . $emapData[0] . "','" . $emapData[1] . "','" . $emapData[2] . "','" . $emapData[3] . "','" . $emapData[4] . "','" . $emapData[5] . "','" . $emapData[6] . "','" . $emapData[7] . "')";
                $db->sql($sql);
            }

            $count1++;
        }
        fclose($file);
        echo "<p class='alert alert-success'>CSV file is successfully imported!</p><br>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p><br>";
    }
}

if (isset($_POST['bulk_update']) && $_POST['bulk_update'] == 1 && (isset($_POST['type']) && $_POST['type'] == 'variants')) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['products']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update products.</label>";
        return false;
    }
    $count = 0;

    $count1 = 0;
    $filename = $_FILES["upload_file"]["tmp_name"];
    $error = false;
    $mimetype = mime_content_type($_FILES["upload_file"]["tmp_name"]);
    if (!in_array($mimetype, array('text/plain'))) {
        $error = true;        
    }
    if ($_FILES["upload_file"]["size"] > 0  && $error == false ) {    
        $file = fopen($filename, "r");
        $emptydata = false;
        $invalid_price = false;
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // ID
                $emapData[1] = trim($db->escapeString($emapData[1])); // type
                $emapData[2] = trim($db->escapeString($emapData[2])); // measurement
                $emapData[3] = trim($db->escapeString($emapData[3])); // measurement unit id
                $emapData[4] = trim($db->escapeString($emapData[4])); // price
                $emapData[5] = trim($db->escapeString($emapData[5])); // discounted price
                $emapData[6] = trim($db->escapeString($emapData[6])); // serve for
                $emapData[7] = trim($db->escapeString($emapData[7])); // stock
                $emapData[8] = trim($db->escapeString($emapData[8])); // stock unit id
                $emapData[9] = trim($db->escapeString($emapData[9])); // product id
                
                if (empty($emapData[0])) {
                    echo '<p class="alert alert-danger">Variant ID  is empty at row - ' . $count . '</div>';
                    return false;
                }
                $sql = "SELECT * FROM product_variant WHERE id=".$emapData[0];
                $db->sql($sql);
                $result = $db->getResult();
                if (empty($result)) {
                    echo '<p class="alert alert-danger">Variant ID  is invalid at row - ' . $count . '</div>';
                    return false;
                }
                
                
                if (!empty($emapData[1]) && $emapData[1] != 'packet' && $emapData[1] != 'loose') {
                    echo '<p class="alert alert-danger">Type  is invalid at row - ' . $count . '</div>';
                    return false;
                }
                 
                $sql = "SELECT id FROM unit";
                $db->sql($sql);
                $ids = $db->getResult();
                if(!empty($emapData[3])){
                   $invalid_measurement_unit=1;
                    foreach($ids as $id){
                        if($emapData[3]==$id['id']){
                            $invalid_measurement_unit=0;
                        }
                    }
                    if($invalid_measurement_unit==1){
                        echo '<p class="alert alert-danger">Measurement Unit ID is invalid at row - ' . $count . '</div>';
                        return false;
                    } 
                }
                
                if ($emapData[4] <= $emapData[5]) {
                    echo '<p class="alert alert-danger">Price is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[6]) && $emapData[6] != 'Available' && $emapData[6] != 'Sold Out') {
                    echo '<p class="alert alert-danger">Serve For  is invalid at row - ' . $count . '</div>';
                    return false;
                }
                
                if(!empty($emapData[8])){
                   $invalid_stock_unit=1;
                    foreach($ids as $id){
                        if($emapData[8]==$id['id']){
                            $invalid_stock_unit=0;
                        }
                    }
                    if($invalid_stock_unit==1){
                        echo '<p class="alert alert-danger">Stock Unit ID is invalid at row - ' . $count . '</div>';
                        return false;
                    } 
                }
                if(!empty($emapData[9])){
                  $sql = "SELECT id FROM products WHERE id=".$emapData[9];
                    $db->sql($sql);
                    $result = $db->getResult();
                    if (empty($result)) {
                        echo '<p class="alert alert-danger">Product ID  is invalid at row - ' . $count . '</div>';
                        return false;
                    }  
                }
            }
            $count++;
        }
        fclose($file);
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if ($count1 != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // ID
                $sql = "SELECT * FROM product_variant WHERE id=".$emapData[0];
                $db->sql($sql);
                $result = $db->getResult();
                $emapData[1] = !empty($emapData[1])?trim($db->escapeString($emapData[1])):$result[0]['type']; // type
                $emapData[2] = !empty($emapData[2])?trim($db->escapeString($emapData[2])):$result[0]['measurement']; // measurement
                $emapData[3] = !empty($emapData[3])?trim($db->escapeString($emapData[3])):$result[0]['measurement_unit_id']; // measurement unit id
                $emapData[4] = !empty($emapData[4])?trim($db->escapeString($emapData[4])):$result[0]['price']; // price
                $emapData[5] = $result[0]['discounted_price']==0 && !empty($emapData[5])?trim($db->escapeString($emapData[5])):trim($db->escapeString($emapData[5])); // discounted price
                $emapData[6] = !empty($emapData[6])?trim($db->escapeString($emapData[6])):$result[0]['serve_for']; // serve for
                $emapData[7] = !empty($emapData[7])?trim($db->escapeString($emapData[7])):$result[0]['stock']; // stock
                $emapData[8] = !empty($emapData[8])?trim($db->escapeString($emapData[8])):$result[0]['stock_unit_id']; // stock unit id
                $emapData[9] = !empty($emapData[9])?trim($db->escapeString($emapData[9])):$result[0]['product_id']; // product id
                $sql = "UPDATE product_variant SET `product_id`='".$emapData[9]."',`type`='".$emapData[1]."',`measurement`='".$emapData[2]."',`measurement_unit_id`='".$emapData[3]."',`price`='".$emapData[4]."',`discounted_price`='".$emapData[5]."',`serve_for`='".$emapData[6]."',`stock`='".$emapData[7]."',`stock_unit_id`='".$emapData[8]."' WHERE id=".$emapData[0];
                $db->sql($sql);
            }

            $count1++;
        }
        fclose($file);
        echo "<p class='alert alert-success'>CSV file is successfully imported!</p><br>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p><br>";
    }
}

if (isset($_GET['product_status']) && !empty($_GET['product_status']) && isset($_GET['type']) && !empty($_GET['type'])) {
    if(ALLOW_MODIFICATION==0){
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    if ($permissions['products']['update'] == 0) {
        echo "<label class='alert alert-danger'>You have no permission to update products.</label>";
        return false;
    }
    $type = $db->escapeString($fn->xss_clean($_GET['type']));
    $product_id = $db->escapeString($fn->xss_clean($_GET['id']));

    if($type == 'deactive'){
        $sql = "UPDATE `products` SET `status`= 0 WHERE id = $product_id";
        if($db->sql($sql)){
            echo 1;
        }else{
            echo 0;
        }
    }
    if($type == 'active'){
        $sql = "UPDATE `products` SET `status`= 1 WHERE id = $product_id";
        if($db->sql($sql)){
            echo 1;
        }else{
            echo 0;
        }
    }    
}


?>