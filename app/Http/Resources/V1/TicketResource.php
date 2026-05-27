<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // includes
            'customer' => $this->when(
                $this->shouldInclude('customer'),
                fn() => new UserResource($this->whenLoaded('customer'))
            ),
            'assignedAgent' => $this->when(
                $this->shouldInclude('assignedAgent'),
                fn() => $this->assigned_to ? new UserResource($this->whenLoaded('assignedAgent')) : null
            ),
            'comments' => $this->when(
                $this->shouldInclude('comments'),
                fn() => TicketCommentResource::collection($this->whenLoaded('comments'))
            ),
        ];
    }

    private function shouldInclude(string $relationship): bool
    {
        $includes = request()->query('include', '');
        $includesArray = array_filter(explode(',', $includes));

        return in_array($relationship, $includesArray);
    }
}
