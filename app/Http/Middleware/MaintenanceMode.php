<?php

namespace App\Http\Middleware;

use Closure;

class MaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(config('app.maintenance.enabled'))
        {
            if(auth_is_admin())
            {
                return redirect()->route('admin');
            }
            
            if(config('app.maintenance.auto_disable') && config('app.maintenance.expires_at'))
            {
                if(format_date(config('app.maintenance.expires_at'), 'Y-m-d h:i:s') <= date('Y-m-d h:i:s'))
                {
                    $settings = \App\Models\Setting::first();

                    $general_settings = json_decode($settings->general);
                    $maintenance_sets = json_decode($general_settings->maintenance);

                    $maintenance_sets->enabled = '0';

                    $general_settings->maintenance = json_encode($maintenance_sets);

                    $settings->general = json_encode($general_settings);

                    $settings->save();

                    return $next($request);
                }
            }

            abort('307');
        }

        return $next($request);
    }
}
