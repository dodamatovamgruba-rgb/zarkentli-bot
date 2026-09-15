<?php
if (isset($_GET['card'])) {

    $card = $_GET['card']; // inputdan kelgan karta

    $url = "https://reposu.org/payme/card/" . $card;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "cURL error: " . curl_error($ch);
        exit;
    }

    curl_close($ch);

    echo "<h3>Natija:</h3>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";
}
?>