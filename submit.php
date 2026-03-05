<?php
/**
 * Simple PHP form handler for Client Registration with MySQL and Email Support
 */

require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input data
    $clientName = isset($_POST['clientName']) ? htmlspecialchars($_POST['clientName']) : '';
    $partnerName = isset($_POST['partnerName']) ? htmlspecialchars($_POST['partnerName']) : '';
    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
    $phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
    $instagram = isset($_POST['instagram']) ? htmlspecialchars($_POST['instagram']) : '';
    $clientID = isset($_POST['clientID']) ? htmlspecialchars($_POST['clientID']) : '';
    $weddingDate = isset($_POST['weddingDate']) ? htmlspecialchars($_POST['weddingDate']) : '';
    $partyStartTime = isset($_POST['partyStartTime']) ? htmlspecialchars($_POST['partyStartTime']) : '';
    $partyEndTime = isset($_POST['partyEndTime']) ? htmlspecialchars($_POST['partyEndTime']) : '';
    $guestCount = isset($_POST['guestCount']) ? intval($_POST['guestCount']) : 0;
    $location = isset($_POST['location']) ? htmlspecialchars($_POST['location']) : '';
    if ($location === 'other' && isset($_POST['manualLocation'])) {
        $location = htmlspecialchars($_POST['manualLocation']);
    }
    $ideas = isset($_POST['ideas']) ? htmlspecialchars($_POST['ideas']) : '';
    $weddingColors = isset($_POST['weddingColors']) ? htmlspecialchars($_POST['weddingColors']) : '';
    $cakeTableType = isset($_POST['cakeTableType']) ? htmlspecialchars($_POST['cakeTableType']) : '';
    $cakeTableSize = isset($_POST['cakeTableSize']) ? htmlspecialchars($_POST['cakeTableSize']) : '';
    $decorPackage = isset($_POST['decorPackage']) ? htmlspecialchars($_POST['decorPackage']) : '';
    $estimatedBudget = isset($_POST['estimatedBudget']) ? floatval($_POST['estimatedBudget']) : 0.0;
    $customRequirements = isset($_POST['customRequirements']) ? htmlspecialchars($_POST['customRequirements']) : '';
    $mediaConsent = isset($_POST['mediaConsent']) ? 'Agreed' : 'Not Agreed';
    $addons = isset($_POST['addons']) ? (array)$_POST['addons'] : [];
    $addons_str = implode(', ', array_map('htmlspecialchars', $addons));

    // --- Save to CSV (Keeping this as a backup) ---
    $csvFile = 'submissions.csv';
    $isNewFile = !file_exists($csvFile);
    
    // Define headers
    $headers = [
        'Submission Date', 'Client Name', 'Partner Name', 'Email', 'Phone', 'Instagram',
        'ID/Passport', 'Wedding Date', 'Party Start Time', 'Party End Time', 'Guest Count', 'Location', 'Cake Table Type',
        'Cake Table Size', 'Package', 'Estimated Budget', 'Custom Requirements',
        'Add-ons', 'Media Consent', 'Ideas'
    ];

    // Data to save
    $data = [
        date('Y-m-d H:i:s'),
        $clientName,
        $partnerName,
        $email,
        $phone,
        $instagram,
        $clientID,
        $weddingDate,
        $partyStartTime,
        $partyEndTime,
        $guestCount,
        $location,
        $cakeTableType,
        $cakeTableSize,
        $decorPackage,
        $estimatedBudget,
        $customRequirements,
        $addons_str,
        $mediaConsent,
        $ideas
    ];

    // Open file in append mode
    $fileHandle = fopen($csvFile, 'a');
    if ($fileHandle) {
        if ($isNewFile) {
            fputcsv($fileHandle, $headers);
        }
        fputcsv($fileHandle, $data);
        fclose($fileHandle);
    }

    // --- Save to MySQL ---
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO submissions (
                    client_name, partner_name, email, phone, instagram, 
                    client_id, wedding_date, party_start_time, party_end_time, guest_count, location, 
                    cake_table_type, cake_table_size, decor_package, 
                    estimated_budget, custom_requirements, addons, 
                    media_consent, ideas
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $clientName, $partnerName, $email, $phone, $instagram,
            $clientID, ($weddingDate ?: null), ($partyStartTime ?: null), ($partyEndTime ?: null), $guestCount, $location,
            $cakeTableType, $cakeTableSize, $decorPackage,
            $estimatedBudget, $customRequirements, $addons_str,
            $mediaConsent, $ideas
        ]);
    } catch (PDOException $e) {
        // Log the error but continue to email/success message (in a real app, you might want more complex handling)
        echo "Database Error: " . $e->getMessage();
    }

    // --- Send via Email ---
    $to = ADMIN_EMAIL;
    $subject = EMAIL_SUBJECT_PREFIX . "$clientName & $partnerName";
    $message = "New inquiry from Special Events registration form:

Client Name: $clientName
Partner Name: $partnerName
Email: $email
Phone: $phone
Instagram: $instagram
ID/Passport: $clientID
Wedding Date: $weddingDate
Party Start Time: $partyStartTime
Party End Time: $partyEndTime
Guest Count: $guestCount
Location: $location

--- Cake Table & Packages ---
Cake Table: $cakeTableType ($cakeTableSize)
Package: $decorPackage
Estimated Budget: $estimatedBudget
Decoration Requirements: $customRequirements

--- Add-ons ---
$addons_str

--- Additional Info ---
Wedding Colours: $weddingColors
Media Consent: $mediaConsent
Your Vision/Ideas:
$ideas
";
    $email_headers = "From: " . FROM_EMAIL . "\r\n" .
               "Reply-To: " . $email . "\r\n" .
               "X-Mailer: PHP/" . phpversion();

    mail($to, $subject, $message, $email_headers);

    // --- Show success message ---
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Submission Successful - Special Events</title>
        <link rel='stylesheet' href='assets/css/style.css'>
    </head>
    <body style='display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center; background-color: #f9f9f7; padding: 20px;'>
        <div class='container' style='max-width: 600px; padding: 60px 40px;'>
            <div class='logo-container' style='margin-bottom: 30px;'>
                <img src='assets/img/logo.PNG' alt='Special Events Logo' style='height: 100px; mix-blend-mode: multiply;'>
            </div>
            
            <div style='margin-bottom: 30px;'>
                <div style='width: 60px; height: 60px; background-color: #8b947a; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 30px;'>
                    ✓
                </div>
                <h1 style='color: #8b947a; font-family: \"Playfair Display\", serif; margin-bottom: 15px;'>Registration Successful!</h1>
                <p style='font-size: 1.1rem; color: #444; line-height: 1.6;'>Thank you, <strong>$clientName</strong>. We have successfully received your wedding registration information and vision.</p>
                <p style='margin-top: 15px; color: #666;'>Our team will review your details and get back to you shortly via email ($email) or phone to discuss your dream wedding.</p>
            </div>
            
            <div style='margin-top: 40px; border-top: 1px solid #e0e0e0; padding-top: 30px;'>
                <a href='index.html' class='submit-btn' style='text-decoration: none; display: inline-block;'>Back to Form</a>
            </div>
        </div>
    </body>
    </html>";
} else {
    // If accessed directly, redirect to the form
    header("Location: index.html");
    exit();
}
?>
