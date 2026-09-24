<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    public function display(Request $request): array
    {
        $visitor = Visitor::where('ip', $request->ip())->orderByDesc('id')->first();
        if ($visitor !== null) {
            $visitor->display = $request->input('display');
            $visitor->save();
        }

        return ['OK' => true];
    }
}
