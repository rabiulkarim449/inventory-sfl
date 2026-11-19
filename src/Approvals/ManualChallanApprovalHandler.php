<?php

namespace ME\SflInventory\Approvals;

use App\Approvals\BaseApprovalHandler;
use App\Models\Approval;
use Illuminate\Database\Eloquent\Model;
use ME\SflInventory\Approvals\Concerns\ResolvesConfiguredRecipients;
use ME\SflInventory\Models\ManualChallan;

/**
 * Registered in erp-suhana's config/approval.php under 'inventory.manual_challan'.
 *
 * Who is emailed when a Manual Challan is submitted: the users picked on
 * Settings > Mail Notifications > "Approval: Manual Challan"; with nobody
 * picked, every user whose role has Manual Challan "Approve / Reject".
 *
 * Approving / rejecting on the central Approvals page settles the challan;
 * the challan's own page does the same and closes this request (see
 * ManualChallanController::syncCentralApproval()).
 */
class ManualChallanApprovalHandler extends BaseApprovalHandler
{
    use ResolvesConfiguredRecipients;

    public function recipients(?Model $approvable, Approval $approval): array
    {
        return $this->resolveRecipients('inventory.manual_challan', 'inv_manual_challan.approve');
    }

    public function onApproved(Approval $approval): void
    {
        $this->settle($approval, 'approved');
    }

    public function onRejected(Approval $approval): void
    {
        $this->settle($approval, 'rejected');
    }

    public function mailContent(?Model $approvable, Approval $approval): array
    {
        if (! $approvable instanceof ManualChallan) {
            return [];
        }
        $approvable->loadMissing(['items', 'creator']);
        $info = $approvable->typeInfo();

        return [
            'badge' => strtoupper($info['menu']),
            'number' => $approvable->challan_no,
            'date' => $approvable->challan_date?->format('d.m.Y'),
            'meta' => [
                'Challan' => $info['title'],
                'To' => $approvable->to_name,
                'Address' => $approvable->to_address,
                'Prepared by' => $approvable->creator?->name,
            ],
            'columns' => [['label' => 'Description'], ['label' => 'PO / Style'], ['label' => 'Color'], ['label' => 'Qty', 'align' => 'right'], ['label' => 'Unit']],
            'rows' => $approvable->items->map(fn ($line) => [
                $line->description ?: '-',
                collect([$line->po_no, $line->style])->filter()->implode(' / ') ?: '-',
                $line->color ?: '-',
                inv_qty($line->qty),
                $line->unit ?: '-',
            ])->all(),
            'total' => ['label' => 'Total Qty', 'value' => inv_qty($approvable->totalQty())],
        ];
    }

    private function settle(Approval $approval, string $status): void
    {
        $challan = $approval->approvable;
        if (! $challan instanceof ManualChallan || ! $challan->isPending()) {
            return;
        }
        $challan->update([
            'status' => $status,
            'approved_by' => $approval->approved_by,
            'approved_at' => $approval->approved_at ?? now(),
            'approval_remarks' => $approval->remarks,
        ]);
    }
}
