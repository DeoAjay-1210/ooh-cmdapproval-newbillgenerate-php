<?php
/**
 * New Bill / Advance CMD Approval - Email Template (rent revision)
 *
 * Same design as the CMD Payment Approved mail (cmd-approval/templates/cmdTemplateSK.php).
 * Design order:
 *  1. Media information
 *  2. New Bill details (month-wise) or Advance details
 *  3. Notes
 *  4. Landlord details
 *
 * No bill dates (last / current / next bill date) in this mail.
 *
 * Email-safe implementation:
 *  - Table-based desktop layout
 *  - Responsive stacking below 640px
 *  - Important visual styles are duplicated inline
 *  - No emoji characters are used
 */

$payload = $payload ?? [];

$is_advance        = $payload['is_advance']        ?? false;
$label             = $payload['label']             ?? '';
$media_code        = $payload['media_code']        ?? '';
$media_name        = $payload['media_name']        ?? '';
$media_type        = $payload['media_type']        ?? '';
$media_address     = $payload['media_address']     ?? '';
$media_size        = $payload['media_size']        ?? '';
$payment_frequency = $payload['payment_frequency'] ?? '';
$gst_pct           = $payload['gst_pct']           ?? '';
$with_gst          = $payload['with_gst']          ?? '';
$is_gst_hold       = $payload['is_gst_hold']       ?? false;
$has_gst           = $payload['has_gst']           ?? false;
$reason            = $payload['reason']            ?? '';
$approved_by       = $payload['approved_by']       ?? '';
$approved_at       = $payload['approved_at']       ?? '';
$land_owners       = $payload['land_owners']       ?? [];
$bill              = $payload['bill']              ?? [];
$advance           = $payload['advance']           ?? [];
$gst_invoice_attached = $payload['gst_invoice_attached'] ?? false;
$gst_invoice_missing  = $payload['gst_invoice_missing']  ?? false;

$is_multiple_landowners = count($land_owners) > 1;
$landlord_count         = count($land_owners);
$mail_title             = $is_advance ? 'Advance Payment Approved' : 'New Bill Payment Approved';

