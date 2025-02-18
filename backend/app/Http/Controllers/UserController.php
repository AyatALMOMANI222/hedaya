<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
                'kyc_status' => 'nullable|string|max:50',
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
                'kyc_status' => $validatedData['kyc_status'],
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
                'success'=> true,
                'message' => 'User updated successfully',
                'user' => $user
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success'=> false,
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


    public function getAllUsers()
{
    try {
        $users = User::all();

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No users found.'
            ], 404);
        }

        return response()->json([
            'success'=> true,
            'message' => 'Users retrieved successfully.',
            'users' => $users
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success'=> false,
            'message' => 'An error occurred while retrieving users.',
            'error' => $e->getMessage()
        ], 500);
    }
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
            'success'=> true,
            'message' => 'User retrieved successfully.',
            'user' => $user
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success'=> false,
            'message' => 'An error occurred while retrieving the user.',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
