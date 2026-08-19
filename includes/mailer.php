<?php
/**
 * Lightweight SMTP mailer (no dependencies) + enquiry notification helpers.
 *
 * Supports: ssl:// (implicit TLS), STARTTLS, or plain. AUTH LOGIN.
 * Falls back to PHP mail() when SMTP is not configured.
 * Never throws to the caller — returns a result array instead, so a mail
 * failure can never lose a stored enquiry.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

class SmtpClient
{
    private $sock;
    private string $host;
    private int $port;
    private string $encryption;

    public function __construct(string $host, int $port, string $encryption = 'tls')
    {
        $this->host = $host;
        $this->port = $port;
        $this->encryption = strtolower($encryption);
    }

    /** @throws RuntimeException on protocol errors */
    public function send(string $from, array $to, string $subject, string $html, string $text,
                         string $fromName = '', ?string $user = null, ?string $pass = null,
                         array $cc = [], ?string $replyTo = null): void
    {
        $remote = ($this->encryption === 'ssl' ? 'ssl://' : '') . $this->host;
        $errno = 0; $errstr = '';
        $ctx = stream_context_create(['ssl' => [
            'verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false,
        ]]);
        $this->sock = @stream_socket_client($remote . ':' . $this->port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
        if (!$this->sock) {
            throw new RuntimeException("SMTP connect failed: $errstr ($errno)");
        }
        stream_set_timeout($this->sock, 20);
        $this->expect([220]);

        $ehlo = $this->ehlo();
        if ($this->encryption === 'tls') {
            $this->cmd('STARTTLS');
            $this->expect([220]);
            if (!stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLS negotiation failed');
            }
            $this->ehlo();
        }

        if ($user !== null && $user !== '') {
            $this->cmd('AUTH LOGIN');
            $this->expect([334]);
            $this->cmd(base64_encode($user));
            $this->expect([334]);
            $this->cmd(base64_encode((string) $pass));
            $this->expect([235]);
        }

        $this->cmd('MAIL FROM:<' . $from . '>');
        $this->expect([250]);
        foreach (array_merge($to, $cc) as $rcpt) {
            $this->cmd('RCPT TO:<' . $rcpt . '>');
            $this->expect([250, 251]);
        }
        $this->cmd('DATA');
        $this->expect([354]);

        $boundary = '=_gio_' . bin2hex(random_bytes(12));
        $headers = [];
        $headers[] = 'Date: ' . date(DATE_RFC2822);
        $headers[] = 'From: ' . $this->encodeHeader($fromName ?: $from) . ' <' . $from . '>';
        $headers[] = 'To: ' . implode(', ', $to);
        if ($cc) $headers[] = 'Cc: ' . implode(', ', $cc);
        if ($replyTo) $headers[] = 'Reply-To: ' . $replyTo;
        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . preg_replace('#^https?://#', '', SITE_URL) . '>';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        $body  = '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($text));
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=utf-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($html));
        $body .= '--' . $boundary . "--\r\n";

        $data = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
        $this->cmd($data);
        $this->expect([250]);
        $this->cmd('QUIT');
        fclose($this->sock);
    }

    private function ehlo(): void
    {
        $this->cmd('EHLO ' . (preg_replace('#^https?://#', '', SITE_URL) ?: 'localhost'));
        $this->expect([250]);
    }

    private function cmd(string $line): void
    {
        fwrite($this->sock, $line . "\r\n");
    }

    /** @throws RuntimeException */
    private function expect(array $codes): string
    {
        $resp = '';
        while (($l = fgets($this->sock, 515)) !== false) {
            $resp .= $l;
            if (strlen($l) >= 4 && $l[3] === ' ') break;
        }
        $code = (int) substr($resp, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new RuntimeException('SMTP error: ' . trim($resp));
        }
        return $resp;
    }

    private function encodeHeader(string $s): string
    {
        if (preg_match('/[^\x20-\x7E]/', $s)) {
            return '=?UTF-8?B?' . base64_encode($s) . '?=';
        }
        return $s;
    }
}

