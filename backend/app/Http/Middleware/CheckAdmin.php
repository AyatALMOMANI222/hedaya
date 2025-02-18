<?php

    namespace App\Http\Middleware;
    
    use Closure;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Crypt;
    use Illuminate\Support\Facades\Storage;
    use Symfony\Component\HttpFoundation\Response;
    
    class CheckAdmin
    {
        public function handle(Request $request, Closure $next): Response
        {
            if (!Auth::user() || Auth::user()->role !== 'admin') {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            $imagePath = $request->route('imagePath');
            try {
                $encryptedImage = Storage::get('secure_images/' . $imagePath);
    
                $decryptedImage = Crypt::decrypt($encryptedImage);
    
                $request->attributes->set('decryptedImage', $decryptedImage);
    
                return $next($request);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error decrypting image'], 500);
            }
        }
    }
    

