<?php

namespace App\Services;

use RuntimeException;

class WaveAudioAssembler
{
    /**
     * @param  list<string>  $waveBinaries
     */
    public function concatenate(array $waveBinaries, int $pauseMilliseconds = 0): string
    {
        if ($waveBinaries === []) {
            throw new RuntimeException('At least one WAV segment is required for assembly.');
        }

        $parsed = array_map(fn (string $binary): array => $this->parseWave($binary), $waveBinaries);
        $first = $parsed[0];
        $pauseBinary = $pauseMilliseconds > 0
            ? str_repeat("\x00", (int) round(($first['sample_rate'] * $first['channels'] * ($first['bits_per_sample'] / 8)) * ($pauseMilliseconds / 1000)))
            : '';

        $pcm = '';

        foreach ($parsed as $index => $segment) {
            if (
                $segment['sample_rate'] !== $first['sample_rate']
                || $segment['channels'] !== $first['channels']
                || $segment['bits_per_sample'] !== $first['bits_per_sample']
            ) {
                throw new RuntimeException('WAV segments must share the same audio format for concatenation.');
            }

            if ($index > 0 && $pauseBinary !== '') {
                $pcm .= $pauseBinary;
            }

            $pcm .= $segment['pcm'];
        }

        return $this->wrapWave(
            $pcm,
            $first['sample_rate'],
            $first['channels'],
            $first['bits_per_sample'],
        );
    }

    /**
     * @return array{pcm: string, sample_rate: int, channels: int, bits_per_sample: int}
     */
    private function parseWave(string $binary): array
    {
        if (strlen($binary) < 44 || substr($binary, 0, 4) !== 'RIFF' || substr($binary, 8, 4) !== 'WAVE') {
            throw new RuntimeException('Invalid WAV binary.');
        }

        $channels = unpack('v', substr($binary, 22, 2))[1] ?? 1;
        $sampleRate = unpack('V', substr($binary, 24, 4))[1] ?? 24000;
        $bitsPerSample = unpack('v', substr($binary, 34, 2))[1] ?? 16;
        $dataOffset = strpos($binary, 'data');

        if (! is_int($dataOffset) || $dataOffset < 0) {
            throw new RuntimeException('WAV data chunk not found.');
        }

        $dataSize = unpack('V', substr($binary, $dataOffset + 4, 4))[1] ?? 0;
        $availableBytes = strlen($binary) - ($dataOffset + 8);

        if (
            $channels < 1
            || $channels > 8
            || $sampleRate < 8000
            || $sampleRate > 192000
            || ! in_array($bitsPerSample, [8, 16, 24, 32], true)
            || $dataSize < 0
            || $dataSize > $availableBytes
        ) {
            throw new RuntimeException('WAV segment contains unsupported or corrupted format metadata.');
        }

        $pcm = substr($binary, $dataOffset + 8, $dataSize);

        return [
            'pcm' => $pcm,
            'sample_rate' => (int) $sampleRate,
            'channels' => (int) $channels,
            'bits_per_sample' => (int) $bitsPerSample,
        ];
    }

    private function wrapWave(string $pcm, int $sampleRate, int $channels, int $bitsPerSample): string
    {
        $byteRate = (int) ($sampleRate * $channels * ($bitsPerSample / 8));
        $blockAlign = (int) ($channels * ($bitsPerSample / 8));
        $dataSize = strlen($pcm);
        $chunkSize = 36 + $dataSize;

        return 'RIFF'
            .pack('V', $chunkSize)
            .'WAVE'
            .'fmt '
            .pack('V', 16)
            .pack('v', 1)
            .pack('v', $channels)
            .pack('V', $sampleRate)
            .pack('V', $byteRate)
            .pack('v', $blockAlign)
            .pack('v', $bitsPerSample)
            .'data'
            .pack('V', $dataSize)
            .$pcm;
    }
}
