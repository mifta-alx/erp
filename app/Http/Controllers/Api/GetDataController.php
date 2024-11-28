<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\Category;
use App\Models\ManufacturingOrder;
use App\Models\Material;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\Rfq;
use App\Models\Tag;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetDataController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $includeProducts = $request->has('products');
        $includeMaterials = $request->has('materials');
        $includeCategories = $request->has('categories');
        $includeTags = $request->has('tags');
        $includeBoms = $request->has('boms');
        $includeVendors = $request->has('vendors');
        $includeMo = $request->has('mos');
        $includeRfq = $request->has('rfqs');
        $includeReceipt = $request->has('receipts');

        $products = Product::with('category', 'tag')->orderBy('created_at', 'ASC')->get();
        $productData = $products->map(function ($product) {
            return [
                'id' => $product->product_id,
                'name' => $product->product_name,
                'category_id' => $product->category_id,
                'category_name' => $product->category->category,
                'sales_price' => $product->sales_price,
                'cost' => $product->cost,
                'barcode' => $product->barcode,
                'internal_reference' => $product->internal_reference,
                'tags' => $product->tag->map(function ($tag) {
                    return [
                        'id' => $tag->tag_id,
                        'name' => $tag->name_tag
                    ];
                }),
                'notes' => $product->notes,
                'image_uuid' => $product->image_uuid,
                'image_url' => $product->image_url,
                'stock' => $product->stock,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at
            ];
        });

        $materials = Material::with('category', 'tag')->orderBy('created_at', 'ASC')->get();
        $materialData = $materials->map(function ($material) {
            return [
                'id' => $material->material_id,
                'name' => $material->material_name,
                'category_id' => $material->category_id,
                'category_name' => $material->category->category,
                'sales_price' => $material->sales_price,
                'cost' => $material->cost,
                'barcode' => $material->barcode,
                'internal_reference' => $material->internal_reference,
                'tags' => $material->tag->map(function ($tag) {
                    return [
                        'id' => $tag->tag_id,
                        'name' => $tag->name_tag
                    ];
                }),
                'notes' => $material->notes,
                'image_uuid' => $material->image_uuid,
                'image_url' => $material->image_url,
                'stock' => $material->stock,
                'created_at' => $material->created_at,
                'updated_at' => $material->updated_at
            ];
        });

        $queryTag = Tag::query();
        if ($request->has('type')) {
            $queryTag->where('type', $request->type);
        }
        $tags = $queryTag->orderBy('tag_id', 'ASC')->get();
        $formattedTags = $tags->map(function ($tag) {
            return [
                'id' => $tag->tag_id,
                'type' => $tag->type,
                'name' => $tag->name_tag,
            ];
        });

        //boms
        $boms = Bom::with(['product', 'bom_components.material'])->get();
        $bomData = $boms->map(function ($bom) {
            $bom_components = $bom->bom_components->map(function ($component) {
                $material = $component->material;
                $material_total_cost = $material->cost * $component->material_qty;
                return [
                    'material' => [
                        'id' => $material->material_id,
                        'name' => $material->material_name,
                        'cost' => $material->cost,
                        'sales_price' => $material->sales_price,
                        'barcode' => $material->barcode,
                        'internal_reference' => $material->internal_reference,
                    ],
                    'material_qty' => $component->material_qty,
                    'material_total_cost' => $material_total_cost,
                ];
            });

            $product = [
                'id' => $bom->product->product_id,
                'name' => $bom->product->product_name,
                'cost' => $bom->product->cost,
                'sales_price' => $bom->product->sales_price,
                'barcode' => $bom->product->barcode,
                'internal_reference' => $bom->product->internal_reference,
            ];

            $bom_cost = $bom_components->sum('material_total_cost');

            return [
                'bom_id' => $bom->bom_id,
                'product' => $product,
                'bom_reference' => $bom->bom_reference,
                'bom_qty' => $bom->bom_qty,
                'bom_components' => $bom_components,
                'bom_cost' => $bom_cost,
            ];
        });

        $vendors = Vendor::orderBy('created_at', 'ASC')->get();
        $vendorData = $vendors->map(function ($vendor) {
            return [
                'id' => $vendor->vendor_id,
                'name' => $vendor->name,
                'type' => $vendor->vendor_type,
                'street' => $vendor->street,
                'city' => $vendor->city,
                'state' => $vendor->state,
                'zip' => $vendor->zip,
                'phone' => $vendor->phone,
                'mobile' => $vendor->mobile,
                'email' => $vendor->email,
                'image_uuid' => $vendor->image_uuid,
                'image_url' => $vendor->image_url,
                'created_at' => $vendor->created_at,
                'updated_at' => $vendor->updated_at
            ];
        });

        $mo = ManufacturingOrder::orderBy('created_at', 'DESC')->get();
        $MoData = $mo->map(function ($order) {
            return [
                'id' => $order->mo_id,
                'reference' => $order->reference,
                'qty' => $order->qty,
                'bom_id' => $order->bom_id,
                'product' => [
                    'id' => $order->product->product_id,
                    'name' => $order->product->product_name,
                    'cost' => $order->product->cost,
                    'sales_price' => $order->product->sales_price,
                    'barcode' => $order->product->barcode,
                    'internal_reference' => $order->product->internal_reference,
                ],
                'state' => $order->state,
                'status' => $order->status,
                'mo_components' => $order->mo ? $order->mo->unique('material_id')->map(function ($component) {
                    return [
                        'material' => [
                            'id' => $component->material->material_id,
                            'name' => $component->material->material_name,
                            'cost' => $component->material->cost,
                            'sales_price' => $component->material->sales_price,
                            'barcode' => $component->material->barcode,
                            'internal_reference' => $component->material->internal_reference,
                        ],
                        'to_consume' => $component->to_consume,
                        'reserved' => $component->reserved,
                        'consumed' => $component->consumed,
                    ];
                }) : [],
            ];
        });

        $rfq = Rfq::orderBy('created_at', 'DESC')->get();
        $rfqData = $rfq->map(function ($item) {
            return [
                'id' => $item->rfq_id,
                'reference' =>  $item->reference,
                'vendor_id' => $item->vendor_id,
                'vendor_name' => $item->vendor->name,
                'vendor_reference' => $item->vendor_reference,
                'order_date' => $item->order_date,
                'state' => $item->state,
                'taxes' => $item->taxes,
                'total' => $item->total,
                'confirmation_date' => $item->confirmation_date,
                'invoice_status' => $item->invoice_status,
                'items' => $item->rfqComponent->map(function ($component) {
                    return [
                        'rfq_component_id' => $component->rfq_component_id,
                        'type' => $component->display_type,
                        'id' => $component->material_id,
                        'internal_reference' => $component->material->internal_reference ?? null,
                        'name' => $component->material->material_name ?? null,
                        'description' => $component->description,
                        'qty' => $component->qty,
                        'unit_price' => $component->unit_price,
                        'tax' => $component->tax,
                        'subtotal' => $component->subtotal,
                        'qty_received' => $component->qty_received,
                        'qty_to_invoice' =>  $component->qty_to_invoice,
                        'qty_invoiced' =>  $component->qty_invoiced,
                    ];
                }),
            ];
        });

        $receipts = Receipt::orderBy('created_at', 'DESC')->get();
        $receiptData = $receipts->map(function ($receipt) {
            return [
                'id' => $receipt->receipt_id,
                'transaction_type' => $receipt->transaction_type,
                'reference' => $receipt->reference,
                'vendor_id' => $receipt->vendor_id,
                'vendor_name' => $receipt->vendor->name ?? null,
                'rfq_id' => $receipt->rfq_id,
                'source_document' => $receipt->source_document,
                'items' =>  $receipt->rfq->rfqComponent->filter(function ($component) {
                    return $component->display_type !== 'line_section';
                })->map(function ($component) {
                    return [
                        'component_id' => $component->rfq_component_id,
                        'type' => $component->display_type,
                        'id' => $component->material_id,
                        'internal_reference' => $component->material->internal_reference ?? null,
                        'name' => $component->material->material_name ?? null,
                        'description' => $component->description,
                        'qty' => $component->qty,
                        'qty_received' => $component->qty_received,
                        'qty_to_invoice' => $component->qty_to_invoice,
                        'qty_invoiced' => $component->qty_invoiced,
                    ];
                }),
            ];
        });

        $response = [
            'success' => true,
            'message' => 'Data fetched successfully',
            'data' => []
        ];

        if ($includeProducts) {
            $response['data']['products'] = $productData;
        }

        if ($includeMaterials) {
            $response['data']['materials'] = $materialData;
        }

        if ($includeCategories) {
            $response['data']['categories'] = Category::all();
        }

        if ($includeTags) {
            $response['data']['tags'] = $formattedTags;
        }

        if ($includeBoms) {
            $response['data']['boms'] = $bomData;
        }

        if ($includeVendors) {
            $response['data']['vendors'] = $vendorData;
        }

        if ($includeMo) {
            $response['data']['manufacturing_orders'] = $MoData;
        }

        if ($includeRfq) {
            $response['data']['rfqs'] = $rfqData;
        }

        if ($includeReceipt) {
            $response['data']['receipts'] = $receiptData;
        }

        return response()->json($response);
    }
}
