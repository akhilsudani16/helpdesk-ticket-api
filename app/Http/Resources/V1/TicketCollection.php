<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TicketCollection extends ResourceCollection
{
    /**
     * The name of the key to use for the collection data.
     * Defaults to 'tickets' but can be overridden.
     */
    public $collectionKey = 'tickets';

    /**
     * Create a new resource collection.
     *
     * @param mixed $resource
     * @param string $collectionKey
     */
    public function __construct($resource, $collectionKey = 'tickets')
    {
        parent::__construct($resource);
        $this->collectionKey = $collectionKey;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            $this->collectionKey => $this->collection,
            'meta' => [
                'current_page' => $this->currentPage(),
                'from' => $this->firstItem(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'to' => $this->lastItem(),
                'total' => $this->total(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
        ];
    }
}
