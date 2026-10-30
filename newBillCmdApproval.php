<?php

// ─────────────────────────────────────────
// New Bill / Advance CMD Approval mail (rent revision).
// Called by the Node backend on CMD approval of POST /admin/rent-revision/approve
// with { mailtype: "newbillcmdapproval", to, cc, data }.
// No bill dates (last / current / next) in this mail.
// ─────────────────────────────────────────

// ─────────────────────────────────────────
// Dependencies
// ─────────────────────────────────────────
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
require_once __DIR__ . '/newBillCmdApprovalHelpers.php';

// ─────────────────────────────────────────
// Render the mail body (also used by tests/preview.php).
// ─────────────────────────────────────────
function renderNewBillCmdMail(array $payload, string $templatePath): string {
    ob_start();
    include $templatePath;
    return ob_get_clean();
}

// ─────────────────────────────────────────
// Render + send one New Bill / Advance CMD approval email.
// ─────────────────────────────────────────
function sendNewBillCmdMail($payload, $subject, $toAddresses, $ccAddresses, $invoiceUrls, $templatePath) {
    $body = renderNewBillCmdMail($payload, $templatePath);

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host     = 'localhost';
    $mail->SMTPAuth = false;
    $mail->Port     = 25;

    $mail->setFrom('noreply@adinndigital.com', 'CMD Approved');
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    foreach ($toAddresses as $email) {
        if (!empty(trim($email))) {
            $mail->addAddress(trim($email));
        }
    }

    foreach ($ccAddresses as $email) {
        if (!empty(trim($email))) {
            $mail->addCC(trim($email));
        }
    }

    // Attach the rent revision invoice PDF. A failed fetch is skipped, not fatal.
    foreach ($invoiceUrls as $pdfUrl) {
        $pdfUrl = trim((string)$pdfUrl);
        if ($pdfUrl === '') continue;
        $pdfData = nbFetchRemoteFile($pdfUrl);
        if ($pdfData === null) continue;
        $filename = basename(parse_url($pdfUrl, PHP_URL_PATH) ?: '') ?: 'invoice.pdf';
        $mail->addStringAttachment($pdfData, $filename, PHPMailer::ENCODING_BASE64, 'application/pdf');
    }

    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->send();
}

/**
 * Validate the request, build the payload and send ONE mail.
 * Reused by the HTTP call below and by tests.
 */
function processNewBillCmdApprovalRequest(array $data): array {
    if (empty($data['mailtype']) || $data['mailtype'] !== 'newbillcmdapproval') {
        throw new InvalidArgumentException("mailtype must be 'newbillcmdapproval'");
    }

    if (empty($data['to'])) {
        throw new InvalidArgumentException("'to' address is required");
    }

    if (empty($data['data']) || !is_array($data['data'])) {
        throw new InvalidArgumentException("'data' object is required");
    }

    $toAddresses = $data['to'] ?? [];
    $ccAddresses = $data['cc'] ?? [];
    if (is_string($toAddresses)) $toAddresses = explode(',', $toAddresses);
    if (is_string($ccAddresses)) $ccAddresses = explode(',', $ccAddresses);

    $templatePath = __DIR__ . '/templates/newBillCmdTemplate.php';
    if (!file_exists($templatePath)) {
        throw new RuntimeException("Template file not found");
    }

    $built   = nbBuildPayload($data['data']);
    $subject = nbBuildSubject(
        $built['payload']['is_advance'],
        is_array($data['data']['landOwners'] ?? null) ? $data['data']['landOwners'] : [],
        (string)($built['payload']['media_code'] ?? '')
    );

    sendNewBillCmdMail($built['payload'], $subject, $toAddresses, $ccAddresses, $built['invoice_urls'], $templatePath);

    return ["status" => "success", "message" => "Mail sent successfully", "mailCount" => 1];
}

// ─────────────────────────────────────────
// HTTP Direct Request Handling Bootstrap
// ─────────────────────────────────────────
if (isset($_SERVER['REQUEST_METHOD'])) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Content-Type: application/json");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Only POST allowed"]);
        exit;
    }

    if (!empty($_POST)) {
        $data = $_POST;
    } else {
        $data = json_decode(file_get_contents("php://input"), true);
    }

    try {
        $result = processNewBillCmdApprovalRequest($data ?? []);
        echo json_encode($result);
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}
