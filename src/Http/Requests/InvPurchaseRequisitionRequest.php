<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use ME\SflInventory\Http\Requests\Concerns\PicksMerchandisingStyle;
use ME\SflInventory\Http\Requests\Concerns\ValidatesItemStore;

class InvPurchaseRequisitionRequest extends FormRequest
{
    use PicksMerchandisingStyle;
    use ValidatesItemStore;

    public function authorize(): bool
    {
        $ability = $this->route('purchase_requisition') ? 'inv_purchase_requisition.edit' : 'inv_purchase_requisition.add';

        return (bool) $this->user()?->can($ability);
    }

    /** Optional Buyer → Style → PO (Merchandising): an Inventory-only style goes into the style text. */
    protected function prepareForValidation(): void
    {
        $this->splitLegacyStyle();
    }

    public function rules(): array
    {
        return [
            'department_id'          => ['nullable', 'integer', 'exists:inv_departments,id'],
            'requisition_date'       => ['required', 'date'],
            'remarks'                => ['nullable', 'string'],
            'msfl_buyer_id'          => ['nullable', 'integer'],
            'msfl_style_id'          => ['nullable', 'integer'],
            'msfl_order_po_id'       => ['nullable', 'integer'],
            'style'                  => ['nullable', 'string', 'max:150'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.item_id'        => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.color_id'       => ['nullable', 'integer', 'exists:inv_colors,id'],
            'items.*.size_id'        => ['nullable', 'integer', 'exists:inv_sizes,id'],
            'items.*.requested_qty'  => ['required', 'numeric', 'min:0.0001'],
            'items.*.estimated_rate' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    /** No store on a purchase requisition — the item just has to have one. Buyer / style / PO, when given, must match. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $this->validateItemStores($v, null, $this->route('purchase_requisition')?->items->pluck('item_id') ?? []);
            if ($this->has('msfl_buyer_id')) {
                $this->validatePick($v, false);
            }
        });
    }
}
