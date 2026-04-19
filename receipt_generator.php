<?php
/**
 * PDF Receipt Generator
 * Generate professional receipts for payments
 * Requires TCPDF library (install via composer: composer require tecnickcom/tcpdf)
 */

require_once 'db_connect.php';

// If TCPDF is not available via Composer, download from: https://github.com/tecnickcom/TCPDF
// Uncomment the following line and adjust the path if needed:
// require_once('tcpdf/tcpdf.php');

class ReceiptGenerator {
    
    /**
     * Generate payment receipt PDF
     * 
     * @param int $paymentId
     * @param bool $download Whether to force download or display inline
     * @return bool|string
     */
    public static function generateReceipt(int $paymentId, bool $download = true): bool|string {
        try {
            // Check if TCPDF is available
            if (!class_exists('TCPDF')) {
                return self::generateHTMLReceipt($paymentId);
            }

            // Get payment details
            $payment = self::getPaymentDetails($paymentId);
            
            if (!$payment) {
                return false;
            }

            // Get gym settings
            $gymName = get_setting('gym_name', 'Gym Management System');
            $gymAddress = get_setting('gym_address', '');
            $gymPhone = get_setting('gym_phone', '');
            $gymEmail = get_setting('gym_email', '');

            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator('Gym Management System');
            $pdf->SetAuthor($gymName);
            $pdf->SetTitle('Payment Receipt - ' . $payment['receipt_number']);

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set margins
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);

            // Add a page
            $pdf->AddPage();

            // Set font
            $pdf->SetFont('helvetica', '', 10);

            // Build HTML content
            $html = self::buildReceiptHTML($payment, $gymName, $gymAddress, $gymPhone, $gymEmail);

            // Print text using writeHTMLCell()
            $pdf->writeHTML($html, true, false, true, false, '');

            // Output PDF
            $filename = 'Receipt_' . $payment['receipt_number'] . '.pdf';
            
            if ($download) {
                $pdf->Output($filename, 'D'); // Force download
            } else {
                $pdf->Output($filename, 'I'); // Inline display
            }
            
            return true;

        } catch (Exception $e) {
            error_log("Receipt generation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build HTML content for receipt
     * 
     * @param array $payment
     * @param string $gymName
     * @param string $gymAddress
     * @param string $gymPhone
     * @param string $gymEmail
     * @return string
     */
    private static function buildReceiptHTML(
        array $payment, 
        string $gymName, 
        string $gymAddress, 
        string $gymPhone, 
        string $gymEmail
    ): string {
        $html = '
        <style>
            .header { text-align: center; margin-bottom: 20px; }
            .gym-name { font-size: 20px; font-weight: bold; color: #2c3e50; }
            .gym-info { font-size: 10px; color: #7f8c8d; margin-top: 5px; }
            .receipt-title { font-size: 16px; font-weight: bold; text-align: center; 
                            margin: 20px 0; padding: 10px; background-color: #3498db; color: white; }
            .info-table { width: 100%; margin: 20px 0; }
            .info-table td { padding: 8px; border-bottom: 1px solid #ecf0f1; }
            .label { font-weight: bold; width: 40%; color: #34495e; }
            .value { width: 60%; color: #2c3e50; }
            .amount-box { text-align: center; margin: 30px 0; padding: 20px; 
                         background-color: #ecf0f1; border-radius: 5px; }
            .amount-label { font-size: 12px; color: #7f8c8d; }
            .amount-value { font-size: 24px; font-weight: bold; color: #27ae60; margin-top: 5px; }
            .footer { margin-top: 40px; text-align: center; font-size: 9px; color: #95a5a6; }
            .signature { margin-top: 40px; text-align: right; }
            .signature-line { border-top: 1px solid #34495e; width: 200px; 
                             display: inline-block; margin-top: 50px; }
        </style>

        <div class="header">
            <div class="gym-name">' . htmlspecialchars($gymName) . '</div>
            <div class="gym-info">
                ' . htmlspecialchars($gymAddress) . '<br>
                Phone: ' . htmlspecialchars($gymPhone) . ' | Email: ' . htmlspecialchars($gymEmail) . '
            </div>
        </div>

        <div class="receipt-title">PAYMENT RECEIPT</div>

        <table class="info-table">
            <tr>
                <td class="label">Receipt Number:</td>
                <td class="value">' . htmlspecialchars($payment['receipt_number']) . '</td>
            </tr>
            <tr>
                <td class="label">Date:</td>
                <td class="value">' . date('F d, Y', strtotime($payment['payment_date'])) . '</td>
            </tr>
            <tr>
                <td class="label">Member Name:</td>
                <td class="value">' . htmlspecialchars($payment['member_name']) . '</td>
            </tr>
            <tr>
                <td class="label">Member Code:</td>
                <td class="value">' . htmlspecialchars($payment['member_code']) . '</td>
            </tr>
            <tr>
                <td class="label">Phone:</td>
                <td class="value">' . htmlspecialchars($payment['member_phone']) . '</td>
            </tr>
            <tr>
                <td class="label">Payment Type:</td>
                <td class="value">' . htmlspecialchars($payment['payment_type']) . '</td>
            </tr>
            <tr>
                <td class="label">Payment Method:</td>
                <td class="value">' . htmlspecialchars($payment['payment_method']) . '</td>
            </tr>';

        if ($payment['membership_type']) {
            $html .= '
            <tr>
                <td class="label">Membership Type:</td>
                <td class="value">' . htmlspecialchars($payment['membership_type']) . '</td>
            </tr>
            <tr>
                <td class="label">Membership Period:</td>
                <td class="value">' . 
                    date('M d, Y', strtotime($payment['membership_start'])) . ' to ' . 
                    date('M d, Y', strtotime($payment['membership_end'])) . 
                '</td>
            </tr>';
        }

        if ($payment['transaction_id']) {
            $html .= '
            <tr>
                <td class="label">Transaction ID:</td>
                <td class="value">' . htmlspecialchars($payment['transaction_id']) . '</td>
            </tr>';
        }

        $html .= '
        </table>

        <div class="amount-box">
            <div class="amount-label">Total Amount Paid</div>
            <div class="amount-value">$' . number_format($payment['amount'], 2) . '</div>
        </div>';

        if ($payment['notes']) {
            $html .= '
            <div style="margin: 20px 0; padding: 10px; background-color: #fff9e6; border-left: 3px solid #f39c12;">
                <strong>Notes:</strong> ' . htmlspecialchars($payment['notes']) . '
            </div>';
        }

        $html .= '
        <div class="signature">
            <div class="signature-line"></div><br>
            <span style="font-size: 10px;">Authorized Signature</span>
        </div>

        <div class="footer">
            This is a computer-generated receipt and does not require a signature.<br>
            Thank you for choosing ' . htmlspecialchars($gymName) . '!<br>
            Generated on ' . date('F d, Y h:i A') . '
        </div>';

        return $html;
    }

    /**
     * Get payment details for receipt
     * 
     * @param int $paymentId
     * @return array|false
     */
    private static function getPaymentDetails(int $paymentId): array|false {
        $query = "SELECT 
            p.*,
            CONCAT(m.first_name, ' ', m.last_name) as member_name,
            m.member_code,
            m.phone as member_phone,
            m.email as member_email,
            pt.type_name as payment_type,
            mt.type_name as membership_type,
            ms.start_date as membership_start,
            ms.end_date as membership_end
        FROM payments p
        JOIN members m ON p.member_id = m.member_id
        JOIN payment_types pt ON p.payment_type_id = pt.payment_type_id
        LEFT JOIN memberships ms ON p.membership_id = ms.membership_id
        LEFT JOIN membership_types mt ON ms.type_id = mt.type_id
        WHERE p.payment_id = ?";

        return Database::querySingle($query, [$paymentId]);
    }

    /**
     * Generate HTML receipt (fallback when TCPDF is not available)
     * 
     * @param int $paymentId
     * @return string
     */
    public static function generateHTMLReceipt(int $paymentId): string {
        $payment = self::getPaymentDetails($paymentId);
        
        if (!$payment) {
            return '<p>Receipt not found</p>';
        }

        $gymName = get_setting('gym_name', 'Gym Management System');
        $gymAddress = get_setting('gym_address', '');
        $gymPhone = get_setting('gym_phone', '');
        $gymEmail = get_setting('gym_email', '');

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt - ' . htmlspecialchars($payment['receipt_number']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        ' . self::getHTMLStyles() . '
    </style>
</head>
<body>
    ' . self::buildReceiptHTML($payment, $gymName, $gymAddress, $gymPhone, $gymEmail) . '
    <div style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #3498db; color: white; 
                border: none; border-radius: 5px; cursor: pointer;">Print Receipt</button>
    </div>
</body>
</html>';
    }

    /**
     * Get HTML styles
     * 
     * @return string
     */
    private static function getHTMLStyles(): string {
        return '
            .header { text-align: center; margin-bottom: 20px; }
            .gym-name { font-size: 24px; font-weight: bold; color: #2c3e50; }
            .gym-info { font-size: 12px; color: #7f8c8d; margin-top: 5px; }
            .receipt-title { font-size: 18px; font-weight: bold; text-align: center; 
                            margin: 20px 0; padding: 10px; background-color: #3498db; color: white; }
            .info-table { width: 100%; margin: 20px 0; border-collapse: collapse; }
            .info-table td { padding: 12px; border-bottom: 1px solid #ecf0f1; }
            .label { font-weight: bold; width: 40%; color: #34495e; }
            .value { width: 60%; color: #2c3e50; }
            .amount-box { text-align: center; margin: 30px 0; padding: 20px; 
                         background-color: #ecf0f1; border-radius: 5px; }
            .amount-label { font-size: 14px; color: #7f8c8d; }
            .amount-value { font-size: 32px; font-weight: bold; color: #27ae60; margin-top: 5px; }
            .footer { margin-top: 40px; text-align: center; font-size: 11px; color: #95a5a6; }
            .signature { margin-top: 40px; text-align: right; }
            .signature-line { border-top: 2px solid #34495e; width: 200px; 
                             display: inline-block; margin-top: 50px; }
            @media print {
                button { display: none; }
            }
        ';
    }
}

// Handle receipt generation requests
if (isset($_GET['payment_id']) && isset($_GET['action']) && $_GET['action'] === 'generate_receipt') {
    $paymentId = (int)$_GET['payment_id'];
    $download = isset($_GET['download']) && $_GET['download'] === 'true';
    
    if (class_exists('TCPDF')) {
        ReceiptGenerator::generateReceipt($paymentId, $download);
    } else {
        echo ReceiptGenerator::generateHTMLReceipt($paymentId);
    }
    exit;
}
?>