/** Effective mail settings: DB settings override config constants. */
function mail_config(): array
{
    return [
        'host'         => setting('smtp_host', SMTP_HOST),
        'port'         => (int) setting('smtp_port', (string) SMTP_PORT),
        'user'         => setting('smtp_user', SMTP_USER),
        'pass'         => setting('smtp_pass', SMTP_PASS),
        'encryption'   => setting('smtp_encryption', SMTP_ENCRYPTION),
        'from_email'   => setting('mail_from_email', 'noreply@' . preg_replace('#^https?://(www\.)?#', '', SITE_URL)),
        'from_name'    => setting('mail_from_name', SITE_NAME),
        'notify_email' => setting('mail_notify_email', ''),
        'cc_email'     => setting('mail_cc_email', ''),
        'send_ack'     => setting('mail_send_ack', '1') === '1',
        'footer'       => setting('mail_footer', ''),
    ];
}

/**
 * Send one email. Returns ['ok'=>bool,'error'=>string,'transport'=>'smtp|mail|none'].
 */
function send_mail(string $to, string $subject, string $html, string $text = '', ?string $replyTo = null, array $cc = []): array
{
    $cfg = mail_config();
    $text = $text !== '' ? $text : trim(preg_replace('/\n{3,}/', "\n\n", strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], ["\n", "\n", "\n", "\n\n", "\n"], $html))));

    if ($cfg['host'] !== '') {
        try {
            $smtp = new SmtpClient($cfg['host'], $cfg['port'] ?: 587, $cfg['encryption']);
            $smtp->send($cfg['from_email'], [$to], $subject, $html, $text, $cfg['from_name'],
                        $cfg['user'] ?: null, $cfg['pass'] ?: null, $cc, $replyTo);
            return ['ok' => true, 'error' => '', 'transport' => 'smtp'];
        } catch (Throwable $t) {
            error_log('SMTP send failed: ' . $t->getMessage());
            return ['ok' => false, 'error' => $t->getMessage(), 'transport' => 'smtp'];
        }
    }

    // Fallback to PHP mail().
    $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=utf-8\r\n"
             . 'From: ' . $cfg['from_name'] . ' <' . $cfg['from_email'] . ">\r\n";
    if ($replyTo) $headers .= 'Reply-To: ' . $replyTo . "\r\n";
    if ($cc) $headers .= 'Cc: ' . implode(',', $cc) . "\r\n";
    $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers);
    return ['ok' => (bool)$ok, 'error' => $ok ? '' : 'mail() returned false', 'transport' => 'mail'];
}

/* ------------------------------------------------------------ templates -- */
function email_layout(string $title, string $contentHtml): string
{
    $site = e(SITE_NAME);
    $url  = e(SITE_URL);
    $foot = e(mail_config()['footer']);
    return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#F7F7F5;font-family:Arial,Helvetica,sans-serif;color:#1B1E21;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F7F5;padding:24px 12px;"><tr><td align="center">'
        . '<table role="presentation" width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;background:#ffffff;border:1px solid #E6E7E8;border-radius:12px;overflow:hidden;">'
        . '<tr><td style="background:#111315;padding:22px 28px;"><span style="color:#ffffff;font-size:20px;font-weight:800;letter-spacing:.5px;">' . $site . '</span></td></tr>'
        . '<tr><td style="padding:28px;"><h1 style="margin:0 0 14px;font-size:20px;line-height:1.3;color:#111315;">' . $title . '</h1>' . $contentHtml . '</td></tr>'
        . '<tr><td style="padding:18px 28px;border-top:1px solid #E6E7E8;color:#70747A;font-size:12px;line-height:1.6;">'
        . ($foot !== '' ? $foot . '<br>' : '')
        . '<a href="' . $url . '" style="color:#007AC1;">' . $url . '</a></td></tr>'
        . '</table></td></tr></table></body></html>';
}

function email_row(string $label, string $value): string
{
    return '<tr><td style="padding:7px 12px 7px 0;color:#70747A;font-size:13px;vertical-align:top;white-space:nowrap;">' . e($label) . '</td>'
         . '<td style="padding:7px 0;font-size:14px;color:#1B1E21;">' . $value . '</td></tr>';
}

/**
 * Send the business notification + customer acknowledgement for an enquiry.
 * Returns ['notify'=>..., 'ack'=>...] result arrays.
 */
