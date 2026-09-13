{{-- 
  DEPRECATED / CLEANUP:
  This legacy template has been consolidated into the unified Group A Interactive Studio:
  resources/views/admin/repairs/print.blade.php
  Both ServiceJobController::printTicket and RepairController::printTicket render admin.repairs.print.
--}}
@include('admin.repairs.print', [
    'repair' => $repair ?? ($job ?? null),
    'store' => $store ?? null,
    'storeRouteParams' => $storeRouteParams ?? ($store ? ['store_slug' => $store->slug] : []),
    'template' => $template ?? null,
    'templates' => $templates ?? collect(),
    'paperSize' => $paperSize ?? 'a5',
    'trackingUrl' => $trackingUrl ?? null,
    'trackingQrSvg' => $trackingQrSvg ?? null,
    'trackingQrDataUri' => $trackingQrDataUri ?? null,
])
