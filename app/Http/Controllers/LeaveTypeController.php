<?php

namespace App\Http\Controllers;

use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaveTypeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return LeaveTypeResource::collection(LeaveType::orderBy('id')->get());
    }
}
