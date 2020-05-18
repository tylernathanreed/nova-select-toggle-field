<?php

namespace Reedware\NovaSelectToggleField\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Reedware\NovaSelectToggleField\Tool;

class SelectToggleController extends Controller
{
    /**
     * Handles incoming select-toggle requests.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Determine the options
        $options = Tool::getOptions(
            $request->resourceName,
            $request->fieldAttribute,
            $request->targetAttribute,
            $request->targetValue
        );

        // Return the response
        return Response::json(compact('options'));
    }
}