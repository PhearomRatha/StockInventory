<?php

namespace App\Helpers;

use App\Models\Products as Product;

class StockHelper
{
    /**
     * Update product stock quantity
     */
    public static function updateStock($productId, $quantityChange)
    {
        $product = Product::find($productId);
        if ($product) {
            $product->stock_quantity += $quantityChange;
            $product->save();
            return $product;
        }
        return null;
    }

    /**
     * Check if product has sufficient stock
     */
    public static function hasSufficientStock($productId, $requiredQuantity)
    {
        $product = Product::find($productId);
        return $product && $product->stock_quantity >= $requiredQuantity;
    }

    /**
     * Get stock status for a product
     */
    public static function getStockStatus($product)
    {
        return $product->stock_status;
    }

    /**
     * Check if product is low on stock
     */
    public static function isLowStock($product)
    {
        return $product->is_low_stock;
    }

    /**
     * Generate invoice number
     */
    public static function generateInvoiceNumber($saleId)
    {
        return 'INV-' . date('Y') . '-' . str_pad($saleId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate stock in code
     */
    public static function generateStockInCode($stockInId)
    {
        return 'STK-' . date('Y') . '-' . str_pad($stockInId, 6, '0', STR_PAD_LEFT);
    }
}