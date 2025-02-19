<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'email' => 'nullable|email|max:255',
                'full_name' => 'nullable|string|max:255',
                'kyc_document_type' => 'nullable|string|max:50',
                'kyc_document_front' => 'nullable|image|max:1024',
                'kyc_document_back' => 'nullable|image|max:1024',
                'kyc_selfie_image' => 'nullable|image|max:1024',
                'referral_code' => 'nullable|string|max:100',
                'referred_by' => 'nullable|string|max:255',
            ]);

            // Find the user by ID
            $user = User::findOrFail($id);

            // Handle file uploads if present
            $kyc_document_front = $this->handleFileUploadAndEncrypt($request, 'kyc_document_front');
            $kyc_document_back = $this->handleFileUploadAndEncrypt($request, 'kyc_document_back');
            $kyc_selfie_image = $this->handleFileUploadAndEncrypt($request, 'kyc_selfie_image');

            // Update the user's data
            $user->update([
                'email' => $validatedData['email'],
                'full_name' => $validatedData['full_name'],
                'kyc_document_type' => $validatedData['kyc_document_type'],
                'kyc_document_front' => $kyc_document_front ?? $user->kyc_document_front,
                'kyc_document_back' => $kyc_document_back ?? $user->kyc_document_back,
                'kyc_selfie_image' => $kyc_selfie_image ?? $user->kyc_selfie_image,
                'kyc_reviewed_at' => $validatedData['kyc_reviewed_at'] ?? $user->kyc_reviewed_at,
                'kyc_reviewed_by' => $validatedData['kyc_reviewed_by'] ?? $user->kyc_reviewed_by,
                'referral_code' => $validatedData['referral_code'] ?? $user->referral_code,
                'referred_by' => $validatedData['referred_by'] ?? $user->referred_by,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function handleFileUploadAndEncrypt(Request $request, $field)
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);

            $imageContent = file_get_contents($file->getPathname());

            $encryptedImage = Crypt::encrypt($imageContent);

            $fileName = time() . '-' . $file->getClientOriginalName();
            $encryptedFilePath = 'secure_images/' . $fileName;

            Storage::put($encryptedFilePath, $encryptedImage);

            return $encryptedFilePath;
        }

        return null;
    }


    public function getAllUsers(Request $request)
    {
        try {
            // التحقق من أن المستخدم أدمن
            if (!Auth::check() || Auth::user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only admins can access this resource.'
                ], 403);
            }
    
            $perPage = $request->input('per_page', 10); 
            $currentPage = $request->input('page', 1); 
    
            $query = User::query();
    
            // إضافة الفلاتر حسب المدخلات
            if ($request->has('full_name')) {
                $query->where('full_name', 'LIKE', '%' . $request->input('full_name') . '%');
            }
    
            if ($request->has('mobile_no')) {
                $query->where('mobile_no', 'LIKE', '%' . $request->input('mobile_no') . '%');
            }
    
            if ($request->has('email')) {
                $query->where('email', 'LIKE', '%' . $request->input('email') . '%');
            }
    
            if ($request->has('kyc_status')) {
                $query->where('kyc_status', '=', $request->input('kyc_status'));
            }
    
            // استرجاع المستخدمين مع الباجيناشن
            $users = $query->paginate($perPage, ['*'], 'page', $currentPage);
    
            if ($users->isEmpty()) {
                return response()->json([
                    'message' => 'No users found.'
                ], 404);
            }
    
            // إرجاع بيانات المستخدمين مع الصور
            $usersData = [];
            foreach ($users as $user) {
                // فك تشفير الصور
                $user->kyc_document_front = $this->decryptImage($user->kyc_document_front);
                $user->kyc_document_back = $this->decryptImage($user->kyc_document_back);
                $user->kyc_selfie_image = $this->decryptImage($user->kyc_selfie_image);
                
                $user->kyc_document_front = str_replace('http://127.0.0.1:8000', '', $user->kyc_document_front);
                $user->kyc_document_back = str_replace('http://127.0.0.1:8000', '', $user->kyc_document_back);
                $user->kyc_selfie_image = str_replace('http://127.0.0.1:8000', '', $user->kyc_selfie_image);
    
                $usersData[] = [
                    'id' => $user->id,
                    'mobile_no'=>$user->mobile_no,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'kyc_status' => $user->kyc_status,
                    'kyc_document_type' => $user->kyc_document_type,
                    'kyc_reviewed_at' => $user->kyc_reviewed_at,
                    'kyc_reviewed_by' => $user->kyc_reviewed_by,
                    'referral_code' => $user->referral_code,
                    'referred_by' => $user->referred_by,
                    'role' => $user->role,
                    'deleted_at' => $user->deleted_at,
                    'kyc_document_front' => $user->kyc_document_front, // الصورة الأولى
                    'kyc_document_back' => $user->kyc_document_back,   // الصورة الثانية
                    'kyc_selfie_image' => $user->kyc_selfie_image,      // الصورة الثالثة
                ];
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully.',
                'user' => $usersData,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total_elements' => $users->total(),
                    'last_page' => $users->lastPage()
                ]
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving users.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    
    
    
    private function decryptImage($encryptedImagePath)
    {
        if ($encryptedImagePath && Storage::exists($encryptedImagePath)) {
            // قراءة الصورة المشفرة
            $encryptedImage = Storage::get($encryptedImagePath);
    
            try {
                // فك التشفير
                $decryptedImage = Crypt::decrypt($encryptedImage);
    
                // حفظ الصورة المفككة في المسار المناسب
                $fileName = basename($encryptedImagePath);
                $publicPath = 'storage/secure_images/' . $fileName;
    
                // حفظ الصورة المفككة في المسار الصحيح
                Storage::put('public/secure_images/' . $fileName, $decryptedImage);
    
                // إرجاع URL الصورة المفككة
                $imageUrl = url($publicPath);
    
                return $imageUrl; // إرجاع URL الصورة
            } catch (\Exception $e) {
                return response()->json([
                    'error'=>'Error decrypting image: ' . $e->getMessage(),
                ],404);
            }
        }
    
        return null;
    }
    
    
    
    
    public function getUserById($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully.',
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function updateKycStatus(Request $request, $id)
    {
        try {
            if (!Auth::check() || Auth::user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Only admins can update KYC status.'
                ], 403);
            }

            $validatedData = $request->validate([
                'kyc_status' => 'required|string|in:pending,approved,rejected',
            ]);

            $user = User::findOrFail($id);

            $user->update([
                'kyc_status' => $validatedData['kyc_status'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KYC status updated successfully',
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating KYC status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }



}
