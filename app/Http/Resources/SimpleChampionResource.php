<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpleChampionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cost' => $this->cost,
            'champion_img' => asset('images/champions/' . $this->champion_img),
            'champion_icon' => asset('images/champions/' . $this->champion_icon),
            'synergies' => SimpleSynergyResource::collection($this->whenLoaded('synergies')),
            'recommended_items' => ItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
