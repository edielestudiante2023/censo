<?php

namespace App\Libraries;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

class QrSvgService
{
    public function render(string $data, string $foreground = '#111827', int $size = 360): string
    {
        [$red, $green, $blue] = $this->hexToRgb($foreground);

        $errorReporting = error_reporting(error_reporting() & ~E_DEPRECATED);

        try {
            return Builder::create()
                ->writer(new SvgWriter())
                ->data($data)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size($size)
                ->margin(14)
                ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->foregroundColor(new Color($red, $green, $blue))
                ->backgroundColor(new Color(255, 255, 255))
                ->build()
                ->getString();
        } finally {
            error_reporting($errorReporting);
        }
    }

    private function hexToRgb(?string $hex): array
    {
        $hex = ltrim((string) $hex, '#');
        if (! preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            $hex = '111827';
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
