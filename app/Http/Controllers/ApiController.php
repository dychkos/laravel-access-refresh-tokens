<?php

namespace App\Http\Controllers;

use App\Traits\HttpApiResponse;

abstract class ApiController extends Controller
{
    use HttpApiResponse;
}

