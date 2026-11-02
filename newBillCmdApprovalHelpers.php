<?php
// ─────────────────────────────────────────
// Pure business-logic helpers for the New Bill / Advance CMD Approval mail
// (rent revision). No PHPMailer / HTTP / header() calls in this file on
// purpose — it is required by newBillCmdApproval.php for the live mail flow,
// and can also be required standalone (tests/preview.php).
//
// Function names are prefixed "nb" so this file never clashes with
// cmdApprovalHelpers.php when both are deployed in the same folder.
// ─────────────────────────────────────────

// ─────────────────────────────────────────
// ENUM MAPS (same values as the CMD approval mail)
// ─────────────────────────────────────────
$NB_PAYMENT_CATEGORY = [
    1 => 'Cash',
    2 => 'Online',
    3 => 'Cash + Online',
];
$NB_ONLINE_MODE = [
    1 => 'Bank Transfer',
    2 => 'UPI',
    3 => 'Cheque',
];
$NB_WITH_GST = [
    0 => 'No GST',
    1 => 'GST Hold',
    2 => 'GST Direct',
];
$NB_MEDIA_STATUS = [
    1 => 'Active',
    2 => 'Inactive',
    3 => 'Hold',
];
$NB_ROW_TYPE = [
    'newBill'    => 'New Bill',
    'billUpdate' => 'Bill Increase',
];

function nbMapVal($map, $code, $fallbackPrefix = 'Code ') {
    if ($code === null || $code === '') return '';
    return $map[$code] ?? ($fallbackPrefix . $code);
}

function nbText($v, $empty = '–') {
    $v = trim((string)($v ?? ''));
    return $v === '' ? $empty : $v;
}

// Indian number format: 1234567 → 12,34,567
function nbFmtAmount($n) {
    if ($n === null || $n === '') return '';
    $n = (float)$n;
    $isNegative = $n < 0;
    $numStr = number_format(abs($n), 0, '.', '');
    $len = strlen($numStr);
    if ($len <= 3) {
        $formatted = $numStr;
    } else {
        $lastThree = substr($numStr, -3);
        $remaining = substr($numStr, 0, $len - 3);
        $remaining = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining);
        $formatted = $remaining . ',' . $lastThree;
    }
    return ($isNegative ? '-' : '') . $formatted;
}

function nbFetchRemoteFile($url, $timeoutSec = 15) {
    $context = stream_context_create([
        'http'  => ['timeout' => $timeoutSec, 'header' => "User-Agent: new-bill-cmd-mail\r\n"],
        'https' => ['timeout' => $timeoutSec, 'header' => "User-Agent: new-bill-cmd-mail\r\n"],
    ]);
    $data = @file_get_contents($url, false, $context);
    return $data === false ? null : $data;
}

// ─────────────────────────────────────────
// Landlord rows: owner + bank details and this bill's share.
// ─────────────────────────────────────────
function nbProcessLandOwners(array $landOwnersRaw) {
    global $NB_PAYMENT_CATEGORY, $NB_ONLINE_MODE;

    $rows = [];
    foreach ($landOwnersRaw as $i => $o) {
        $share = (float)($o['shareAmount'] ?? 0);
        $gst   = (float)($o['gstAmount'] ?? 0);
        $total = isset($o['totalAmount']) ? (float)$o['totalAmount'] : $share + $gst;
        $rows[] = [
            'sno'              => $i + 1,
            'owner_name'       => nbText($o['name'] ?? ''),
            'phone'            => nbText($o['phone'] ?? ''),
            'bank_name'        => nbText($o['bankName'] ?? ''),
            'ifsc'             => nbText($o['ifsc'] ?? ''),
            'account_no'       => nbText($o['accountNumber'] ?? ''),
            'pan'              => nbText($o['panNumber'] ?? ''),
            'payment_category' => nbMapVal($NB_PAYMENT_CATEGORY, $o['paymentCategory'] ?? null),
            'online_mode'      => !empty($o['onlineMode']) ? nbMapVal($NB_ONLINE_MODE, $o['onlineMode']) : '–',
            'gst_number'       => trim((string)($o['gstNumber'] ?? '')),
            'share_amount'     => nbFmtAmount($share),
            'online_amount'    => nbFmtAmount($o['onlineAmount'] ?? 0),
            'cash_amount'      => nbFmtAmount($o['cashAmount'] ?? 0),
            'gst_amount'       => nbFmtAmount($gst),
            'has_gst'          => $gst > 0,
            'total_amount'     => nbFmtAmount($total),
        ];
    }
    return $rows;
}

