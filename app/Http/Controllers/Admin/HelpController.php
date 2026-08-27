<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminHelpCatalog;
use Illuminate\Contracts\View\View;

class HelpController extends Controller
{
    public function __construct(private AdminHelpCatalog $catalog)
    {
        $this->middleware('auth');
    }

    public function show(?string $topic = null): View
    {
        $current = $this->catalog->find($topic);

        if ($current === null) {
            abort(404);
        }

        return view('admin.help.show', [
            'topics' => $this->catalog->topics(),
            'current' => $current,
            'topicView' => $current['view'],
        ]);
    }
}
