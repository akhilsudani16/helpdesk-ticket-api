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
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        if ($this->relationLoaded('customer')) {
            $data['customer'] = $this->shouldInclude('customer')
                ? new UserResource($this->customer)
                : [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                ];
        }

        if ($this->assigned_to && $this->relationLoaded('assignedAgent')) {
            $data['assigned_agent'] = $this->shouldInclude('assignedAgent')
                ? new UserResource($this->assignedAgent)
                : [
                    'id' => $this->assignedAgent->id,
                    'name' => $this->assignedAgent->name,
                    'email' => $this->assignedAgent->email,
                ];
        } elseif ($this->assigned_to === null) {
            $data['assigned_agent'] = null;
        }

        if ($this->shouldInclude('comments') && $this->relationLoaded('comments')) {
            $data['comments'] = TicketCommentResource::collection($this->comments);
        }

        return $data;
    }

    /**
     * Check if a relationship should be included based on the request.
     */
    private function shouldInclude(string $relationship): bool
    {
        $includes = request()->query('include', '');
        $includesArray = array_filter(explode(',', $includes));

        return in_array($relationship, $includesArray);
    }
}
