<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق من محاولة تعديل أو حذف السوبر أدمن
        if ($request->route('record')) {
            $adminId = $request->route('record');
            
            // إذا كان المسار يحتوي على معرف المدير
            if (is_numeric($adminId)) {
                $admin = \App\Models\Admin::find($adminId);
                
                if ($admin && $admin->email === 'admin@mrshife.com') {
                    // منع تعديل أو حذف السوبر أدمن
                    if (in_array($request->method(), ['PUT', 'PATCH', 'DELETE'])) {
                        abort(403, 'لا يمكن تعديل أو حذف السوبر أدمن');
                    }
                }
            }
        }

        // التحقق من محاولة تعديل صلاحيات السوبر أدمن
        if ($request->has('roles') && $request->user('admin')) {
            $currentAdmin = $request->user('admin');
            
            if ($currentAdmin->email === 'admin@mrshife.com') {
                // منع تعديل صلاحيات السوبر أدمن
                if ($request->method() === 'POST' && $request->is('admin/admins/*/roles/*')) {
                    abort(403, 'لا يمكن تعديل صلاحيات السوبر أدمن');
                }
            }
        }

        return $next($request);
    }
}
