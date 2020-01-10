<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Google_Client;
use Google_Service_Fitness;
use Google_Service_People;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    public function redirectToProvider($driver)
    {
        return Socialite::driver('google')
            ->scopes([Google_Service_Fitness::FITNESS_ACTIVITY_READ])
            ->redirect();
    }

    public function handleProviderCallback($driver)
    {

        try {
            $user = Socialite::driver('google')->user();
            $google_client_token = [
                'access_token' => $user->token,
                'refresh_token' => $user->refreshToken,
                'expires_in' => $user->expiresIn
            ];

            $client = new Google_Client();
            $client->setApplicationName("Laravel");
            $client->setDeveloperKey(env('GOOGLE_SERVER_KEY'));
            $client->setAccessToken(json_encode($google_client_token));


            $fitness_service = new Google_Service_Fitness($client);

            $dataSources = $fitness_service->users_dataSources;
            $dataSets = $fitness_service->users_dataSources_datasets;

            $listDataSources = $dataSources->listUsersDataSources("me");
            dump('list data sources',$listDataSources);
            $timezone = "GMT+0530";
            $today = date("Y-m-d");
            $endTime = strtotime(date("Y-m-d", strtotime("-6 day")));
            $startTime = strtotime(date("Y-m-d", strtotime("-12 day")));
            dump('sendTime:',$endTime);
            dump('startTime:',$startTime);
            while($listDataSources->valid()) {
                $dataSourceItem = $listDataSources->next();
                dump($dataSourceItem);
                if ($dataSourceItem['dataType']['name'] == "com.google.step_count.delta") {
                    $dataStreamId = $dataSourceItem['dataStreamId'];
                    $listDatasets = $dataSets->get("me", $dataStreamId, $startTime.'000000000'.'-'.$endTime.'000000000');
                    $step_count = 0;
                    while($listDatasets->valid()) {
                        $dataSet = $listDatasets->next();
                        $dataSetValues = $dataSet['value'];

                        if ($dataSetValues && is_array($dataSetValues)) {
                            foreach($dataSetValues as $dataSetValue) {
                                $step_count += $dataSetValue['intVal'];
                            }
                        }
                    }
                    print("STEP: ".$step_count."<br />");
                };
            }
            die();
        } catch (\Exception $e) {
            dd($e->getMessage());
            return redirect()->route('login');
        }

        $existingUser = User::where('email', $user->getEmail())->first();

        if ($existingUser) {
            auth()->login($existingUser, true);
        } else {
            $newUser                    = new User;
            $newUser->provider_name     = $driver;
            $newUser->provider_id       = $user->getId();
            $newUser->name              = $user->getName();
            $newUser->email             = $user->getEmail();
            $newUser->email_verified_at = now();
            $newUser->avatar            = $user->getAvatar();
            $newUser->save();

            auth()->login($newUser, true);
        }

        return redirect($this->redirectPath());
    }
}
