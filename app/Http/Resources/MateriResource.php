<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MateriResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'category'    => $this->category,
            'description' => $this->description,
            'image'       => $this->image ? url('storage/' . $this->image) : null,
            'teacher'     => new TeacherResource($this->whenLoaded('teacher')),
            'created_at'  => $this->created_at->format('Y-m-d H:i:s'),
        ];  
        
    }
}       
