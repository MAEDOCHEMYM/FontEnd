<?php

header("Content-Type: application/json");

// 🔐 Ta clé Flutterwave (remplace)
$secret_key = "FLWSECK_TEST-06ec1156cc90e6a43b9129066e134221-X";

// 📥 Lire JSON (envoyé par fetch)
$input = json_decode(file_get_contents("php://input"), true);

// 🧾 Récupération (comme ton frontend)
$name       = $input['client_name'] ?? '';
$phone      = $input['phone'] ?? '';
$amount     = $input['amount'] ?? '';
$formation  = $input['formation'] ?? '';
$operator   = $input['operator'] ?? ''; // pas utilisé ici mais gardé

// ⚠️ Validation
if(empty($name) || empty($phone) || empty($amount)){
    echo json_encode([
        "status" => "ERROR",
        "message" => "Champs requis manquants"
    ]);
    exit;
}

// 🆔 Référence unique
$tx_ref = "TX_" . time();

// 📦 Données Flutterwave
$data = [
    "tx_ref" => $tx_ref,
    "amount" => $amount,
    "currency" => "USD",
    "redirect_url" => "https://tonsite.com/success.php",

    "customer" => [
        "email" => "client@email.com", // ⚠️ Flutterwave exige email
        "phonenumber" => $phone,
        "name" => $name
    ],

    "customizations" => [
        "title" => "Paiement Formation",
        "description" => $formation ?: "Paiement formation"
    ]
];

// 🚀 Appel API Flutterwave
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://api.flutterwave.com/v3/payments");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $secret_key",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);

if(curl_errno($ch)){
    echo json_encode([
        "status" => "ERROR",
        "message" => curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// 📊 Analyse réponse
$result = json_decode($response, true);

// 🔁 Retour EXACT pour ton JS
if(isset($result['data']['link'])){
    echo json_encode([
        "status" => "SUCCESS",
        "redirectUrl" => $result['data']['link']
    ]);
} else {
    echo json_encode([
        "status" => "ERROR",
        "message" => $result['message'] ?? "Erreur Flutterwave",
        "debug" => $result
    ]);
}

?>
