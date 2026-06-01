<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketCommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'body' => $this->body,
            'is_internal' => $this->is_internal,

            // User relationship - only included when loaded
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
