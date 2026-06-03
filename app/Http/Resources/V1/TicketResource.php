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
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
        ];

        // Build includes only if relationships are loaded
        $includes = [];
        if ($this->relationLoaded('customer')) {
            $includes['customer'] = new UserResource($this->customer);
        }
        if ($this->relationLoaded('assignedAgent')) {
            $includes['assigned_agent'] = new UserResource($this->assignedAgent);
        }
        if ($this->relationLoaded('comments')) {
            $includes['comments'] = TicketCommentResource::collection($this->comments);
        }

        // Only add includes key if there are any includes
        if (!empty($includes)) {
            $data['includes'] = $includes;
        }

        // Build relationships only if any include is requested
        $relationships = [];
        if (!empty($includes)) {
            $relationships['customer'] = [
                'data' => [
                    'type' => 'user',
                    'id' => $this->user_id,
                ],
                'links' => [
                    'self' => route('users.show', ['user' => $this->user_id]),
                ],
            ];

            if ($this->assigned_to) {
                $relationships['assigned_agent'] = [
                    'data' => [
                        'type' => 'user',
                        'id' => $this->assigned_to,
                    ],
                    'links' => [
                        'self' => route('users.show', ['user' => $this->assigned_to]),
                    ],
                ];
            }
        }

        // Only add relationships key if there are any relationships
        if (!empty($relationships)) {
            $data['relationships'] = $relationships;
        }

        $data['links'] = [
            'self' => route('tickets.show', ['ticket' => $this->id]),
        ];

        return $data;
    }
}
