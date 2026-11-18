<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CentreSettingsService
{
    private array $cache = [];

    public function get(string $key, mixed $default = null, ?int $branchId = null): mixed
    {
        $cacheKey = ($branchId ?? 'global') . '.' . $key;

        if (!array_key_exists($cacheKey, $this->cache)) {
            $row = DB::connection('tenant')
                ->table('centre_settings')
                ->where('key', $key)
                ->where('branch_id', $branchId)
                ->first();

            if (!$row) {
                return $default;
            }

            $this->cache[$cacheKey] = $this->cast($row->value, $row->type);
        }

        return $this->cache[$cacheKey];
    }

    public function set(string $key, mixed $value, string $type = 'string', ?int $branchId = null): void
    {
        $stored = $type === 'encrypted'
            ? encrypt($value)
            : ($type === 'json' ? json_encode($value) : (string) $value);

        DB::connection('tenant')
            ->table('centre_settings')
            ->updateOrInsert(
                ['key' => $key, 'branch_id' => $branchId],
                ['value' => $stored, 'type' => $type]
            );

        $cacheKey = ($branchId ?? 'global') . '.' . $key;
        $this->cache[$cacheKey] = $value;
    }

    public function getMany(array $keys, ?int $branchId = null): array
    {
        $rows = DB::connection('tenant')
            ->table('centre_settings')
            ->whereIn('key', $keys)
            ->where('branch_id', $branchId)
            ->get()
            ->keyBy('key');

        $result = [];
        foreach ($keys as $key) {
            $row = $rows->get($key);
            $result[$key] = $row ? $this->cast($row->value, $row->type) : null;
        }

        return $result;
    }

    public function setMany(array $data, string $type = 'string', ?int $branchId = null): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value, $type, $branchId);
        }
    }

    public function flush(): void
    {
        $this->cache = [];
    }

    private function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean'   => (bool) $value,
            'integer'   => (int) $value,
            'json'      => json_decode($value, true),
            'encrypted' => decrypt($value),
            default     => $value,
        };
    }
}
