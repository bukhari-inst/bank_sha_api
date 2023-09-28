<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransferController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->only('amount', 'pin', 'send_to');

        $validator = Validator::make($data, [
            'amount' => 'required|integer|min:10000',
            'pin' => 'required|digits:6',
            'send_to' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        $sender = auth()->user();
        $receiver = User::select('users.id', 'users.username')
            ->join('wallets', 'wallets.user_id', 'users.id')
            ->where('users.username', $request->send_to)
            ->orWhere('wallets.card_number', $request->send_to)
            ->first();

        $pinChecker = pinChecker($request->pin);

        if (!$pinChecker) {
            return response()->json(['message' => 'Your PIN is wrong'], 400);
        }

        if (!$receiver) {
            return response()->json(['message' => 'User receiver not found'], 404);
        }

        if ($sender->id == $receiver->id) {
            return response()->json(['message' => 'You can not transfer to yourself'], 400);
        }

        $senderWallet = Wallet::where('user_id', $sender->id)->first();
        if ($senderWallet->balance < $request->amount) {
            return response()->json(['message' => 'Your balance is not enough'], 400);
        }
    }
}
