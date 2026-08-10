<?php

namespace App\Http\Resources;

use App\Models\Quantity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QunatityCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * @var $this Quantity
         */
        $image = null;
        if ($this->image !== null && isset($this->product->getMedia()[$this->image])) {
            $image = $this->product->getMedia()[$this->image]->getUrl('product-image');
        } else {
            $image = $this->product->imgUrl();
        }

        return [
            'id' => $this->id,
            'product_name' => $this->product->name,
            'count' => $this->count,
            'weight' => $this->weight,
            'code' => $this->code,
            'data' => json_decode($this->data),
            'meta' => $this->meta,
            'price' => $this->price,
            'image' => $image,
        ];
    }
}
