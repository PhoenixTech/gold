<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quantity extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'count' => 'integer',
            'price' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function pieceSku(string $productSku, int $number): string
    {
        return trim($productSku).'-'.sprintf('%04d', $number);
    }

    public static function pieceNumberFromCode(?string $code, string $productSku): ?int
    {
        $productSku = trim($productSku);
        $code = trim((string) $code);
        if ($productSku === '' || $code === '') {
            return null;
        }

        if (! preg_match('/^'.preg_quote($productSku, '/').'-(\d+)$/', $code, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * @param  list<string|null>  $codes
     */
    public static function nextPieceNumber(array $codes, string $productSku): int
    {
        $max = 0;
        foreach ($codes as $code) {
            $number = self::pieceNumberFromCode(is_string($code) ? $code : null, $productSku);
            if ($number !== null) {
                $max = max($max, $number);
            }
        }

        return $max + 1;
    }

    /**
     * Fill empty piece codes as {productSku}-0001, {productSku}-0002, ...
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public static function assignPieceSkus(array $items, string $productSku): array
    {
        $productSku = trim($productSku);
        if ($productSku === '') {
            return $items;
        }

        foreach ($items as $index => $item) {
            $code = trim((string) ($item['code'] ?? ''));
            if ($code !== '') {
                $items[$index]['code'] = $code;

                continue;
            }

            $used = array_map(
                fn (array $row): string => trim((string) ($row['code'] ?? '')),
                $items
            );
            $number = max(self::nextPieceNumber($used, $productSku), $index + 1);
            $items[$index]['code'] = self::pieceSku($productSku, $number);
        }

        return $items;
    }

    public function isAvailable(): bool
    {
        return $this->count > 0;
    }

    public function markSold(): void
    {
        $this->count = 0;
        $this->save();
    }

    public function getMetaAttribute()
    {
        $data = json_decode($this->data, true);
        if ($data == null) {
            return [];
        }
        $props = $this->product->category->props()->whereIn('name', array_keys($data))->get();
        $result = [];
        foreach ($props as $key => $prop) {
            $result[$prop->name] = [
                'label' => $prop->label,
                'human_value' => '',
                'type' => $prop->type,
                'value' => $data[$prop->name],
            ];
            switch ($prop->type) {
                case 'color':
                    $result[$prop->name]['human_value'] = "<div style='background:  {$data[$prop->name]}' class='color-bullet'> &nbsp; </div>";
                    break;
                case 'checkbox':
                    $result[$prop->name]['human_value'] = $data[$prop->name] ? '<i class="ri-checkbox-circle-line"></i>' : '<i class="ri-close-circle-line"></i>';
                    break;
                case 'select':
                case 'singlemulti':
                    $tmp = $prop->datas;
                    if (! is_array($data[$prop->name])) {
                        if (isset($tmp[$data[$prop->name]])) {
                            $result[$prop->name]['human_value'] = $tmp[$data[$prop->name]];
                        } else {
                            $result[$prop->name]['human_value'] = '-';
                        }
                    } else {
                        $result[$prop->name]['human_value'] = '';
                        $tmp = $prop->datas;
                        foreach ($data[$prop->name] as $k => $v) {
                            $result[$prop->name]['human_value'] = $tmp[$v].', ';
                        }
                        $result[$prop->name]['human_value'] = trim($result[$prop->name], ' ,');
                    }
                    break;
                default:
                    if (is_array($data[$prop->name])) {
                        $result[$prop->name]['human_value'] = '<span class="meta-tag">'.implode('</span> <span class="meta-tag">', $data[$prop->name]).'</span>';
                    } else {
                        if ($data[$prop->name] == '' || $data[$prop->name] == null) {
                            $result[$prop->name]['human_value'] = '-';
                        } else {
                            $result[$prop->name]['human_value'] = $data[$prop->name];
                        }
                    }
            }

            $result[$prop->name]['human_value'] .= ' '.$prop->unit;
        }

        return $result;
    }
}
