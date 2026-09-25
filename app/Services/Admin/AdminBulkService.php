<?php

namespace App\Services\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdminBulkService
{
    public function handle(string $modelClass, ?string $action, array $ids, ?callable $customHandler = null): RedirectResponse
    {
        $actionParts = explode('.', (string) $action);
        $primaryAction = $actionParts[0] ?? '';
        $subAction = $actionParts[1] ?? null;

        return DB::transaction(function () use ($modelClass, $primaryAction, $subAction, $ids, $customHandler) {
            $msg = null;

            if ($customHandler !== null) {
                $msg = $customHandler($primaryAction, $subAction, $ids);
            }

            if ($msg === null) {
                switch ($primaryAction) {
                    case 'delete':
                        $modelClass::destroy($ids);
                        $msg = __(':COUNT items deleted successfully', ['COUNT' => count($ids)]);
                        break;

                    case 'restore':
                        foreach ($ids as $id) {
                            $modelClass::withTrashed()->find($id)?->restore();
                        }
                        $msg = __(':COUNT items restored successfully', ['COUNT' => count($ids)]);
                        break;

                    default:
                        $msg = __('Unknown bulk action : :ACTION', ['ACTION' => $primaryAction]);
                }
            }

            logAdminBatch(static::class.'.'.$primaryAction, $modelClass, $ids);

            return redirect()->back()->with(['message' => $msg]);
        });
    }
}
