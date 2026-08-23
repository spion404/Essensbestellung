<?php

declare(strict_types=1);

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use InvalidArgumentException;

final class PdfService
{
    public function render(
        string $html,
        string $paper = 'A4',
        string $orientation = 'portrait'
    ): string {
        if (!in_array($orientation, ['portrait', 'landscape'], true)) {
            throw new InvalidArgumentException(
                'Ungültige PDF-Ausrichtung.'
            );
        }

        $options = new Options();
        $options->setDefaultFont('DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        return $dompdf->output();
    }
}
