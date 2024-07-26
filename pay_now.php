<?php
define('PAYPAL_CLIENT_ID', 'AZs5wgt5b3tRYxIvY5MhO5hxUn_UC_d91ecoMy8KGeyYyTR_bmZBa85oNKvs69N-JNzzV04Alujonm4N');
define('PAYPAL_SECRET', 'EK_AuxssZsPbHcbHyEMaQBu_r5v9Y74EqXbw4oeJU4oQTwCL2gkDitR-LyEwPKwA5UOHe6p1wnVUvbmy');
define('PAYPAL_BASE_URL', 'https://api.paypal.com');
require('admin/inc/db_config.php');
require('admin/inc/essentials.php');

date_default_timezone_set("Africa/Casablanca");

session_start();

function calculateTotalAmount($checkin, $checkout, $pricePerNight) {
    $checkinDate = new DateTime($checkin);
    $checkoutDate = new DateTime($checkout);
    $interval = $checkinDate->diff($checkoutDate);
    $numberOfNights = $interval->days;
    return $numberOfNights * $pricePerNight;
}

function generateAccessToken() {
    $authString = PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET;
    $authString = base64_encode($authString);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL.'/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Basic ' . $authString,
        'Content-Type: application/x-www-form-urlencoded'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $responseData = json_decode($response, true);
    return isset($responseData['access_token']) ? $responseData['access_token'] : '';
}

if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
    redirect('index.php');
}

if (isset($_POST['pay_now']) && isset($_POST['name']) && isset($_POST['phonenum']) && isset($_POST['address']) && isset($_POST['checkin']) && isset($_POST['checkout'])) {
    $name = $_POST['name'];
    $phonenum = $_POST['phonenum'];
    $address = $_POST['address'];
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $roomId = $_SESSION['room']['id'];

    $totalAmount = calculateTotalAmount($checkin, $checkout, $_SESSION['room']['price']);

    $ch = curl_init();
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . generateAccessToken()
    );

    $data = array(
        'intent' => 'sale',
        'payer' => array(
            'payment_method' => 'paypal',
        ),
        'transactions' => array(
            array(
                'amount' => array(
                    'total' => $totalAmount,
                    'currency' => 'USD'
                ),
                'description' => 'Paiement pour la réservation de chambre'
            )
        ),
        'redirect_urls' => array(
            'return_url' => 'http://localhost/htl/rdwebsite/pay_response.php',
            'cancel_url' => 'http://localhost/htl/rdwebsite/rooms.php'
        )
    );

    $payload = json_encode($data);

    curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL.'/v1/payments/payment');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);

    $ORDER_ID = 'ORD_' . $_SESSION['uId'] . random_int(11111, 9999999);
    $CUST_ID = $_SESSION['uId'];

    $query1 = "INSERT INTO booking_order(user_id, room_id, check_in, check_out, order_id) VALUES (?, ?, ?, ?, ?)";
    insert($query1, [$CUST_ID, $_SESSION['room']['id'], $checkin, $checkout, $ORDER_ID], 'iisss');

    $booking_id = mysqli_insert_id($con);

    $query2 = "INSERT INTO booking_details(booking_id, room_name, price, total_pay, user_name, phonenum, address) VALUES (?, ?, ?, ?, ?, ?, ?)";
    insert($query2, [$booking_id, $_SESSION['room']['name'], $_SESSION['room']['price'], $totalAmount, $name, $phonenum, $address], 'issssss');

    $_SESSION['payment_details'] = array(
        'name' => $name,
        'phonenum' => $phonenum,
        'address' => $address,
        'checkin' => $checkin,
        'checkout' => $checkout,
        'totalAmount' => $totalAmount
    );

    if (isset($responseData['links'][1]['href'])) {
        header('Location: ' . $responseData['links'][1]['href']);
        exit();
    } else {
        echo "Redirection link not found!";
    }
} else {
    header('Location: rooms.php');
    exit();
}
?>
