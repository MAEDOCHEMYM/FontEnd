<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
    $name = $input['client_name'];
    $phone = $input['phone']; // Format attendu par PawaPay : +243...
    $amount = $input['amount'];
    $formation = $input['formation'];
    $ref = "INS-" . time(); // Référence unique

    // 1. ENVOI DE L'EMAIL DE NOTIFICATION
    $to = "mardocheyombu7@gmail.com"; // Mettez votre adresse ici
    $subject = "Nouvelle Inscription : $name";
    $body = "L'étudiant $name ($phone) veut s'inscrire à $formation pour $amount USD.";
    mail($to, $subject, $body, "From: inscriptions@votre-site.com");

    // 2. APPEL RÉEL À PAWAPAY (Mode Dépôt)
    $apiKey = "eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJ0dCI6IkFBVCIsInN1YiI6IjE2MTk5IiwibWF2IjoiMSIsImV4cCI6MjA4OTY5OTY3NCwiaWF0IjoxNzc0MDgwNDc0LCJwbSI6IkRBRixQQUYiLCJqdGkiOiI2OTVjZmU5Zi05YWExLTQxNTUtODRjNC0zN2M2MjY1ZTBiNDcifQ.asYDBa_NnVrAtHBubSv5jN3a2y-y0GDBxz3rfDB5TGjUG6rxzwF8WJCJrNALYgPM5TUL-3hCRuFf4EI0cecGYw"; // Votre clé API PawaPay
    
    $payload = [
        "depositId" => $ref,
        "amount" => (string)$amount,
        "currency" => "USD",
        "correspondent" => $input['operator'], // ex: ORANGE, AIRTEL, VODACOM
        "pawaPayContactId" => $phone,
        "customerFirstName" => $name,
        "customerLastName" => "Client",
        "billingAddress" => "RDC",
        "description" => "Paiement Formation $formation"
    ];

    $ch = curl_init("https://api.sandbox.pawapay.io/v2/deposits"); // URL de production
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $resData = json_decode($response, true);
    curl_close($ch);

    if (isset($resData['redirectUrl'])) {
        echo json_encode(["status" => "SUCCESS", "redirectUrl" => $resData['redirectUrl']]);
    } else {
        echo json_encode(["status" => "ERROR", "message" => "PawaPay n'a pas pu générer le lien."]);
    }
}
?>