$SECTION_CARD = 'width:100%; margin:0 0 18px 0; border:1px solid #eadfe0; border-radius:12px; overflow:hidden; background:#ffffff;';
$SECTION_HEAD = 'padding:13px 16px; border-bottom:1px solid #eadfe0; background:#ffffff; color:#df1128; font-size:14px; line-height:1.2; font-weight:800; letter-spacing:0.35px; text-transform:uppercase;';
$SECTION_RED  = 'padding:12px 16px; background:#e2182d; color:#ffffff; font-size:13px; line-height:1.2; font-weight:800; letter-spacing:0.35px; text-transform:uppercase;';
$BADGE        = 'display:inline-block; width:30px; height:30px; line-height:30px; margin-right:10px; border-radius:8px; background:#e2182d; color:#ffffff; text-align:center; font-size:11px; font-weight:800; vertical-align:middle;';
$BADGE_LIGHT  = 'display:inline-block; min-width:30px; height:26px; line-height:26px; margin-right:9px; padding:0 7px; border-radius:7px; background:rgba(255,255,255,0.18); color:#ffffff; text-align:center; font-size:10px; font-weight:800; vertical-align:middle;';
$INFO_LABEL   = 'margin:0 0 5px 0; color:#797980; font-size:10px; line-height:1.2; font-weight:700; letter-spacing:0.45px; text-transform:uppercase;';
$INFO_VALUE   = 'margin:0; color:#17171a; font-size:14px; line-height:1.45; font-weight:700; word-break:break-word;';
$RENT_LABEL   = 'width:60%; padding:11px 14px; border-bottom:1px solid #eee5e6; color:#505057; text-align:left; font-size:12px; font-weight:600;';
$RENT_VALUE   = 'padding:11px 14px; border-bottom:1px solid #eee5e6; color:#17171a; text-align:right; font-size:12px; font-weight:700; white-space:nowrap;';
$TOTAL_LABEL  = 'padding:13px 14px; border-bottom:0; background:#e2182d; color:#ffffff; text-align:left; font-size:14px; font-weight:800;';
$TOTAL_VALUE  = 'padding:13px 14px; border-bottom:0; background:#e2182d; color:#ffffff; text-align:right; font-size:14px; font-weight:800; white-space:nowrap;';
$TABLE_HEAD   = 'border:1px solid #eadfe0; background:#f8eef0; color:#2d2d31; font-size:11px; font-weight:700; padding:10px 10px; text-align:center; vertical-align:middle; white-space:nowrap;';
$TABLE_CELL   = 'border:1px solid #eadfe0; background:#ffffff; color:#2d2d31; font-size:11px; font-weight:500; padding:10px 10px; text-align:center; vertical-align:middle;';
$DETAIL_LABEL = 'width:48%; padding:9px 11px; border-bottom:1px solid #eee5e6; background:#fffafa; color:#5b5b61; font-size:11px; font-weight:700; text-align:left;';
$DETAIL_VALUE = 'padding:9px 11px; border-bottom:1px solid #eee5e6; color:#26262a; font-size:11px; font-weight:600; text-align:left; word-break:break-word;';
$DETAIL_RIGHT = 'padding:9px 11px; border-bottom:1px solid #eee5e6; color:#26262a; font-size:11px; font-weight:600; text-align:right;';
$GST_STRIKE   = ' color:#999999; text-decoration:line-through;';
$HOLD_LABEL   = '<span class="hold-label" style="display:inline-block; margin-left:6px; padding:2px 6px; border-radius:4px; background:#fff0f2; color:#b32032; font-size:9px; font-weight:800; text-decoration:none;">ON HOLD</span>';
$NOTE         = 'margin:0 0 4px 0; color:#55555b; font-size:11px; line-height:1.6;';
$NOTE_HOLD    = 'margin:0 0 4px 0; color:#b32032; font-size:11px; line-height:1.6; font-weight:800;';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($mail_title) ?></title>
<style>
  /* Override auto-generated blue hyperlinks from email clients (Gmail, Apple Mail, Outlook) */
  a,
  .responsive-table td a,
  .responsive-table a,
  a[x-apple-data-detectors],
  .no-auto-link,
  .no-auto-link a {
    color: inherit !important;
    text-decoration: none !important;
    font-size: inherit !important;
    font-family: inherit !important;
    font-weight: inherit !important;
    line-height: inherit !important;
  }

  * { box-sizing: border-box; }

  body {
    margin: 0;
    padding: 28px 12px;
    background: #eef0f2;
    font-family: "Segoe UI", Arial, Helvetica, sans-serif;
    color: #202024;
  }

  table { border-spacing: 0; }

  .mobile-label { display: none; }

  @media only screen and (max-width: 640px) {
    body { padding: 0; background: #ffffff; }

    .email-shell { border: 0 !important; border-radius: 0 !important; box-shadow: none !important; }

    .main-content { padding: 18px 12px 4px 12px !important; }

    .media-grid,
    .media-grid tbody,
    .media-grid tr,
    .media-grid td,
    .split-table,
    .split-table tbody,
    .split-table tr,
    .split-column,
    .owner-summary-table,
    .owner-summary-table tbody,
    .owner-summary-table tr,
    .owner-summary-column {
      display: block !important;
      width: 100% !important;
    }

    .media-grid td,
    .split-column:first-child,
    .owner-summary-column:first-child {
      border-right: 0 !important;
      border-bottom: 1px solid #eadfe0 !important;
    }

    .footer-table td {
      display: block !important;
      width: 100% !important;
      padding: 14px 16px !important;
      text-align: left !important;
    }

    .responsive-table thead { display: none !important; }

    .responsive-table,
    .responsive-table tbody,
    .responsive-table tr,
    .responsive-table td {
      display: block !important;
      width: 100% !important;
    }

    .responsive-table tr {
      margin: 0 0 10px 0 !important;
      border: 1px solid #eadfe0 !important;
      border-radius: 8px !important;
      overflow: hidden !important;
    }

    .responsive-table td {
      padding: 8px 10px !important;
      border: 0 !important;
      border-bottom: 1px solid #eadfe0 !important;
      text-align: left !important;
      white-space: normal !important;
    }

    .responsive-table td:last-child { border-bottom: 0 !important; }

    .mobile-label {
      display: inline-block !important;
      min-width: 110px;
      color: #b8192d;
      font-weight: 800;
    }
  }
</style>
</head>
<body>
<div class="email-shell" style="width:100%; max-width:920px; margin:0 auto; background:#ffffff; border:1px solid #e2e4e7; border-radius:14px; overflow:hidden; box-shadow:0 8px 30px rgba(28,28,32,0.10);">

  <!-- HEADER -->
  <div style="width:100%; line-height:0; font-size:0;">
    <img
      class="header-banner"
      src="https://adinndigital.com/api/outdoormedia/templates/image/header-banner2.png"
      alt="ADINN <?= htmlspecialchars($mail_title) ?>"
      style="width:100%; height:auto; display:block; border:0;"
    >
  </div>

  <div class="main-content" style="padding:24px 28px 8px 28px;">

    <!-- INTRODUCTION -->
    <h2 style="margin:0 0 6px 0; font-size:18px; line-height:1.3; color:#17171a; font-weight:700;">Hello Team,</h2>
    <p style="margin:0 0 20px 0; font-size:13px; line-height:1.65; color:#63636a;">
      <?= htmlspecialchars($mail_title) ?> &ndash; Ready for Disbursement.
      <?php if ($label !== ''): ?>
        <br><strong style="color:#17171a;"><?= htmlspecialchars($label) ?></strong>
      <?php endif; ?>
    </p>

    <!-- MEDIA INFORMATION -->
    <div class="section-card" style="<?= $SECTION_CARD ?>">
      <div class="section-heading" style="<?= $SECTION_HEAD ?>">
        <span class="heading-badge" style="<?= $BADGE ?>">MI</span>
        Media Information
      </div>
      <table class="media-grid" role="presentation" style="width:100%; border-collapse:collapse; table-layout:fixed;">
        <tr>
          <td style="width:50%; padding:15px 16px; border-right:1px solid #eadfe0; border-bottom:1px solid #eadfe0; vertical-align:middle;">
            <div style="<?= $INFO_LABEL ?>">Media Name</div>
            <div style="<?= $INFO_VALUE ?>"><?= htmlspecialchars(nbText($media_name)) ?></div>
          </td>
          <td style="width:50%; padding:15px 16px; border-bottom:1px solid #eadfe0; vertical-align:middle;">
            <div style="<?= $INFO_LABEL ?>">Media Code</div>
            <div style="<?= $INFO_VALUE ?>"><span class="no-auto-link"><?= htmlspecialchars(nbText($media_code)) ?></span></div>
          </td>
        </tr>
        <tr>
          <td style="width:50%; padding:15px 16px; border-right:1px solid #eadfe0; border-bottom:1px solid #eadfe0; vertical-align:middle;">
            <div style="<?= $INFO_LABEL ?>">Media Type</div>
            <div style="<?= $INFO_VALUE ?>"><?= htmlspecialchars(nbText($media_type)) ?></div>
          </td>
          <td style="width:50%; padding:15px 16px; border-bottom:1px solid #eadfe0; vertical-align:middle;">
            <div style="<?= $INFO_LABEL ?>">Size</div>
            <div style="<?= $INFO_VALUE ?>"><?= htmlspecialchars(nbText($media_size)) ?></div>
          </td>
        </tr>
        <tr>
          <td style="width:50%; padding:15px 16px; border-right:1px solid #eadfe0; vertical-align:middle;">
            <div style="<?= $INFO_LABEL ?>">Media Address</div>
            <div style="<?= $INFO_VALUE ?>"><?= nl2br(htmlspecialchars(nbText($media_address))) ?></div>
          </td>
          <td style="width:50%; padding:15px 16px; vertical-align:middle;">
            <div style="<?= $INFO_LABEL ?>">Payment Frequency</div>
            <div style="<?= $INFO_VALUE ?>"><?= htmlspecialchars(nbText($payment_frequency)) ?></div>
          </td>
        </tr>
      </table>
    </div>

    <?php if (!$is_advance): ?>
    <!-- NEW BILL DETAILS (month-wise) -->
    <div class="section-card" style="<?= $SECTION_CARD ?>">
      <div class="section-heading" style="<?= $SECTION_HEAD ?>">
        <span class="heading-badge" style="<?= $BADGE ?>">NB</span>
        New Bill Details
      </div>

      <div style="width:100%; overflow-x:auto;">
        <table class="responsive-table" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="<?= $TABLE_HEAD ?>">Month</th>
              <th style="<?= $TABLE_HEAD ?>">Type</th>
              <th style="<?= $TABLE_HEAD ?>">Amount</th>
              <?php if ($has_gst): ?>
              <th style="<?= $TABLE_HEAD ?>">GST<?= $gst_pct !== '' ? ' (' . htmlspecialchars($gst_pct) . '%)' : '' ?></th>
              <?php endif; ?>
              <th style="<?= $TABLE_HEAD ?>">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (($bill['rows'] ?? []) as $row): ?>
            <tr>
              <td style="<?= $TABLE_CELL ?>"><span class="mobile-label">Month:</span><?= htmlspecialchars($row['month']) ?></td>
              <td style="<?= $TABLE_CELL ?>"><span class="mobile-label">Type:</span><?= htmlspecialchars($row['type']) ?></td>
              <td style="<?= $TABLE_CELL ?>"><span class="mobile-label">Amount:</span>&#8377; <?= htmlspecialchars($row['amount']) ?></td>
              <?php if ($has_gst): ?>
              <td style="<?= $TABLE_CELL ?><?= $is_gst_hold ? $GST_STRIKE : '' ?>"><span class="mobile-label">GST:</span>&#8377; <?= htmlspecialchars($row['gst_amount']) ?></td>
              <?php endif; ?>
              <td style="<?= $TABLE_CELL ?> font-weight:700;"><span class="mobile-label">Total:</span>&#8377; <?= htmlspecialchars($row['total']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <table class="split-table" role="presentation" style="width:100%; border-collapse:collapse; table-layout:fixed;">
        <tr>
          <!-- LEFT: TOTALS -->
          <td class="split-column" style="width:50%; padding:0; vertical-align:top; border:0; border-right:1px solid #eadfe0;">
            <table role="presentation" style="width:100%; border-collapse:collapse;">
              <tr>
                <td style="<?= $RENT_LABEL ?>">Total Amount</td>
                <td style="<?= $RENT_VALUE ?>">&#8377; <?= htmlspecialchars($bill['total_amount'] ?? '0') ?></td>
              </tr>
              <?php if ($has_gst): ?>
              <tr>
                <td style="<?= $RENT_LABEL ?><?= $is_gst_hold ? $GST_STRIKE : '' ?>">Total GST<?= $gst_pct !== '' ? ' (' . htmlspecialchars($gst_pct) . '%)' : '' ?></td>
                <td style="<?= $RENT_VALUE ?><?= $is_gst_hold ? $GST_STRIKE : '' ?>">&#8377; <?= htmlspecialchars($bill['total_gst'] ?? '0') ?><?= $is_gst_hold ? $HOLD_LABEL : '' ?></td>
              </tr>
              <?php endif; ?>
              <tr>
                <td style="<?= $TOTAL_LABEL ?>">Grand Total Payable</td>
                <td style="<?= $TOTAL_VALUE ?>">&#8377; <?= htmlspecialchars($bill['grand_total'] ?? '0') ?></td>
              </tr>
            </table>
          </td>

          <!-- RIGHT: RENT CHANGE -->
          <td class="split-column" style="width:50%; padding:0; vertical-align:top; border:0;">
            <table role="presentation" style="width:100%; border-collapse:collapse;">
              <?php if (($bill['old_rent'] ?? '') !== ''): ?>
              <tr>
                <td style="<?= $RENT_LABEL ?>">Old Rent</td>
                <td style="<?= $RENT_VALUE ?>">&#8377; <?= htmlspecialchars($bill['old_rent']) ?></td>
              </tr>
              <?php endif; ?>
              <?php if (($bill['new_rent'] ?? '') !== ''): ?>
              <tr>
                <td style="<?= $RENT_LABEL ?>">New Rent</td>
                <td style="<?= $RENT_VALUE ?>">&#8377; <?= htmlspecialchars($bill['new_rent']) ?></td>
              </tr>
              <?php endif; ?>
              <tr>
                <td style="<?= $RENT_LABEL ?>">Apply to Next Bills</td>
                <td style="<?= $RENT_VALUE ?>"><?= !empty($bill['apply_to_all']) ? 'Yes' : 'No' ?></td>
              </tr>
              <?php if (!empty($bill['apply_to_all']) && ($bill['next_increase'] ?? '') !== ''): ?>
              <tr>
                <td style="<?= $RENT_LABEL ?>">Next Bill Increase</td>
                <td style="<?= $RENT_VALUE ?>">&#8377; <?= htmlspecialchars($bill['next_increase']) ?></td>
              </tr>
              <?php endif; ?>
              <?php if (!empty($bill['apply_to_all']) && ($bill['next_bill_from'] ?? '') !== ''): ?>
              <tr>
                <td style="<?= $RENT_LABEL ?>">Increase From</td>
                <td style="<?= $RENT_VALUE ?>"><?= htmlspecialchars($bill['next_bill_from']) ?></td>
              </tr>
              <?php endif; ?>
            </table>
          </td>
        </tr>
      </table>
    </div>
    <?php else: ?>
    <!-- ADVANCE DETAILS -->
    <div class="section-card" style="<?= $SECTION_CARD ?>">
      <div class="section-heading" style="<?= $SECTION_HEAD ?>">
        <span class="heading-badge" style="<?= $BADGE ?>">AD</span>
        Advance Details
      </div>

      <table class="split-table" role="presentation" style="width:100%; border-collapse:collapse; table-layout:fixed;">
        <tr>
          <!-- LEFT: ADVANCE AMOUNT -->
          <td class="split-column" style="width:50%; padding:0; vertical-align:top; border:0; border-right:1px solid #eadfe0;">
            <table role="presentation" style="width:100%; border-collapse:collapse;">
              <tr>
                <td style="<?= $RENT_LABEL ?>">Advance Month</td>
                <td style="<?= $RENT_VALUE ?>"><?= htmlspecialchars(nbText($advance['advance_month'] ?? '')) ?></td>
              </tr>
              <tr>
                <td style="<?= $RENT_LABEL ?>">Advance Amount</td>
                <td style="<?= $RENT_VALUE ?>">&#8377; <?= htmlspecialchars($advance['amount'] ?? '0') ?></td>
              </tr>
              <?php if (!empty($advance['has_gst'])): ?>
              <tr>
                <td style="<?= $RENT_LABEL ?><?= $is_gst_hold ? $GST_STRIKE : '' ?>">GST<?= $gst_pct !== '' ? ' (' . htmlspecialchars($gst_pct) . '%)' : '' ?></td>
                <td style="<?= $RENT_VALUE ?><?= $is_gst_hold ? $GST_STRIKE : '' ?>">&#8377; <?= htmlspecialchars($advance['gst_amount']) ?><?= $is_gst_hold ? $HOLD_LABEL : '' ?></td>
              </tr>
              <?php endif; ?>
              <tr>
                <td style="<?= $TOTAL_LABEL ?>">Grand Total Payable</td>
                <td style="<?= $TOTAL_VALUE ?>">&#8377; <?= htmlspecialchars($advance['grand_total'] ?? '0') ?></td>
              </tr>
            </table>
          </td>

          <!-- RIGHT: ADJUSTMENT -->
          <td class="split-column" style="width:50%; padding:0; vertical-align:top; border:0;">
            <table role="presentation" style="width:100%; border-collapse:collapse;">
              <tr>
                <td style="<?= $RENT_LABEL ?>">Adjust In Bill</td>
                <td style="<?= $RENT_VALUE ?>"><?= htmlspecialchars(nbText($advance['adjust_in'] ?? '')) ?></td>
              </tr>
              <tr>
                <td style="<?= $RENT_LABEL ?>">Bill Amount</td>
                <td style="<?= $RENT_VALUE ?>">&#8377; <?= htmlspecialchars($advance['bill_amount'] ?? '0') ?></td>
              </tr>
              <tr>
                <td style="<?= $RENT_LABEL ?>">Less: Advance</td>
                <td style="<?= $RENT_VALUE ?> color:#b32032;">- &#8377; <?= htmlspecialchars($advance['amount'] ?? '0') ?></td>
              </tr>
              <tr>
                <td style="<?= $RENT_LABEL ?> border-bottom:0; font-weight:800; color:#17171a;">Balance Bill Amount</td>
                <td style="<?= $RENT_VALUE ?> border-bottom:0; font-weight:800;">&#8377; <?= htmlspecialchars($advance['balance_amount'] ?? '0') ?></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </div>
    <?php endif; ?>

    <!-- NOTES -->
    <div class="notes-box" style="margin:0 0 18px 0; padding:13px 15px; border:1px solid #f0d6da; border-left:4px solid #e2182d; border-radius:8px; background:#fff8f8;">
      <div style="margin:0 0 6px 0; color:#202024; font-size:12px; line-height:1.3; font-weight:800;">Notes</div>
      <?php if ($reason !== ''): ?>
      <p style="<?= $NOTE ?>"><strong>Reason:</strong> <?= htmlspecialchars($reason) ?></p>
      <?php endif; ?>
      <?php if ($is_advance): ?>
      <p style="<?= $NOTE ?>">This advance will be deducted from the <?= htmlspecialchars(nbText($advance['adjust_in'] ?? '', 'next')) ?> bill.</p>
      <?php endif; ?>
      <?php if ($has_gst): ?>
      <p style="<?= $NOTE ?>">GST: <?= htmlspecialchars($with_gst) ?>.</p>
      <?php endif; ?>
      <?php if ($gst_invoice_attached): ?>
      <p style="<?= $NOTE ?>">Invoice PDF attached.</p>
      <?php elseif ($gst_invoice_missing): ?>
      <p style="<?= $NOTE_HOLD ?>">GST invoice PDF is required but was not provided.</p>
      <?php endif; ?>
      <?php if ($is_gst_hold && $has_gst): ?>
      <p style="<?= $NOTE_HOLD ?>">GST is on hold.</p>
      <?php endif; ?>
      <!-- <?php if ($approved_by !== '' || $approved_at !== ''): ?>
      <p style="<?= $NOTE ?>">Approved by CMD<?= $approved_by !== '' ? ': ' . htmlspecialchars($approved_by) : '' ?><?= $approved_at !== '' ? ' on ' . htmlspecialchars($approved_at) : '' ?>.</p>
      <?php endif; ?> -->
    </div>

    <!-- LANDLORD DETAILS -->
    <?php if (!empty($land_owners)): ?>
    <div class="section-card" style="<?= $SECTION_CARD ?>">
      <div style="<?= $SECTION_RED ?>">
        <span style="<?= $BADGE_LIGHT ?>">LO</span>
        <?= $is_multiple_landowners ? 'Multiple Landlord Details' : 'Landlord Details' ?>
        <span style="font-weight:600; opacity:0.9;">(<?= htmlspecialchars((string)$landlord_count) ?>)</span>
      </div>

      <div style="padding:14px;">
        <?php foreach ($land_owners as $idx => $owner): ?>
        <div style="margin:0 0 14px 0; border:1px solid #eadfe0; border-radius:10px; overflow:hidden; background:#ffffff;">
          <div style="padding:11px 14px; background:#2b2b2f; color:#ffffff; font-size:12px; line-height:1.35; font-weight:800;">
            LAND OWNER <?= htmlspecialchars(str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT)) ?>
            <span style="color:#ffffff; font-weight:600;"> - <?= htmlspecialchars($owner['owner_name']) ?></span>
          </div>

          <table class="owner-summary-table" role="presentation" style="width:100%; border-collapse:collapse; table-layout:fixed;">
            <tr>
              <!-- OWNER AND BANK DETAILS -->
              <td class="owner-summary-column" style="width:50%; padding:0; border:0; border-right:1px solid #eadfe0; vertical-align:top;">
                <div style="padding:9px 12px; background:#fff5f6; border-bottom:1px solid #eadfe0; color:#b8192d; font-size:10px; line-height:1.3; font-weight:800; letter-spacing:0.5px; text-transform:uppercase;">Owner and Bank Details</div>
                <table role="presentation" style="width:100%; border-collapse:collapse;">
                  <tr><td style="<?= $DETAIL_LABEL ?>">Owner Name</td><td style="<?= $DETAIL_VALUE ?>"><?= htmlspecialchars($owner['owner_name']) ?></td></tr>
                  <tr><td style="<?= $DETAIL_LABEL ?>">Phone</td><td style="<?= $DETAIL_VALUE ?>"><span class="no-auto-link"><?= htmlspecialchars($owner['phone']) ?></span></td></tr>
                  <tr><td style="<?= $DETAIL_LABEL ?>">Bank Name</td><td style="<?= $DETAIL_VALUE ?>"><?= htmlspecialchars($owner['bank_name']) ?></td></tr>
                  <tr><td style="<?= $DETAIL_LABEL ?>">IFSC Code</td><td style="<?= $DETAIL_VALUE ?>"><?= htmlspecialchars($owner['ifsc']) ?></td></tr>
                  <tr><td style="<?= $DETAIL_LABEL ?>">Account No</td><td style="<?= $DETAIL_VALUE ?>"><span class="no-auto-link"><?= htmlspecialchars($owner['account_no']) ?></span></td></tr>
                  <tr><td style="<?= $DETAIL_LABEL ?>">PAN Number</td><td style="<?= $DETAIL_VALUE ?>"><?= htmlspecialchars($owner['pan']) ?></td></tr>
                  <tr><td style="<?= $DETAIL_LABEL ?>">Payment Category</td><td style="<?= $DETAIL_VALUE ?>"><?= htmlspecialchars(nbText($owner['payment_category'])) ?></td></tr>
                  <tr><td style="<?= $DETAIL_LABEL ?><?= $owner['gst_number'] === '' ? ' border-bottom:0;' : '' ?>">Online Mode</td><td style="<?= $DETAIL_VALUE ?><?= $owner['gst_number'] === '' ? ' border-bottom:0;' : '' ?>"><?= htmlspecialchars($owner['online_mode']) ?></td></tr>
                  <?php if ($owner['gst_number'] !== ''): ?>
                  <tr><td style="<?= $DETAIL_LABEL ?> border-bottom:0;">GST Number</td><td style="<?= $DETAIL_VALUE ?> border-bottom:0;"><?= htmlspecialchars($owner['gst_number']) ?></td></tr>
                  <?php endif; ?>
                </table>
              </td>

              <!-- PAYMENT BREAKDOWN (this bill / advance) -->
              <td class="owner-summary-column" style="width:50%; padding:0; border:0; vertical-align:top;">
                <div style="padding:9px 12px; background:#fff5f6; border-bottom:1px solid #eadfe0; color:#b8192d; font-size:10px; line-height:1.3; font-weight:800; letter-spacing:0.5px; text-transform:uppercase;">Payment Breakdown</div>
                <table role="presentation" style="width:100%; border-collapse:collapse;">
                  <tr><td style="<?= $DETAIL_LABEL ?>"><?= $is_advance ? 'Advance Share' : 'Share Amount' ?></td><td style="<?= $DETAIL_RIGHT ?>">&#8377; <?= htmlspecialchars($owner['share_amount']) ?></td></tr>
                  <tr><td style="<?= $DETAIL_LABEL ?>">Online Amount</td><td style="<?= $DETAIL_RIGHT ?>">&#8377; <?= htmlspecialchars($owner['online_amount']) ?></td></tr>
                  <tr><td style="<?= $DETAIL_LABEL ?>">Cash Amount</td><td style="<?= $DETAIL_RIGHT ?>">&#8377; <?= htmlspecialchars($owner['cash_amount']) ?></td></tr>
                  <?php if ($owner['has_gst']): ?>
                  <tr><td style="<?= $DETAIL_LABEL ?><?= $is_gst_hold ? $GST_STRIKE : '' ?>">GST Amount</td><td style="<?= $DETAIL_RIGHT ?><?= $is_gst_hold ? $GST_STRIKE : '' ?>">&#8377; <?= htmlspecialchars($owner['gst_amount']) ?></td></tr>
                  <?php endif; ?>
                  <tr>
                    <td style="padding:10px 11px; border-bottom:0; background:#fff3f4; color:#b8192d; font-size:11px; font-weight:800; text-align:left;">Total Amount</td>
                    <td style="padding:10px 11px; border-bottom:0; background:#fff3f4; color:#b8192d; font-size:11px; font-weight:800; text-align:right;">&#8377; <?= htmlspecialchars($owner['total_amount']) ?></td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- FOOTER -->
  <table class="footer-table" role="presentation" style="width:100%; background:#f3f4f5; border-collapse:collapse;">
    <tr>
      <td style="padding:18px 28px; border:0; background:#f3f4f5; color:#56565c; font-size:10px; line-height:1.55; vertical-align:middle;">
        This is an automated email from ADINN Rental Management System.<br>
        Kindly review and verify the details.
      </td>
      <td style="padding:18px 28px; border:0; background:#f3f4f5; color:#2f2f34; font-size:10px; line-height:1.55; vertical-align:middle; text-align:right; white-space:nowrap; font-weight:700;">
        &copy; <?= date('Y') ?> Adinn Advertising Services Ltd. All Rights Reserved.
      </td>
    </tr>
  </table>

</div>
</body>
</html>
