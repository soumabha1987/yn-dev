<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait DecodeImageToBase64
{
    private function decodeImageToBase64(string $content, string $tableName): string
    {
        $userId = Auth::user()->id;

        preg_match_all('@src="([^"]+)"@', $content, $matches);

        $directory = '';

        if (count($matches[0]) > 0) {
            $directory = 'public/' . $tableName . '/editor-' . $userId . '/';
            if (! Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }
        }

        foreach ($matches[0] as $key => $base64String) {
            $fileName = $directory . '/' . $tableName . '_' . now()->format('Y_m_d_H_i_s') . '_' . $key . '.jpg';
            if ($this->base64ToJpeg($base64String, Storage::path($fileName))) {
                $protocol = request()->isSecure() ? 'https' : 'http';
                $content = Str::replace(
                    $base64String,
                    'alt="content_image' . $key . '" title="content_image' . $key . '" src="' . $protocol . '://' . request()->getHttpHost() . Storage::url($fileName) . '"',
                    $content
                );
            }
        }

        return $content;
    }

    private function base64ToJpeg(string $base64String, string $outputFile): bool
    {
        $data = explode(',', $base64String);

        if (strpos($data[0], 'base64') == false && strpos($data[0], 'data:image') == false) {
            return false;
        }

        $ifp = fopen($outputFile, 'w+');

        $decoded = base64_decode($data[1]);
        fwrite($ifp, $decoded);

        fclose($ifp);

        return true;
    }
}
