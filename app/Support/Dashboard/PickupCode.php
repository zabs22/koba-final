<?php

namespace App\Support\Dashboard;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Pickup codes: "KB-" + the branch letter + five characters, e.g. KB-A7F42X
 * (K = Kebena, A = Atlas, 4 = 4 Kilo, P = Peacock).
 *
 * Staff read codes aloud at a noisy counter, so the alphabet leaves out every
 * look-alike: no O or 0, no I, 1 or L. Codes are derived from the order number,
 * so the demo needs no storage. When orders get a table, store the code on the
 * order with the same alphabet and a unique index per branch and day.
 *
 * The QR code holds "KOBA:{branch}:{code}" only, never a name, phone or total.
 * Uses bacon/bacon-qr-code, which Jetstream already installs for two-factor login.
 */
final class PickupCode
{
    public const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    private const BRANCH_LETTERS = ['kebena' => 'K', 'atlas' => 'A', '4-kilo' => '4', 'peacock' => 'P'];

    public static function for(array $order): string
    {
        $hash = hexdec(substr(md5('pickup|'.$order['id']), 0, 12));
        $tail = '';
        for ($i = 0; $i < 5; $i++) {
            $tail .= self::ALPHABET[$hash % strlen(self::ALPHABET)];
            $hash = intdiv($hash, strlen(self::ALPHABET));
        }

        return 'KB-'.self::letter($order['branch']).$tail;
    }

    /** "KB-B7F 42X": easier to read out; copy and QR always use the plain code. */
    public static function display(string $code): string
    {
        return substr($code, 0, 6).' '.substr($code, 6);
    }

    public static function letter(string $branch): string
    {
        return self::BRANCH_LETTERS[$branch] ?? self::ALPHABET[crc32($branch) % strlen(self::ALPHABET)];
    }

    /**
     * What staff type or scan, as a code: "kb-b7f 42x", "B7F42X" or the QR text
     * "KOBA:atlas:KB-A7F42X". Null when it cannot be a pickup code.
     */
    public static function normalize(string $input): ?string
    {
        $value = strtoupper(trim($input));
        if (preg_match('/^KOBA:[A-Z0-9-]+:(KB-[A-Z0-9]{6})$/', $value, $m)) {
            $value = $m[1];
        }
        $value = preg_replace('/[\s-]+/', '', $value);
        if (strlen($value) === 8 && str_starts_with($value, 'KB')) {
            $value = substr($value, 2);
        }

        return preg_match('/^['.self::ALPHABET.']{6}$/', $value) ? 'KB-'.$value : null;
    }

    public static function payload(array $order): string
    {
        return 'KOBA:'.$order['branch'].':'.self::for($order);
    }

    /** Black-on-white QR as inline SVG (scales to its container). */
    public static function svg(string $text, int $size = 240): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));
        $svg = $writer->writeString($text, 'utf-8', ErrorCorrectionLevel::M());
        $svg = substr($svg, strpos($svg, '<svg'));

        return preg_replace('/ width="\d+" height="\d+"/', ' width="100%" height="100%" role="img" aria-label="Pickup QR code"', $svg, 1);
    }
}
