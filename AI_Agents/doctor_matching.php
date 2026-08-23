<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$page_title = "AI Doctor Matching";
require_once '../header.php';

$symptoms = "";
$ai_suggestion = "";
$recommended_doctors = [];
$error = "";
$success = false;


$api_key = "*****";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $symptoms = trim($_POST['symptoms']);

    if (empty($symptoms)) {
        $error = "Please describe your symptoms.";
    } else {

        // ===== Call Gemini =====
        $prompt = "You are a medical triage assistant for a telemedicine platform in Bangladesh.
A patient described these symptoms: \"$symptoms\"

Based on the symptoms, suggest the most suitable medical specialization.
Reply in this exact format only:

Specialization: [name of specialization]
Reason: [short explanation in simple English]

Examples of specializations: Cardiology, Dermatology, General Physician, Gastroenterology, Neurology, Orthopedics, ENT, Pediatrics, Gynecology, Psychiatry, Urology, etc.
Do not give personal medical advice. Just suggest the specialization.";

        $models = ["gemini-2.5-flash", "gemini-2.0-flash", "gemini-1.5-flash"];
        $ai_success = false;

        if (!empty($api_key) && $api_key !== "YOUR_GEMINI_API_KEY_HERE") {
            foreach ($models as $model) {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($api_key);

                $data = [
                    "contents" => [["parts" => [["text" => $prompt]]]]
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
                    $ai_suggestion = $result['candidates'][0]['content']['parts'][0]['text'];
                    $ai_success = true;
                    break;
                }
            }
        }

        // Fallback if Gemini fails
        if (!$ai_success) {
            $ai_suggestion = getFallbackSuggestion($symptoms);
        }

        // Extract specialization from AI response
        $specialization = "General Physician"; // default
        if (preg_match('/Specialization:\s*(.+)/i', $ai_suggestion, $matches)) {
            $specialization = trim($matches[1]);
        }

        // Find matching verified doctors
        $stmt = $conn->prepare("
            SELECT d.doctor_id, d.specialization, d.hospital, d.consultation_fee, d.rating,
                   u.name
            FROM doctors d
            JOIN users u ON d.doctor_id = u.user_id
            WHERE d.is_verified = 1 
              AND d.specialization LIKE ?
            ORDER BY d.rating DESC
            LIMIT 6
        ");
        $like = "%" . $specialization . "%";
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $recommended_doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Save to database
        $agent_id = 2; // Doctor Matching Agent
        $patient_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("
            INSERT INTO doctor_match_suggestions (patient_id, agent_id, symptoms, suggested_doctor_id)
            VALUES (?, ?, ?, NULL)
        ");
        $stmt->bind_param("iis", $patient_id, $agent_id, $symptoms);
        $stmt->execute();
        $stmt->close();

        $success = true;
    }
}

function getFallbackSuggestion($symptoms) {
    $symptoms = strtolower($symptoms);

    if (strpos($symptoms, 'skin') !== false || strpos($symptoms, 'rash') !== false || strpos($symptoms, 'itch') !== false) {
        return "Specialization: Dermatology\nReason: Symptoms related to skin issues.";
    }
    if (strpos($symptoms, 'heart') !== false || strpos($symptoms, 'chest pain') !== false || strpos($symptoms, 'breath') !== false) {
        return "Specialization: Cardiology\nReason: Symptoms related to heart or chest.";
    }
    if (strpos($symptoms, 'stomach') !== false || strpos($symptoms, 'acid') !== false || strpos($symptoms, 'gastric') !== false) {
        return "Specialization: Gastroenterology\nReason: Digestive system related symptoms.";
    }
    if (strpos($symptoms, 'bone') !== false || strpos($symptoms, 'joint') !== false || strpos($symptoms, 'back pain') !== false) {
        return "Specialization: Orthopedics\nReason: Bone or joint related symptoms.";
    }

    return "Specialization: General Physician\nReason: General symptoms. A General Physician can guide you further.";
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1">
                        <i class="bi bi-robot text-primary me-2"></i> AI Doctor Matching
                    </h3>
                    <p class="text-muted mb-0">Describe your symptoms and get doctor recommendations</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
            </div>

            <!-- Input Form -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Describe your symptoms</label>
                            <textarea name="symptoms" class="form-control form-control-lg" rows="4" 
                                      placeholder="Example: I have itching and red rashes on my hands for 3 days..." required><?php echo htmlspecialchars($symptoms); ?></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-search me-2"></i> Find Matching Doctors
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <!-- AI Suggestion -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-robot me-2"></i> AI Suggestion</h5>
                    </div>
                    <div class="card-body">
                        <div style="white-space: pre-line; line-height: 1.7;">
                            <?php echo nl2br(htmlspecialchars($ai_suggestion)); ?>
                        </div>
                    </div>
                </div>

                <!-- Recommended Doctors -->
                <h5 class="fw-bold mb-3">Recommended Doctors</h5>

                <?php if (empty($recommended_doctors)): ?>
                    <div class="alert alert-info">
                        No verified doctors found for this specialization right now.
                        <a href="search_doctors.php">Browse all doctors</a>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($recommended_doctors as $doc): ?>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($doc['name']); ?></h5>
                                        <p class="text-muted mb-2"><?php echo htmlspecialchars($doc['specialization']); ?></p>
                                        <p class="mb-1">
                                            <i class="bi bi-star-fill text-warning"></i> 
                                            <?php echo number_format($doc['rating'], 1); ?>
                                        </p>
                                        <p class="mb-3">
                                            Fee: <strong><?php echo number_format($doc['consultation_fee'], 0); ?> BDT</strong>
                                        </p>
                                        <a href="doctor_profile.php?doctor_id=<?php echo $doc['doctor_id']; ?>" 
                                           class="btn btn-primary btn-sm w-100">
                                            View & Book
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>