// ─────────────────────────────────────────
// Site rows: every face / site of the media (data.media.sites[]).
// Location = full address (location, district, city, state).
// ─────────────────────────────────────────
function nbProcessSites(array $sitesRaw) {
    global $NB_MEDIA_STATUS;

    $rows = [];
    foreach ($sitesRaw as $i => $s) {
        if (!is_array($s)) continue;
        $address = trim((string)($s['fullAddress'] ?? ''));
        if ($address === '') {
            $address = implode(', ', array_filter([$s['location'] ?? '', $s['district'] ?? '', $s['city'] ?? '', $s['state'] ?? '']));
        }
        $size = trim((string)($s['size'] ?? ''));
        if ($size === '' && !empty($s['width']) && !empty($s['height'])) {
            $size = $s['width'] . ' x ' . $s['height'] . ' ft' . (!empty($s['totalSqft']) ? ' (' . $s['totalSqft'] . ' sq.ft)' : '');
        }
        $status = (int)($s['status'] ?? 0);
        $rows[] = [
            'sno'         => $i + 1,
            'media_code'  => nbText($s['mediaCode'] ?? ''),
            'media_name'  => nbText($s['mediaName'] ?? ''),
            'media_type'  => nbText($s['mediaType'] ?? ''),
            'size'        => nbText($size),
            'address'     => nbText($address),
            // not Active → shown next to the name (inactive faces are listed too)
            'status_note' => ($status && $status !== 1) ? nbMapVal($NB_MEDIA_STATUS, $status, '') : '',
        ];
    }
    return $rows;
}

// Subject: "CMD New Bill Approved - <Landowner Name(s)> - <Media Code>"
//          "CMD Advance Approved - <Landowner Name(s)> - <Media Code>"
function nbBuildSubject(bool $isAdvance, array $landOwnersRaw, string $mediaCode): string {
    $names = array_values(array_filter(array_map(
        function ($o) { return trim((string)($o['name'] ?? '')); },
        $landOwnersRaw
    )));
    $ownerPart = count($landOwnersRaw) > 1
        ? (!empty($names) ? implode(', ', $names) : 'Multiple Landlords')
        : ($names[0] ?? '');
    $parts = array_filter([$ownerPart, trim($mediaCode)], function ($p) { return $p !== ''; });
    $title = $isAdvance ? 'CMD Advance Approved' : 'CMD New Bill Approved';
    return $title . ($parts ? ' - ' . implode(' - ', $parts) : '');
}

