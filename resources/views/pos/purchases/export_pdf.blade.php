<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Orders — {{ $store->name }}</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 12px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; font-weight: bold; }
        .header { margin-bottom: 8px; }
        .title { font-size: 18px; font-weight: bold; }
        .text-right { text-align: right; }
        .text-danger { color: #dc2626; }
        .text-emerald { color: #16a34a; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Purchase Orders — {{ $store->name }}</div>
        <div>Generated: {{ now()->format('Y-m-d H:i') }}</div>
        @if ($status)
            <div>Filter: {{ ucfirst($status) }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>PO Number</th>
                <th>Supplier</th>
                <th>Status</th>
                <th>Payment</th>
                <th class="text-right">Total Cost</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Balance</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pos as $po)
                <tr>
                    <td>{{ $po->po_number }}</td>
                    <td>{{ $po->supplier?->name ?? '-' }}</td>
                    <td>{{ ucfirst($po->status) }}</td>
                    <td>{{ ucfirst($po->payment_status) }}</td>
                    <td class="text-right">Ks {{ number_format((float) $po->total_cost) }}</td>
                    <td class="text-right text-emerald">Ks {{ number_format((float) $po->paid_amount) }}</td>
                    <td class="text-right text-danger">Ks {{ number_format((float) $po->remaining_balance) }}</td>
                    <td>{{ $po->created_at?->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>