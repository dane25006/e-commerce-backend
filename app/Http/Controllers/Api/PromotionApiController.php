<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PromotionApiController extends Controller
{
    #[OA\Get(
        path: '/api/promotions',
        summary: 'List promotions',
        description: 'Retrieve all active promotions. Publicly accessible.',
        tags: ['Promotions'],
        operationId: 'promotionIndex',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'promotions', type: 'array', items: new OA\Items(ref: '#/components/schemas/Promotion')),
                    ]
                )
            ),
        ]
    )]
    // ── GET /api/promotions ──────────────────────────────────────────────
    public function index()
    {
        $promotions = Promotion::with('product')
            ->active()
            ->latest()
            ->get()
            ->map(fn($p) => $this->format($p));

        return response()->json(['promotions' => $promotions]);
    }

    #[OA\Get(
        path: '/api/promotions/{promotion}',
        summary: 'Get promotion details',
        description: 'Retrieve a specific promotion by ID. Publicly accessible.',
        tags: ['Promotions'],
        operationId: 'promotionShow',
        parameters: [
            new OA\Parameter(
                name: 'promotion',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'Promotion ID'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'promotion', ref: '#/components/schemas/Promotion'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    // ── GET /api/promotions/{promotion} ──────────────────────────────────
    public function show(Promotion $promotion)
    {
        $promotion->load('product');
        return response()->json(['promotion' => $this->format($promotion)]);
    }

    #[OA\Post(
        path: '/api/promotions/validate',
        summary: 'Validate a coupon code',
        description: 'Check whether a coupon code is valid and active.',
        tags: ['Promotions'],
        operationId: 'promotionValidate',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['coupon_code'],
                properties: [
                    new OA\Property(property: 'coupon_code', type: 'string', description: 'Coupon code to validate'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Coupon is valid',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'valid', type: 'boolean'),
                        new OA\Property(property: 'promotion', ref: '#/components/schemas/Promotion'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Invalid or expired coupon'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    // ── POST /api/promotions/validate ────────────────────────────────────
    public function validate(Request $request)
    {
        $request->validate(['coupon_code' => 'required|string']);

        $promotion = Promotion::active()
            ->where('coupon_code', $request->coupon_code)
            ->first();

        if (!$promotion) {
            return response()->json([
                'valid'  => false,
                'message' => 'This coupon code is invalid or has expired.',
            ], 404);
        }

        return response()->json([
            'valid'     => true,
            'promotion' => $this->format($promotion),
            'message'   => ($promotion->discount_type === 'percentage')
                ? "Coupon applied! You saved {$promotion->discount_value}%"
                : "Coupon applied! You saved \${$promotion->discount_value}",
        ]);
    }

    // ── Private helper ───────────────────────────────────────────────────
    private function format(Promotion $p): array
    {
        return [
            'id'             => $p->id,
            'title'          => $p->title,
            'slug'           => $p->slug,
            'description'    => $p->description,
            'discount_type'  => $p->discount_type,
            'discount_value' => (float) $p->discount_value,
            'coupon_code'    => $p->coupon_code,
            'image_url'      => $p->image_url,
            'is_active'      => $p->is_active,
            'starts_at'      => $p->starts_at?->toDateTimeString(),
            'ends_at'        => $p->ends_at?->toDateTimeString(),
            'product'        => $p->product ? [
                'id'    => $p->product->id,
                'name'  => $p->product->name,
                'price' => (float) $p->product->price,
            ] : null,
            'created_at'     => $p->created_at->toDateTimeString(),
        ];
    }
}
