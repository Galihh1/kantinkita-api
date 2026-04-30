<?php
config([
    'mail.mailers.smtp.host' => 'smtp.gmail.com',
    'mail.mailers.smtp.port' => 465,
    'mail.mailers.smtp.encryption' => 'ssl',
    'mail.mailers.smtp.username' => 'pangestu5711@gmail.com',
    'mail.mailers.smtp.password' => 'cczglctbjpkzubbx'
]);

try {
    Mail::raw('Ini adalah email percobaan dari lokal.', function ($msg) {
        $msg->to('galihhpangestuu@gmail.com')
            ->subject('Test Email Local');
    });
    echo "SUCCESS: Email terkirim.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