// ─────────────────────────────────────────
// data (from Node) → template payload. Throws InvalidArgumentException when
// the bill / advance block is missing. Returns ['payload', 'invoice_urls'].
// ─────────────────────────────────────────
function nbBuildPayload(array $data): array {
    global $NB_WITH_GST, $NB_ROW_TYPE;

    $billType  = strtoupper(trim((string)($data['billType'] ?? '')));
    if (!in_array($billType, ['BILL', 'ADVANCE'], true)) {
        throw new InvalidArgumentException("data.billType must be 'BILL' or 'ADVANCE'");
    }
    $isAdvance = $billType === 'ADVANCE';
    $media     = is_array($data['media'] ?? null) ? $data['media'] : [];

    $address = trim((string)($media['fullAddress'] ?? ''));
    if ($address === '') {
        $address = implode(', ', array_filter([$media['location'] ?? '', $media['city'] ?? '', $media['state'] ?? '']));
    }

    $withGst       = (int)($data['withGst'] ?? 0);
    $gstPercentage = (float)($data['gstPercentage'] ?? 0);
    $invoiceUrl    = trim((string)($data['gstInvoiceUrl'] ?? ''));

    $payload = [
        'is_advance'        => $isAdvance,
        'label'             => trim((string)($data['label'] ?? '')),
        'media_code'        => trim((string)($media['mediaCode'] ?? '')),
        'media_name'        => trim((string)($media['mediaName'] ?? '')),
        'media_type'        => trim((string)($media['mediaType'] ?? '')),
        'media_address'     => $address,
        'media_size'        => (!empty($media['width']) && !empty($media['height']))
            ? $media['width'] . ' x ' . $media['height'] . ' ft' . (!empty($media['totalSqft']) ? ' (' . $media['totalSqft'] . ' sq.ft)' : '')
            : '',
        'sites'             => nbProcessSites(is_array($media['sites'] ?? null) ? $media['sites'] : []),
        'payment_frequency' => trim((string)($data['paymentFrequency'] ?? '')),
        'gst_pct'           => $gstPercentage > 0 ? rtrim(rtrim(number_format($gstPercentage, 2, '.', ''), '0'), '.') : '',
        'with_gst'          => nbMapVal($NB_WITH_GST, $withGst),
        'is_gst_hold'       => $withGst === 1,
        'reason'            => trim((string)($data['reason'] ?? '')),
        'approved_by'       => trim((string)($data['approvedBy'] ?? '')),
        'approved_at'       => trim((string)($data['approvedAt'] ?? '')),
        'land_owners'       => nbProcessLandOwners(is_array($data['landOwners'] ?? null) ? $data['landOwners'] : []),
    ];

    if ($isAdvance) {
        $a = is_array($data['advance'] ?? null) ? $data['advance'] : null;
        if (!$a) throw new InvalidArgumentException("'data.advance' object is required for ADVANCE");
        $amount = (float)($a['advanceAmount'] ?? 0);
        $gst    = (float)($a['advanceGstAmount'] ?? 0);
        $payload['advance'] = [
            'advance_month'  => trim((string)($a['advanceMonth'] ?? '')),
            'adjust_in'      => trim((string)($a['adjustInMonth'] ?? '')),
            'amount'         => nbFmtAmount($amount),
            'gst_amount'     => nbFmtAmount($gst),
            'has_gst'        => $gst > 0,
            'grand_total'    => nbFmtAmount($a['grandTotal'] ?? ($amount + $gst)),
            'bill_amount'    => nbFmtAmount($a['billAmount'] ?? 0),
            'balance_amount' => nbFmtAmount($a['balanceAmount'] ?? 0),
        ];
        $payload['has_gst'] = $gst > 0;
    } else {
        $b = is_array($data['bill'] ?? null) ? $data['bill'] : null;
        if (!$b || empty($b['months']) || !is_array($b['months'])) {
            throw new InvalidArgumentException("'data.bill.months' is required for BILL");
        }
        $rows = [];
        foreach ($b['months'] as $m) {
            $amt = (float)($m['amount'] ?? 0);
            $gst = (float)($m['gstAmount'] ?? 0);
            $rows[] = [
                'month'      => trim((string)($m['month'] ?? '')),
                'type'       => nbMapVal($NB_ROW_TYPE, $m['type'] ?? 'newBill', ''),
                'amount'     => nbFmtAmount($amt),
                'gst_amount' => nbFmtAmount($gst),
                'total'      => nbFmtAmount($m['total'] ?? ($amt + $gst)),
            ];
        }
        $totalGst = (float)($b['totalGstAmount'] ?? 0);
        $applyAll = isset($b['applyToAllMonths']) ? (int)$b['applyToAllMonths'] : null;
        $payload['bill'] = [
            'rows'             => $rows,
            'total_amount'     => nbFmtAmount($b['totalAmount'] ?? 0),
            'total_gst'        => nbFmtAmount($totalGst),
            'grand_total'      => nbFmtAmount($b['grandTotal'] ?? 0),
            'old_rent'         => !empty($b['oldRent']) ? nbFmtAmount($b['oldRent']) : '',
            'new_rent'         => !empty($b['newRent']) ? nbFmtAmount($b['newRent']) : '',
            'apply_to_all'     => $applyAll === 1,
            'next_increase'    => !empty($b['nextIncreaseAmount']) ? nbFmtAmount($b['nextIncreaseAmount']) : '',
            'next_bill_from'   => trim((string)($b['nextBillFromMonth'] ?? '')),
        ];
        $payload['has_gst'] = $totalGst > 0;
    }

    $payload['gst_invoice_attached'] = $invoiceUrl !== '';
    $payload['gst_invoice_missing']  = $payload['has_gst'] && $invoiceUrl === '';

    return [
        'payload'      => $payload,
        'invoice_urls' => $invoiceUrl !== '' ? [$invoiceUrl] : [],
    ];
}
