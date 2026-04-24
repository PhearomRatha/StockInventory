<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'sku' => 'nullable|unique:products,sku',
            'barcode' => 'nullable|string',
            'description' => 'nullable|string',
            'cost' => 'required|numeric',
            'price' => 'nullable|numeric',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_level' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