function send_enquiry_emails(array $enq): array
{
    $cfg = mail_config();
    $results = ['notify' => ['ok' => false, 'error' => 'no recipient', 'transport' => 'none'],
                'ack'    => ['ok' => false, 'error' => 'disabled', 'transport' => 'none']];

    $name = trim($enq['first_name'] . ' ' . ($enq['last_name'] ?? ''));
    $price = $enq['price_shown'] !== null && $enq['price_shown'] !== '' ? cad((float)$enq['price_shown']) : 'Shown on page';
    $ref = $enq['reference'];

    /* ---- business notification ---- */
    if ($cfg['notify_email'] !== '') {
        $rows  = email_row('Lead reference', '<strong>' . e($ref) . '</strong>');
        $rows .= email_row('Customer', e($name));
        $rows .= email_row('Email', '<a href="mailto:' . e($enq['email']) . '">' . e($enq['email']) . '</a>');
        $rows .= email_row('Phone', '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $enq['phone'])) . '">' . e($enq['phone']) . '</a>');
        $rows .= email_row('Location', e(trim(($enq['city'] ?? '') . ', ' . ($enq['province'] ?? '') . ' ' . ($enq['postal_code'] ?? ''))));
        $rows .= email_row('Preferred contact', e($enq['preferred_contact'] ?? 'Either'));
        $rows .= email_row('Product', '<strong>' . e($enq['product_name']) . '</strong>');
        $rows .= email_row('SKU', e($enq['product_sku'] ?? ''));
        if (!empty($enq['colour']))  $rows .= email_row('Colour', e($enq['colour']));
        if (!empty($enq['variant'])) $rows .= email_row('Variant', e($enq['variant']));
        $rows .= email_row('Price shown', e($price));
        $rows .= email_row('Page', '<a href="' . eurl($enq['page_url'] ?? '') . '">' . e($enq['page_url'] ?? '') . '</a>');
        $utm = trim(implode(' / ', array_filter([$enq['utm_source'] ?? '', $enq['utm_medium'] ?? '', $enq['utm_campaign'] ?? ''])));
        if ($utm !== '') $rows .= email_row('UTM', e($utm));
        if (!empty($enq['referrer'])) $rows .= email_row('Referrer', e($enq['referrer']));
        $rows .= email_row('Submitted', e($enq['created_at'] ?? date('Y-m-d H:i:s')));

        $html = '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;">' . $rows . '</table>';
        if (!empty($enq['message'])) {
            $html .= '<div style="margin-top:16px;padding:14px 16px;background:#F7F7F5;border-left:3px solid #D8232A;border-radius:6px;font-size:14px;line-height:1.6;">'
                   . nl2br(e($enq['message'])) . '</div>';
        }
        $html .= '<p style="margin:20px 0 0;"><a href="' . e(site_url('admin/enquiries.php')) . '" style="display:inline-block;background:#D8232A;color:#fff;text-decoration:none;padding:11px 22px;border-radius:8px;font-size:14px;font-weight:700;">Open in Admin Panel</a></p>';

        $subject = 'New Product Enquiry – ' . $enq['product_name'] . ' – ' . $name;
        $cc = $cfg['cc_email'] !== '' ? [$cfg['cc_email']] : [];
        $results['notify'] = send_mail($cfg['notify_email'], $subject, email_layout(e($subject), $html), '', $enq['email'], $cc);
    }

    /* ---- customer acknowledgement ---- */
    if ($cfg['send_ack']) {
        $ack  = '<p style="font-size:15px;line-height:1.7;margin:0 0 14px;">Hi ' . e($enq['first_name']) . ',</p>';
        $ack .= '<p style="font-size:15px;line-height:1.7;margin:0 0 14px;">Thank you — we&rsquo;ve received your product enquiry for the <strong>' . e($enq['product_name']) . '</strong>'
              . (!empty($enq['colour']) ? ' in ' . e($enq['colour']) : '') . '. Our team will follow up with you shortly to answer your questions and discuss next steps.</p>';
        $ack .= '<p style="font-size:15px;line-height:1.7;margin:0 0 14px;">Your enquiry reference is <strong>' . e($ref) . '</strong>. Please keep it handy if you contact us.</p>';
        $ack .= '<p style="font-size:13px;line-height:1.7;color:#70747A;margin:18px 0 0;">Please note: this message confirms receipt of your enquiry only — no order has been placed and no payment has been taken. Questions? Call us at ' . e(setting('store_phone', '1-855-907-4211')) . '.</p>';
        $results['ack'] = send_mail($enq['email'], 'We\'ve received your GIO enquiry (' . $ref . ')', email_layout('We&rsquo;ve received your product enquiry.', $ack));
    }

    return $results;
}
