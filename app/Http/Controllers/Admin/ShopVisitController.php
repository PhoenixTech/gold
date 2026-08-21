<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\XController;
use App\Http\Requests\ShopVisitSaveRequest;
use App\Models\ShopVisit;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShopVisitController extends XController
{
    protected $cols = ['mobile', 'first_name', 'last_name', 'has_purchase', 'mall', 'user_id', 'submitted_at'];

    protected $extra_cols = ['id', 'created_at'];

    protected $searchable = ['mobile', 'first_name', 'last_name', 'mall', 'address'];

    protected $listView = 'admin.shop-visits.shop-visit-list';

    protected $formView = 'admin.shop-visits.shop-visit-show';

    protected $buttons = [
        'show' => ['title' => 'Detail', 'class' => 'btn-outline-light', 'icon' => 'ri-eye-line'],
        'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
    ];

    public function __construct()
    {
        parent::__construct(ShopVisit::class, ShopVisitSaveRequest::class);
    }

    public function index()
    {
        $query = $this->makeSortAndFilter()
            ->with(['user', 'state', 'city'])
            ->completed();

        return $this->showList($query);
    }

    public function show($item)
    {
        $visit = ShopVisit::query()
            ->with(['user', 'state', 'city'])
            ->where('id', $item)
            ->firstOrFail();

        return view('admin.shop-visits.shop-visit-show', ['item' => $visit]);
    }

    public function bulk(Request $request)
    {
        $data = explode('.', $request->input('action'));
        $action = $data[0];
        $ids = $request->input('id');
        switch ($action) {
            case 'delete':
                $msg = __(':COUNT items deleted successfully', ['COUNT' => count($ids)]);
                $this->_MODEL_::destroy($ids);
                break;
            default:
                $msg = __('Unknown bulk action : :ACTION', ['ACTION' => $action]);
        }

        return $this->do_bulk($msg, $action, $ids);
    }

    public function destroy(ShopVisit $item)
    {
        return parent::delete($item);
    }

    public function export(): StreamedResponse
    {
        $filename = 'shop-visits-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'mobile',
                'first_name',
                'last_name',
                'has_purchase',
                'has_own_workshop',
                'other_reason',
                'categories',
                'work_styles',
                'province',
                'city',
                'mall',
                'address',
                'visitor',
                'submitted_at',
            ]);

            ShopVisit::query()
                ->with(['user', 'state', 'city'])
                ->completed()
                ->orderByDesc('id')
                ->chunk(200, function ($visits) use ($handle): void {
                    foreach ($visits as $visit) {
                        fputcsv($handle, [
                            $visit->mobile,
                            $visit->first_name,
                            $visit->last_name,
                            $visit->has_purchase ? __('Yes') : __('No'),
                            $visit->has_own_workshop ? __('Yes') : __('No'),
                            $visit->other_reason,
                            implode('|', $visit->categoryLabels()),
                            implode('|', $visit->workStyleLabels()),
                            $visit->state?->name,
                            $visit->city?->name,
                            $visit->mall,
                            $visit->address,
                            $visit->user?->name,
                            $visit->submittedAtLabel(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
