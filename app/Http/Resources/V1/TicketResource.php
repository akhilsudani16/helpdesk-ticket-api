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
        $rawIncludes = $request->query('include', '');
        $includes = array_filter(array_map('trim', explode(',', $rawIncludes)));

        $hasInclude = function (array $names) use ($includes) {
            foreach ($names as $n) {
                if (in_array($n, $includes, true)) {
                    return true;
                }
            }
            return false;
        };

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            // only include relations when requested via ?include=
            'customer' => $this->when($hasInclude(['customer', 'customer_id']), UserResource::make($this->whenLoaded('customer'))),
            'assigned_agent' => $this->when($hasInclude(['assignedAgent', 'assigned_agent', 'assigned-to']), UserResource::make($this->whenLoaded('assignedAgent'))),
            'comments' => $this->when($hasInclude(['comments']), TicketCommentResource::collection($this->whenLoaded('comments'))),
        ];
    }
}
