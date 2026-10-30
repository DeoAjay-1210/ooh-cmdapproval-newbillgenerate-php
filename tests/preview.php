<?php
// Render the sample payloads (tests/payloads.json) to HTML files — no mail is sent.
//   php tests/preview.php            → tests/out/bill.html, tests/out/advance.html
// Prints the subject of each mail.

require_once __DIR__ . '/../newBillCmdApproval.php';

$payloads = json_decode(file_get_contents(__DIR__ . '/payloads.json'), true);
$outDir   = __DIR__ . '/out';
if (!is_dir($outDir)) mkdir($outDir, 0777, true);

foreach ($payloads as $name => $request) {
    $built   = nbBuildPayload($request['data']);
    $subject = nbBuildSubject($built['payload']['is_advance'], $request['data']['landOwners'] ?? [], (string)$built['payload']['media_code']);
    $html    = renderNewBillCmdMail($built['payload'], __DIR__ . '/../templates/newBillCmdTemplate.php');
    file_put_contents("$outDir/$name.html", $html);
    echo "$name: $subject → tests/out/$name.html\n";
}
