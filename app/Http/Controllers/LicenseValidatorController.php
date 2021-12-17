<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, DB };
use App\Models\Transaction;


class LicenseValidatorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('back.license_validation');
    }



    public function validate_license(Request $request)
    {
        $request->validate(['licenseKey' => 'required|uuid']);

        if(!auth_is_admin())
        {   
            $bearer = $request->bearerToken() ?? abort(404);

            $credentials = explode(':', base64_decode($bearer), 2);

            count($credentials) === 2 || abort(404);

            list($email, $pwd) = $credentials;

            Auth::validate(['email' => $email, 'password' => $pwd, 'role' => 'admin']) || abort(404);
        }

        $response = ['status' => false, 'data' => []];

        $data = DB::select("SELECT transactions.created_at, transactions.reference_id, transactions.order_id, transactions.processor,
                        transactions.transaction_id, transactions.cs_token, transactions.guest_token, buyers.email AS buyer_email, 
                        buyers.name AS buyer_name, products.name
                        FROM transactions 
                        JOIN products ON transactions.products_ids LIKE CONCAT('%\'', products.id, '\'%')
                        JOIN users AS buyers ON transactions.user_id = buyers.id
                        WHERE transactions.licenses LIKE ? AND transactions.licenses IS NOT NULL LIMIT 1", 
                        ['%"'.$request->licenseKey.'"%']);

        if($data = array_shift($data))
        {
          $response['data']   = $data;
          $response['status'] = true;
        }

        extract($response);

        return response()->json(compact('status', 'data'));
    }

    
}
