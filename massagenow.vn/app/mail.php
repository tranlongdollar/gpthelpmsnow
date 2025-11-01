<?php declare(strict_types=1);
/**
 * /app/mail.php — Gửi email bằng hàm mail() chuẩn, không cần thư viện.
 * - Hỗ trợ HTML + Text alternative (multipart/alternative).
 * - Hỗ trợ CC, BCC, Reply-To, Q-encoding UTF-8 cho tiêu đề/From name.
 * - Nếu mail() fail, ghi log vào /tmp/massagenow_mail.log để kiểm tra.
 *
 * CẦN (khuyến nghị) cấu hình trong /app/config.php:
 *   define('MAIL_FROM', 'no-reply@massagenow.vn');   // cùng domain
 *   define('MAIL_FROM_NAME', 'MassageNow');
 *   define('MAIL_ADMIN_TO', 'booking@massagenow.vn'); // nơi nhận đơn
 */

if (!function_exists('mail_send_html')) {
    /**
     * Gửi mail HTML + text alternative.
     * @param string          $to
     * @param string          $subject
     * @param string          $html
     * @param string          $text
     * @param string|null     $replyTo
     * @param array<string>   $cc
     * @param array<string>   $bcc
     * @return array{ok:bool,error:string}
     */
    function mail_send_html(
        string $to,
        string $subject,
        string $html,
        string $text = '',
        ?string $replyTo = null,
        array $cc = [],
        array $bcc = []
    ): array {
        $fromEmail = defined('MAIL_FROM') ? MAIL_FROM : 'noreply@localhost';
        $fromName  = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Website';
        $replyTo   = $replyTo ?: $fromEmail;

        // Boundary cho multipart/alternative
        try {
            $boundary = '=_mn_' . bin2hex(random_bytes(8));
        } catch (Throwable $e) {
            $boundary = '=_mn_' . uniqid('', true);
        }

        // Chuẩn hóa line ending
        $eol  = "\r\n";
        $text = $text !== '' ? $text : strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $html ?? '') ?? '');

        // Headers
        $headers  = 'From: ' . encode_header($fromName) . " <{$fromEmail}>" . $eol;
        if (!empty($replyTo)) {
            $headers .= 'Reply-To: ' . $replyTo . $eol;
        }
        if (!empty($cc)) {
            $headers .= 'Cc: ' . implode(', ', array_map('trim', $cc)) . $eol;
        }
        if (!empty($bcc)) {
            $headers .= 'Bcc: ' . implode(', ', array_map('trim', $bcc)) . $eol;
        }
        $headers .= 'MIME-Version: 1.0' . $eol;
        $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . $eol;

        // Body (multipart alternative)
        $body  = '--' . $boundary . $eol;
        $body .= 'Content-Type: text/plain; charset=UTF-8' . $eol;
        $body .= 'Content-Transfer-Encoding: 8bit' . $eol . $eol;
        $body .= $text . $eol . $eol;

        $body .= '--' . $boundary . $eol;
        $body .= 'Content-Type: text/html; charset=UTF-8' . $eol;
        $body .= 'Content-Transfer-Encoding: 8bit' . $eol . $eol;
        $body .= (string)$html . $eol . $eol;

        $body .= '--' . $boundary . '--' . $eol;

        // Một số MTA cần -f để set envelope sender (giảm rơi spam)
        $params = '-f' . $fromEmail;

        $ok = @mail($to, encode_header($subject), $body, $headers, $params);
        if (!$ok) {
            mail_log('[FAIL] to=' . $to . ' subj=' . $subject);
            return ['ok' => false, 'error' => 'send_fail'];
        }
        return ['ok' => true, 'error' => ''];
    }

    function encode_header(string $s): string {
        // Q/B-encoding cho UTF-8 header (dùng B để đơn giản/ổn định)
        if (preg_match('/[^\x20-\x7E]/', $s)) {
            return '=?UTF-8?B?' . base64_encode($s) . '?=';
        }
        return $s;
    }

    function mail_log(string $line): void {
        $msg = '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL;
        @file_put_contents('/tmp/massagenow_mail.log', $msg, FILE_APPEND);
    }
}
