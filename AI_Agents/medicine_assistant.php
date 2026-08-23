<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Medicine AI Assistant";
require_once '../header.php';

$medicine_name = "";
$response = "";
$error = "";
$success = false;


$api_key = "******";


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $medicine_name = trim($_POST['medicine_name']);

    if (empty($medicine_name)) {
        $error = "Please enter a medicine name.";
    } else {

        $prompt = "You are a helpful medical information assistant. 
Give clear and simple information about the medicine: \"$medicine_name\".

Format:
1. What it is used for
2. Common dosage (general)
3. Common side effects
4. Important precautions

Use simple English. Always say to consult a doctor.";

        // Try multiple current models
        $models = [
            "gemini-2.5-flash",
            "gemini-2.0-flash",
            "gemini-1.5-flash-latest",
            "gemini-1.5-flash"
        ];

        $api_success = false;

        if (!empty($api_key) && $api_key !== "YOUR_GEMINI_API_KEY_HERE") {
            foreach ($models as $model) {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($api_key);

                $data = [
                    "contents" => [
                        ["parts" => [["text" => $prompt]]]
                    ]
                ];

                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_TIMEOUT => 15
                ]);

                $api_response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $result = json_decode($api_response, true);

                if ($http_code == 200 && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    $response = $result['candidates'][0]['content']['parts'][0]['text'];
                    $api_success = true;
                    break; // Stop when one model works
                }
            }
        }

        // If Gemini failed → use smart local response
        if (!$api_success) {
            $response = getLocalMedicineInfo($medicine_name);
        }

        $success = true;

        // Save to database
        $agent_id = 1;
        $patient_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO medicine_queries (patient_id, agent_id, medicine_name, response) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $patient_id, $agent_id, $medicine_name, $response);
        $stmt->execute();
        $stmt->close();
    }
}

// Local smart responses (works without API)
function getLocalMedicineInfo($medicine) {
    $medicine = strtolower(trim($medicine));

    $data = [
        'napa' => "Napa (Paracetamol) is used to reduce fever and relieve mild to moderate pain.

1. Used for: Fever, headache, body ache, toothache
2. Common dosage: 500mg every 4-6 hours (max 4g per day)
3. Side effects: Rare when used correctly
4. Precautions: Do not take with alcohol. Overdose can damage the liver.

Please consult a doctor for personal advice.",

        'seclo' => "Seclo (Omeprazole) reduces stomach acid production.

1. Used for: Acidity, gastric ulcer, heartburn, GERD
2. Common dosage: 20mg once daily before meal
3. Side effects: Headache, diarrhea, stomach pain
4. Precautions: Take on empty stomach. Long-term use needs doctor supervision.

Consult your doctor for proper use.",

        'paracetamol' => "Paracetamol is a common medicine for fever and pain relief.

1. Used for: Fever, headache, muscle pain
2. Dosage: 500-1000mg every 4-6 hours
3. Side effects: Usually safe if dosage is followed
4. Precautions: Do not exceed recommended dose.

Always follow doctor's advice.",

        'amoxicillin' => "Amoxicillin is an antibiotic used for bacterial infections.

1. Used for: Throat, ear, chest, urinary infections
2. Dosage: Usually 250-500mg every 8 hours
3. Side effects: Diarrhea, nausea, skin rash
4. Precautions: Complete the full course. Not effective for viral infections.

Only take if prescribed by a doctor."
    ];

    foreach ($data as $key => $value) {
        if (strpos($medicine, $key) !== false) {
            return $value;
        }
    }

    return "Information about \"" . htmlspecialchars($medicine) . "\":

This medicine is used for specific health conditions.

1. Please consult a registered doctor for correct dosage and usage.
2. Do not self-medicate.
3. Read the medicine label carefully.
4. Inform your doctor about any other medicines you are taking.

This is general information only. Always seek professional medical advice.";
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <div class="bg-info bg-opacity-10 rounded-circle p-3 me-3">
                    <i class="bi bi-robot text-info fs-2"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-1">Medicine AI Assistant</h3>
                    <p class="text-muted mb-0">Powered by Google Gemini</p>
                </div>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
        </div>

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-body p-4">

                <?php if ($success && $response): ?>
                    <div class="bg-light rounded-3 p-3 mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-robot text-info me-2"></i>
                            <strong class="text-info">MediSlot AI</strong>
                        </div>
                        <div style="white-space: pre-line; line-height: 1.7;">
                            <?php echo nl2br(htmlspecialchars($response)); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="d-flex gap-2">
                    <input type="text" name="medicine_name" class="form-control form-control-lg" 
                           placeholder="Type a medicine name (e.g. Napa, Seclo)..." 
                           value="<?php echo htmlspecialchars($medicine_name); ?>" required>
                    <button type="submit" class="btn btn-info text-white px-4">
                        <i class="bi bi-send-fill"></i> Ask AI
                    </button>
                </form>
            </div>
        </div>

        <div class="alert alert-warning">
            <strong>Disclaimer:</strong> This is general information only. Always consult a registered doctor.
        </div>

    </div>
</div>

<?php require_once '../footer.php'; ?>
