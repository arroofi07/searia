<?php

use App\Support\PdfRenderer;

it('prepares a writable font cache and embeds the organizer logo as a data uri', function () {
    PdfRenderer::prepare();

    $fonts = PdfRenderer::ensureFontsDirectory();
    $public = PdfRenderer::resolvedPublicPath();
    $logo = PdfRenderer::logoDataUri();

    expect(is_dir($fonts))->toBeTrue()
        ->and(is_writable($fonts))->toBeTrue()
        ->and(realpath($public))->not->toBeFalse()
        ->and($logo)->toStartWith('data:image/');
});
