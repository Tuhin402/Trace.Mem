<?php

namespace App\Services\Auth;

use App\Models\EmailBloomFilter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailBloomFilterService
{
    private const FILTER_KEY = 'global_auth_email';
    private const DEFAULT_SIZE = 16384;
    private const DEFAULT_HASH_COUNT = 7;

    public function maybeContains(string $email): bool
    {
        $email = $this->normalize($email);
        $filter = $this->getFilter();
        $binary = $this->decodeBitset($filter->bitset, (int) $filter->size);

        foreach ($this->indexes($email, (int) $filter->size, (int) $filter->hash_count) as $index) {
            if (! $this->getBit($binary, $index)) {
                return false;
            }
        }

        return true;
    }

    public function add(string $email): void
    {
        $email = $this->normalize($email);

        DB::transaction(function () use ($email) {
            $filter = EmailBloomFilter::query()
                ->where('filter_key', self::FILTER_KEY)
                ->lockForUpdate()
                ->first();

            if (! $filter) {
                $filter = EmailBloomFilter::create([
                    'filter_key' => self::FILTER_KEY,
                    'size' => self::DEFAULT_SIZE,
                    'hash_count' => self::DEFAULT_HASH_COUNT,
                    'bitset' => $this->encodeBitset($this->emptyBinary(self::DEFAULT_SIZE)),
                ]);
            }

            $binary = $this->decodeBitset($filter->bitset, (int) $filter->size);

            foreach ($this->indexes($email, (int) $filter->size, (int) $filter->hash_count) as $index) {
                $binary = $this->setBit($binary, $index);
            }

            $filter->bitset = $this->encodeBitset($binary);
            $filter->save();
        });
    }

    private function getFilter(): EmailBloomFilter
    {
        return EmailBloomFilter::firstOrCreate(
            ['filter_key' => self::FILTER_KEY],
            [
                'size' => self::DEFAULT_SIZE,
                'hash_count' => self::DEFAULT_HASH_COUNT,
                'bitset' => $this->encodeBitset($this->emptyBinary(self::DEFAULT_SIZE)),
            ]
        );
    }

    private function normalize(string $email): string
    {
        return Str::of($email)->trim()->lower()->toString();
    }

    private function indexes(string $email, int $size, int $hashCount): array
    {
        $indexes = [];

        for ($i = 0; $i < $hashCount; $i++) {
            $hash = hash('sha256', $i . '|' . $email);
            $indexes[] = hexdec(substr($hash, 0, 8)) % $size;
        }

        return $indexes;
    }

    private function emptyBinary(int $size): string
    {
        return str_repeat("\0", (int) ceil($size / 8));
    }

    private function decodeBitset(?string $encoded, int $size): string
    {
        $binary = $encoded ? base64_decode($encoded, true) : false;

        if ($binary === false || $binary === null || $binary === '') {
            return $this->emptyBinary($size);
        }

        return $binary;
    }

    private function encodeBitset(string $binary): string
    {
        return base64_encode($binary);
    }

    private function getBit(string $binary, int $index): bool
    {
        $byteIndex = intdiv($index, 8);
        $bitIndex = $index % 8;

        if (! isset($binary[$byteIndex])) {
            return false;
        }

        return (ord($binary[$byteIndex]) & (1 << $bitIndex)) !== 0;
    }

    private function setBit(string $binary, int $index): string
    {
        $byteIndex = intdiv($index, 8);
        $bitIndex = $index % 8;

        if (strlen($binary) <= $byteIndex) {
            $binary = str_pad($binary, $byteIndex + 1, "\0");
        }

        $current = ord($binary[$byteIndex]);
        $binary[$byteIndex] = chr($current | (1 << $bitIndex));

        return $binary;
    }
}