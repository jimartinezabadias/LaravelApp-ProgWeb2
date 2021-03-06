<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\User;
use App\Http\Requests\CreateMachineRequest;
use Illuminate\Http\Request;

class MachineController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        // $machines = [];
        $search = $request->get('search');

        $user = auth()->user();

        if ( $user->isAdmin() )
        {
            $machines = Machine::with('user')
            ->where('type','iLIKE', "%{$search}%" )
            ->orWhere('owner','iLIKE', "%{$search}%" )
            ->orWhere('model','iLIKE', "%{$search}%" )
            ->orWhere('trademark','iLIKE', "%{$search}%" )
            ->paginate(10);
        } 
        else 
        {
            $machines = $user->machines()
            ->where( function ($query) use ($search) {
                return $query->where('type','iLIKE', "%{$search}%" )
                ->orWhere('owner','iLIKE', "%{$search}%" )
                ->orWhere('model','iLIKE', "%{$search}%" )
                ->orWhere('trademark','iLIKE', "%{$search}%" );
            })
            ->paginate(10);
        }
            
        return view('machines.index', [
            'machines' => $machines
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create',Machine::class);
        $clients = User::where('role','client')->get();
        return view('machines.create', ['clients' => $clients]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateMachineRequest $request)
    {
        //
        $this->authorize('create',Machine::class);
        $input = $request->all();
        
        $new_machine = new Machine;
        $new_machine->owner = $input['owner'];
        $new_machine->model = $input['model'];
        $new_machine->trademark = $input['trademark'];
        $new_machine->type = $input['type'];
        $new_machine->user_id = $input['user_id'];
        
        $new_machine->save();

        return redirect( route('machines.show', $new_machine->id ) );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Machine  $machine
     * @return \Illuminate\Http\Response
     */
    public function show(Machine $machine)
    {
        //
        $this->authorize('view',$machine);
        $machineWithServices = Machine::with('services')->find($machine->id);
        return view('machines.show', [ 'machine' => $machineWithServices ] );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Machine  $machine
     * @return \Illuminate\Http\Response
     */
    public function edit(Machine $machine)
    {
        //
        $this->authorize('update',$machine);
        $clients = User::where(['role' => 'client'])->get();
        return view('machines.edit', [
            'machine' => $machine,
            'clients' => $clients
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Machine  $machine
     * @return \Illuminate\Http\Response
     */
    public function update(CreateMachineRequest $request, Machine $machine)
    {
        //
        $this->authorize('update',$machine);
        $input = $request->all();
        $machine->owner = $input['owner'];
        $machine->type = $input['type'];
        $machine->model = $input['model'];
        $machine->trademark = $input['trademark'];
        if ($input['user_id'] !== "Sin Usuario")
        {
            $machine->user_id = $input['user_id'];
        }
        else
        {
            $machine->user_id = null;
        }
        $machine->save();
        return back()->with('success','Machine updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Machine  $machine
     * @return \Illuminate\Http\Response
     */
    public function destroy(Machine $machine)
    {
        //
        // $this->authorize('delete',App\Models\Machine::class);
        $this->authorize('delete',$machine);
        foreach ($machine->services as $service) {
            $service->delete();
        }
        $machine->delete();
        return redirect('machines');
    }
}
