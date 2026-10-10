
<?php

return [
    'host'       => 'smtp.gmail.com',
    'username'   => getenv('IT_SUPPORT_MAIL_USER') ?: '',
    'password'   => getenv('IT_SUPPORT_MAIL_PASS') ?: '',
    'port'       => 587,
    'encryption' => 'tls',
];
