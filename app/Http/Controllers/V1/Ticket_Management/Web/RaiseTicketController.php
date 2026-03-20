<?php

namespace App\Http\Controllers\V1\Ticket_Management\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Ticket_Management\Web\StoreRaiseTicketRequest;
use App\Http\Requests\V1\Ticket_Management\Web\UpdateRaiseTicketRequest;
use App\Http\Resources\V1\Ticket_Management\Web\RaiseTicketResource;
use App\Services\V1\Ticket_Management\Web\RaiseTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RaiseTicketController extends Controller
{
    protected $service;

    public function __construct(RaiseTicketService $service)
    {
        $this->service = $service;
    }

    /**
     * List tickets
     */
    public function index(Request $request)
    {
        try {
            $tickets = $this->service->getAll(
                $request->all(),
                $request->per_page ?? 10
            );

            // Dropdown case (no pagination)
            if ($tickets instanceof \Illuminate\Support\Collection) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket dropdown list fetched successfully',
                    'data' => RaiseTicketResource::collection($tickets)
                ]);
            }

            // Paginated response
            return response()->json([
                'success' => true,
                'message' => 'Ticket list fetched successfully',
                'data' => RaiseTicketResource::collection($tickets->items()),
                'pagination' => [
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                    'per_page' => $tickets->perPage(),
                    'total' => $tickets->total(),
                    'from' => $tickets->firstItem(),
                    'to' => $tickets->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Ticket list failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store new ticket
     */
    // public function store(StoreRaiseTicketRequest $request)
    // {
    //     try {
    //         // dd($request);
    //         DB::beginTransaction();

    //         $ticket = $this->service->create($request->validated());

    //         DB::commit();

    //         return new RaiseTicketResource($ticket);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error("Ticket create failed: " . $e->getMessage());
    //         return response()->json(['message' => $e->getMessage()], 500);
    //     }
    // }
    public function store(StoreRaiseTicketRequest $request)
    {
        try {

            DB::beginTransaction();

            $ticket = $this->service->create($request->validated());

            DB::commit();

            return new RaiseTicketResource($ticket);
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("Ticket create failed: " . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * Show ticket
     */
    public function show(string $uuid)
    {
        try {
            $ticket = $this->service->getById($uuid);
            return response()->json([
                'success' => true,
                'message' => 'Ticket fetched successfully',
                'data' => new RaiseTicketResource($ticket)
            ]);
        } catch (\Exception $e) {
            Log::error("Ticket fetch failed: " . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * Update ticket
     */
    public function update(UpdateRaiseTicketRequest $request, string $uuid)
    {
        try {
            $ticket = $this->service->update($uuid, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Ticket updated successfully',
                'data' => new RaiseTicketResource($ticket)
            ]);
        } catch (\Exception $e) {
            Log::error("Ticket update failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Delete ticket
     */
    public function destroy(int $id)
    {
        try {
            DB::beginTransaction();

            $this->service->delete($id);

            DB::commit();

            return response()->json(['message' => 'Ticket deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Ticket delete failed: " . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * Global search
     */
    // public function search(Request $request)
    // {
    //     try {
    //         $tickets = $this->service->search(
    //             $request->per_page ?? 10,
    //             $request->keyword
    //         );

    //         return RaiseTicketResource::collection($tickets);
    //     } catch (\Exception $e) {
    //         Log::error("Ticket search failed: " . $e->getMessage());
    //         return response()->json(['message' => $e->getMessage()], 500);
    //     }
    // }
    public function search(Request $request)
    {
        try {
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

            $tickets = $this->service->search(
                $request->per_page ?? 10,
                $request->keyword,
                $dropdown,
                $request->all()
            );

            // ✅ Dropdown Response
            if ($tickets instanceof \Illuminate\Support\Collection) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket dropdown search fetched successfully',
                    'data' => RaiseTicketResource::collection($tickets)
                ]);
            }

            // ✅ Paginated Response (Same as index)
            return response()->json([
                'success' => true,
                'message' => 'Ticket search results fetched successfully',
                'data' => RaiseTicketResource::collection($tickets->items()),
                'pagination' => [
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                    'per_page' => $tickets->perPage(),
                    'total' => $tickets->total(),
                    'from' => $tickets->firstItem(),
                    'to' => $tickets->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error("Ticket search failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function exportTickets(Request $request)
    {
        try {
            $format = strtolower($request->input('format', 'xlsx'));
            $extension = $format === 'csv' ? 'csv' : 'xlsx';

            $filename = 'Tickets_' . now()->format('Ymd_His') . '.' . $extension;
            $path = 'ticketexports/' . $filename;

            $search = $request->input('search');
            $filters = $request->input('filters', []);

            $export = new \App\Exports\TicketExport($search, $filters);

            \Maatwebsite\Excel\Facades\Excel::store(
                $export,
                $path,
                'public',
                $format === 'csv'
                    ? \Maatwebsite\Excel\Excel::CSV
                    : \Maatwebsite\Excel\Excel::XLSX
            );

            $fullUrl = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

            return response()->json([
                'status' => 'success',
                'download_url' => $fullUrl,
            ]);
        } catch (\Exception $e) {
            \Log::error("Ticket export failed: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getTicketsByUser(Request $request)
    {
        $request->validate([
            'flag' => 'required|in:customer,salesman,companyCustomer',
            'id'   => 'required|integer'
        ]);

        try {
            $tickets = $this->service->getTicketsByFlagAndId(
                $request->flag,
                $request->id
            );

            return RaiseTicketResource::collection($tickets);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }
}
