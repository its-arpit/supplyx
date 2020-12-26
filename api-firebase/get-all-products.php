<?php
header('Access-Control-Allow-Origin: *');
include_once('../includes/variables.php');
include_once('../includes/crud.php');
include_once('verify-token.php');
$db = new Database();
$db->connect();
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
date_default_timezone_set('Asia/Kolkata');

/* accesskey:90336
  	   user_id:369 {optional}
  	 */
if (!verify_token()) {
    return false;
}
if (isset($_POST['accesskey']) && isset($_POST['get_all_products'])) {
    $access_key_received = isset($_POST['accesskey']) && !empty($_POST['accesskey']) ? $db->escapeString($fn->xss_clean($_POST['accesskey'])) : '';
    $user_id = (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";

    if ($access_key_received == $access_key) {
        $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
        $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

        $sort = (isset($_POST['sort']) && !empty($_POST['sort'])) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : "row_order + 0 ";
        $order = (isset($_POST['order']) && !empty($_POST['order'])) ? $db->escapeString($fn->xss_clean($_POST['order'])) : "ASC";

        $sql = "SELECT count(id) as total FROM products ";
        $db->sql($sql);
        $total = $db->getResult();


        $sql = "SELECT * FROM products ORDER BY $sort $order LIMIT $offset,$limit ";
        $db->sql($sql);
        $res = $db->getResult();
        $product = array();
        $i = 0;
        $sql = "SELECT id FROM cart limit 1";
        $db->sql($sql);
        $res_cart = $db->getResult();
        foreach ($res as $row) {

            $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " ";
            $db->sql($sql);
            $row['other_images'] = json_decode($row['other_images'], 1);
            $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];
            for ($j = 0; $j < count($row['other_images']); $j++) {
                $row['other_images'][$j] = DOMAIN_URL . $row['other_images'][$j];
            }

            $row['image'] = DOMAIN_URL . $row['image'];
            if($row['tax_id'] == 0){
                $row['tax_title'] = "";
                $row['tax_percentage'] = "";
            }else{
                $t_id = $row['tax_id'];
                $sql_tax = "SELECT * from taxes where id= $t_id";
                $db->sql($sql_tax);
                $res_tax1 = $db->getResult(); 
                foreach($res_tax1 as $tax1){
                    $row['tax_title'] = (!empty($tax1['title'])) ? $tax1['title'] : "";
                    $row['tax_percentage'] =  (!empty($tax1['percentage'])) ? $tax1['percentage'] : "";
                }
            }
            $product[$i] = $row;
            $variants = $db->getResult();
            for ($k = 0; $k < count($variants); $k++) {
                if ($variants[$k]['stock'] <= 0) {
                    $variants[$k]['serve_for'] = 'Sold Out';
                } else {
                    $variants[$k]['serve_for'] = 'Available';
                }
                if (!empty($user_id)) {
                    $sql = "SELECT qty as cart_count FROM cart where product_variant_id= " . $variants[$k]['id'] . " AND user_id=" . $user_id;
                    $db->sql($sql);
                    $res = $db->getResult();
                    if (!empty($res)) {
                        foreach ($res as $row1) {
                            $variants[$k]['cart_count'] = $row1['cart_count'];
                        }
                    } else {
                        $variants[$k]['cart_count'] = "0";
                    }
                } else {
                    $variants[$k]['cart_count'] = "0";
                }
            }

            $product[$i]['variants'] = $variants;
            $i++;
        }

        // create json output
        if (!empty($product)) {
            $output = json_encode(
                array(
                    'error' => false,
                    'total' => $total[0]['total'],
                    'limit' => $limit,
                    'offset' => $offset,
                    'sort' => $sort,
                    'order' => $order,
                    'message' => "Products retrieved successfully",
                    'data' => $product
                )
            );
        } else {
            $output = json_encode(array(
                'error' => true,
                'total' => $total[0]['total'],
                'limit' => $limit,
                'offset' => $offset,
                'sort' => $sort,
                'order' => $order,
                'message' => 'No products available',
                'data' => array()
            ));
        }
    } else {
        die('accesskey is incorrect.');
    }
} else {
    die('accesskey is required.');
}
//Output the output.
echo $output;
$db->disconnect();
//to check if the string is json or not
function isJSON($string)
{
    return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
}
