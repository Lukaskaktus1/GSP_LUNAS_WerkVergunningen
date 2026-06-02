<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';

$vergunningId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$vergunningId) {
    setFlashMessage('error', 'Ongeldige aanvraag.');
    redirect('mijn_aanvragen.php');
}

redirect('aanvraag_bekijken.php?id=' . $vergunningId . '#vak6_inline_form');
