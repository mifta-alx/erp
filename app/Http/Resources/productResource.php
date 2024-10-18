<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    //define properti
    public $status;
    public $message;
    public $resource;

    /**
     * __construct
     *
     * @param  mixed $status
     * @param  mixed $message
     * @param  mixed $resource
     * @return void
     */
    public function __construct($status, $message, $resource)
    {
        parent::__construct($resource);
        $this->status  = $status;
        $this->message = $message;
    }

    /**
     * toArray
     *
     * @param  mixed $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'success'   => $this->status,
            'message'   => $this->message,
            'data'      => [
                'product_id' => $this->product_id, // Pastikan menggunakan $this->id bukan $this->product_id
                'product_name' => $this->product_name,
                'category_id' => $this->category_id,
                'sales_price' => $this->sales_price,
                'cost' => $this->cost,
                'barcode' => $this->barcode,
                'internal_reference' => $this->internal_reference,
                'notes' => $this->notes,
                'tags' => $this->tag->map(function ($tag) {
                    return [
                        'tag_id' => $tag->tag_id,
                        'name' => $tag->name_tag,
                    ];
                }), // Mengubah format tags agar lebih sederhana
                'image_uuid' => $this->image_uuid,
                'image_url' => $this->image_url,
            ]
        ];
    }
}
