<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionApiController extends Controller
{
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

    // ── GET /api/promotions/{promotion} ──────────────────────────────────
    public function show(Promotion $promotion)
    {
        $promotion->load('product');
        return response()->json(['promotion' => $this->format($promotion)]);
    }

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
                'message' => 'Invalid or expired coupon code.',
            ], 404);
        }

        return response()->json([
            'valid'     => true,
            'promotion' => $this->format($promotion),
            'message'   => "Coupon applied! {$promotion->discount_value}" .
                ($promotion->discount_type === 'percentage' ? '% off' : ' off'),
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
