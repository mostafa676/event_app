<?php
namespace App\Http\Controllers\HallOwner;
use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Models\CoordinatorAssignment;
use App\Models\CoordinatorType;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;

class ReseravtionController extends Controller
{
public function assignCoordinatorsToReservation($reservationId)
{
    $reservation = Reservation::with('services.service')->findOrFail($reservationId);
    $assignments = [];
$existingServiceIds=[] ;
    foreach ($reservation->services as $reservationService) {
        $service = $reservationService->service;
       if (in_array($service->id, $existingServiceIds)) {
    continue;
}
$existingServiceIds[] = $service->id;
        //(مثلاً DJ للطرب، Chef للطعام)
        $coordinatorType = CoordinatorType::where('name_en', $service->name_en)->first();

        if ($coordinatorType) {
            $coordinator = Coordinator::where('coordinator_type_id', $coordinatorType->id)->first();

            if ($coordinator) {
                $assignment = CoordinatorAssignment::create([
                     'reservation_id' => $reservation->id,
                    'coordinator_id' => $coordinator->id,
                    'reservation_service_id' => $reservationService->id,
                    'assigned_by' => Auth::id(),
                    'status' => 'pending',
                ]);
                  
                $assignments[] = [
                    'assignment_id' => $assignment->id,
                    'service' => $service->name_en,
                    'coordinator' => $coordinator?->user?->name ?? 'يا لهوووي  ',
                    'status' => $assignment->status,
                ];
            }
        }
    }

    return response()->json([
        'status' => true,
        'message' => 'تم توزيع المهام على المنسقين بنجاح.',
        'assignments' => $assignments,
    ]);
}

    
}
