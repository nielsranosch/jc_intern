<?php
return [
    "exclude"   => ["Drafts", "Sent", "Trash", "Spam", "Archives", "Templates", "Archiv", "zArchiv"], //TODO: make this env-configurable
    "topfolder" => env("MAILCHECKER_TOPFOLDER", "INBOX"),
    "webmail"   => env("MAILCHECKER_WEBMAIL", "https://webmail.jazzchor-bonn.de/")
];