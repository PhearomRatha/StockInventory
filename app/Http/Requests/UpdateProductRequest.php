<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'name' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|exists:categories,id',
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'sku' => 'sometimes|required|unique:products,sku,'.$productId,
            'barcode' => 'nullable|string',
            'description' => 'nullable|string',
            'cost' => 'sometimes|required|numeric',
            'price' => 'nullable|numeric',
            'stock_quantity' => 'sometimes|required|integer',
            'reorder_level' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
