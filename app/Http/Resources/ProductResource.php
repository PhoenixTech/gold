<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * @var $this Product
         */
        if (! $request['loadProduct']) {
            $request->merge([
                'loadProduct' => false,
            ]);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'table' => $this->table,
            'sku' => $this->sku,
            'virtual' => $this->virtual,
            'downloadable' => $this->downloadable,
            'price' => intval($this->price),
            'buy_price' => $this->buy_price,
            'average_rating' => floatval($this->average_rating),
            'view' => $this->view,
            'metal_type' => $this->metal_type,
            'target_group' => $this->target_group,
            'plating_colors' => $this->plating_colors ?? [],
            'plating_color_labels' => $this->getPlatingColorLabels(),
            'stones' => $this->stones ?? [],
            'stone_labels' => $this->getStoneLabels(),
            'accessories' => $this->accessories ?? [],
            'accessory_labels' => $this->getAccessoryLabels(),
            'occasions' => $this->occasions ?? [],
            'occasion_labels' => $this->getOccasionLabels(),
            'category' => $this->when($request->input('loadCategory', true), new CategoryResource($this->category)),
            'categories' => CategoriesCollection::collection($this->categories),
            'image' => $this->imgUrl(),
            'images' => $this->getMedia(),
        ];
    }
}
