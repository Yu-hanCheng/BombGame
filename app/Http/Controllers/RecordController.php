<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Record;
use App\Userrecord;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RecordController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $record = Record::where([['loser', '=', null],['status', '!=', 2]])->get();
        return response()->json(['room_list'=>$record]);
    }
    public function record()
    {
        $record = Record::where('loser', '!=', null)->get();
        return response()->json(['msg'=>$record]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        

        if(!$request->room_id){ //沒有帶房間參數 代表要新建房間
            $player_list[]=$request->name;
            $limit =['low'=>0,'high'=>100]; 
            // dd(json_decode($limit));
            $rand =rand(0,100);
            $record = Record::create([
                'bomb_num'=>$rand,
                'players'=>implode(',',$player_list),
                'status'=>0, //status  0:還不能 1:可以開始 2：正在玩
                'result'=>json_encode($limit),
            ]);

            $player = Userrecord::create([
                'record_id'=>$record->id,
                'name'=>$request->name,
            ]);
            return response()->json(['msg'=>"create new room",'room'=>$record]);
        }

        $record = Record::where('id',$request->room_id)->first();
        $players_list = explode(',', $record->players);
        $cnt = count($players_list);
        if ($cnt==0) {
            return response()->json(['msg'=>"The room is not exist!"]);
        }elseif ($cnt==5) {
            return response()->json(['msg'=>"The room is full!"]);
        }else {
            $va = Validator::make($request->all(), [
                'name' => ['required',
                            'max:15',
                            Rule::unique('userrecords')->where(function ($query)use ($request) {
                            $query->where('record_id', $request->room_id); })],
            ]);
            if ($va->fails()) {
                return response()->json(['result'=>$va->errors()],416);
            }
            $record = Record::where('id',$request->room_id)->first();
           
            if ($record->status==2) {
                return response()->json(['msg'=>"The room is gaming."],403);
            }
            $player = Userrecord::create([
                'record_id'=>$request->room_id,
                'name'=>$request->name,
            ]);
            $record->players=$record->players.','.$request->name;
            $record->status=1;
            $record->save();
            return response()->json(['msg'=>"Enter room successfully!",'room_status'=>$record]);
        }
    }
    public function game(Request $request)
    {
        $out = new \Symfony\Component\Console\Output\ConsoleOutput();
        $out->writeln($request->name.':'.$request->answer);
        $va = Validator::make($request->all(), [
            'name' => ['required',
                        'max:15',
                        Rule::exists('userrecords')->where(function ($query)use ($request) {
                        $query->where('record_id', $request->room_id); })],
            'answer' => 'integer|required|between:1,100',
        ]);
        if ($va->fails()) {
            return response()->json(['result'=>$va->errors()],416);
        }
        $game =  Record::where('id',$request->room_id)->select('bomb_num','result','loser','status')->first();
        if ($game->loser) {
            return response()->json(['result'=>"game over"],200);
        }
        if ($game ->status==0) {
            return response()->json(['result'=>"waiting for others"],200);
        }    
            $new_result = json_decode($game->result);
            if ($new_result->high > $request->answer  and  $request->answer > $new_result->low) {
                
                if ($game->bomb_num > $request->answer ) {
                    $new_result->low=$request->answer+1;
                }elseif ($game->bomb_num < $request->answer) {
                    $new_result->high=$request->answer-1;
                }else {
                    $game_update =  Record::where('id',$request->room_id)->update(['result'=>$request->name,'loser'=>$request->name]);
                    return response()->json(['result'=>"BOOOOMB"],200);
                }
                $game_result=json_encode($new_result);
                $game_update =  Record::where('id',$request->room_id)->update(['result'=>$game_result]);
                $game_msg=$new_result->low."~".$new_result->high;
                return response()->json(['msg'=>$game_msg]);
            }else {
                return response()->json(['msg'=>"The number is out of range!"]);
            }
    }

    public function start(Request $request)
    {
        $new_data = Record::where('id',$request->room_id)->update(['status'=>2]); 
        return response()->json($new_data);
    }

    public function watch(Request $request)
    {
        $room_data = Record::where('id',$request->room_id)->select('result','players','loser')->first();
        
        if ($room_data->loser) {
            return response()->json(['result'=>"game over"]);
        }
        $new_result = json_decode($room_data->result);
        $game_msg=$new_result->low."~".$new_result->high;
        return response()->json(['result'=>$game_msg,'room_data'=>$room_data]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
