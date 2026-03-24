<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Récupération des données
$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
    $name = $input['client_name'] ?? 'Client';
    $phone = $input['phone'] ?? ''; 
    $amount = $input['amount'] ?? '0';
    $formation = $input['formation'] ?? 'Formation';
    $operator = $input['operator'] ?? '';
    $ref = "INS-" . time(); 

    // Nettoyage numéro
    $cleanPhone = str_replace([' ', '+'], '', $phone);

    // Date ISO avec timezone (important)
    $customerTimestamp = date('Y-m-d\TH:i:sP');

    // CONFIG PAWAPAY
    $apiKey = "eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJ0dCI6IkFBVCIsInN1YiI6IjE2MTk5IiwibWF2IjoiMSIsImV4cCI6MjA4OTY5OTY3NCwiaWF0IjoxNzc0MDgwNDc0LCJwbSI6IkRBRixQQUYiLCJqdGkiOiI2OTVjZmU5Zi05YWExLTQxNTUtODRjNC0zN2M2MjY1ZTBiNDcifQ.asYDBa_NnVrAtHBubSv5jN3a2y-y0GDBxz3rfDB5TGjUG6rxzwF8WJCJrNALYgPM5TUL-3hCRuFf4EI0cecGYw"; 
    $apiUrl = "https://api.sandbox.pawapay.cloud/v1/deposits";

    $payload = [
        "depositId" => $ref,
        "amount" => (string)$amount,
        "currency" => "USD",
        "correspondent" => $operator,

        // ✅ NOUVEAU CHAMP OBLIGATOIRE
        "statementDescription" => "Paiement formation",

        // ✅ TIMESTAMP
        "customerTimestamp" => $customerTimestamp,

        "payer" => [
            "type" => "MSISDN",
            "address" => [
                "value" => $cleanPhone
            ]
        ],

        "customerFirstName" => $name,
        "customerLastName" => "Client",
        "description" => "Paiement Formation $formation"
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $resData = json_decode($response, true);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (isset($resData['redirectUrl'])) {
        echo json_encode([
            "status" => "SUCCESS",
            "redirectUrl" => $resData['redirectUrl']
        ]);
    } else {
        $errorMsg = $resData['errorMessage'] ?? ($resData['message'] ?? json_encode($resData));
        echo json_encode([
            "status" => "ERROR",
            "message" => "PawaPay ($httpCode) : " . $errorMsg
        ]);
    }
} else {
    echo json_encode([
        "status" => "ERROR",
        "message" => "Aucune donnée reçue"
    ]);
}
exit;
